<?php

namespace App\Controller;

use App\Doctrine\AccommodationType;
use App\Entity\Member;
use App\Entity\Message;
use App\Form\HostingRequestGuest;
use App\Form\HostingRequestHost;
use App\Model\ConversationModel;
use App\Model\HostingRequestModel;
use App\Service\Mailer;
use App\Utilities\ManagerTrait;
use App\Utilities\TranslatorTrait;
use Exception;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class HostingRequestController.
 *
 * Ignore complexity warning. \todo fix this.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RequestController extends BaseRequestAndInvitationController
{
    use ManagerTrait;
    use TranslatorTrait;

    private Mailer $mailer;

    public function __construct(ConversationModel $conversationModel, HostingRequestModel $requestModel, Mailer $mailer)
    {
        parent::__construct($requestModel);

        $this->mailer = $mailer;
        $this->conversationModel = $conversationModel;
    }

    /**
     * Deals with replies to hosting requests.
     */
    public function reply(Request $request, Message $message): Response
    {
        // determine if guest or host reply to a request
        $guest = $message->getInitiator();
        $host = $message->getReceiver() === $guest ? $message->getSender() : $message->getReceiver();

        $member = $this->getUser();
        if ($member === $guest) {
            return $this->guestReply($request, $message, $guest, $host);
        }

        return $this->hostReply($request, $message, $guest, $host);
    }

    public function guestReply(Request $request, Message $hostingRequest, Member $guest, Member $host): Response
    {

        if ($this->model->hasExpired($hostingRequest)) {
            $this->addExpiredFlash($host);

            return $this->redirectToRoute('conversation_view', ['id' => $hostingRequest->getId()]);
        }

        list($thread) = $this->conversationModel->getThreadInformationForMessage($hostingRequest);

        // keep all information from current hosting request except the message text
        $hostingRequest = $this->getRequestClone($hostingRequest);

        // A reply consists of a new message and maybe a change of the status of the hosting request
        // Additionally the user might change the dates of the request or cancel the request altogether
        /** @var Form $requestForm */
        $requestForm = $this->createForm(HostingRequestGuest::class, $hostingRequest);
        $requestForm->handleRequest($request);

        if ($requestForm->isSubmitted() && $requestForm->isValid()) {
            $realParent = $this->conversationModel->getLastMessageInConversation($hostingRequest);

            $newRequest = $this->persistRequest($requestForm, $realParent, $guest, $host);

            $subject = $this->getSubjectForReply($newRequest);

            $this->sendGuestReplyNotification(
                $host,
                $guest,
                $newRequest,
                $subject,
                ($newRequest->getRequest()->getId() !== $realParent->getRequest()->getId())
            );
            $this->addTranslatedFlash('success', 'flash.notification.updated');

            return $this->redirectToRoute('conversation_view', ['id' => $newRequest->getId()]);
        }

        return $this->render('request/reply_from_guest.html.twig', [
            'guest' => $guest,
            'host' => $host,
            'form' => $requestForm->createView(),
            'thread' => $thread,
        ]);
    }

    public function hostReply(Request $request, Message $hostingRequest, Member $guest, Member $host): Response
    {
        if ($this->model->hasExpired($hostingRequest)) {
            $this->addExpiredFlash($guest);

            return $this->redirectToRoute('conversation_view', ['id' => $hostingRequest->getId()]);
        }

        list($thread) = $this->conversationModel->getThreadInformationForMessage($hostingRequest);

        // keep all information from current hosting request except the message text
        $hostingRequest = $this->getRequestClone($hostingRequest);

        /** @var Form $requestForm */
        $requestForm = $this->createForm(HostingRequestHost::class, $hostingRequest);
        $requestForm->handleRequest($request);

        if ($requestForm->isSubmitted() && $requestForm->isValid()) {
            $realParent = $this->conversationModel->getLastMessageInConversation($hostingRequest);

            $newRequest = $this->persistRequest($requestForm, $realParent, $host, $guest);

            $subject = $this->getSubjectForReply($newRequest);

            $this->sendHostReplyNotification(
                $host,
                $guest,
                $newRequest,
                $subject,
                ($newRequest->getRequest()->getId() !== $realParent->getRequest()->getId())
            );
            $this->addTranslatedFlash('notice', 'flash.notification.updated');

            return $this->redirectToRoute('conversation_view', ['id' => $newRequest->getId()]);
        }

        return $this->render('request/reply_from_host.html.twig', [
            'guest' => $guest,
            'host' => $host,
            'form' => $requestForm->createView(),
            'thread' => $thread,
        ]);
    }

    /**
     * @Route("/new/request/{username}", name="hosting_request")
     *
     * @throws Exception
     */
    public function newHostingRequest(Request $request, Member $host): Response
    {
        /** @var Member $member */
        $member = $this->getUser();
        if ($member === $host) {
            $this->addTranslatedFlash('notice', 'flash.request.self');

            return $this->redirectToRoute('members_profile', ['username' => $member->getUsername()]);
        }

        if (!$host->isBrowseable()) {
            $this->addTranslatedFlash('note', 'flash.member.invalid');
        }

        if (
            $this->conversationModel->hasRequestLimitExceeded(
                $member,
                $this->getParameter('new_members_requests_per_hour'),
                $this->getParameter('new_members_requests_per_day')
            )
        ) {
            $this->addTranslatedFlash('error', 'flash.request.limit');
            $referrer = $request->headers->get('referer');

            return $this->redirect($referrer);
        }

        if (AccommodationType::NO === $host->getAccommodation()) {
            $this->addTranslatedFlash('notice', 'request.not.hosting');

            return $this->redirectToRoute('members_profile', ['username' => $host->getUsername()]);
        }

        $requestForm = $this->createForm(HostingRequestGuest::class, null);
        $requestForm->handleRequest($request);

        if ($requestForm->isSubmitted() && $requestForm->isValid()) {
            // Write request to database after doing some checks
            $hostingRequest = $this->getMessageFromData($requestForm->getData(), $member, $host);

            $em = $this->getDoctrine()->getManager();
            $em->persist($hostingRequest);
            $em->flush();

            $this->sendInitialRequestNotification(
                $host,
                $member,
                $hostingRequest
            );
            $this->addTranslatedFlash('success', 'flash.request.sent');

            return $this->redirectToRoute('members_profile', ['username' => $host->getUsername()]);
        }

        return $this->render('request/request.html.twig', [
            'guest' => $member,
            'host' => $host,
            'form' => $requestForm->createView(),
        ]);
    }

    protected function sendGuestReplyNotification(
        Member $host,
        Member $guest,
        Message $request,
        string $subject,
        bool $requestChanged
    ): void {
        $this->sendRequestNotification(
            $guest,
            $host,
            $host,
            $request,
            $subject,
            'reply_from_guest',
            $requestChanged
        );
    }

    private function sendInitialRequestNotification(Member $host, Member $guest, Message $request)
    {
        $subject = $request->getSubject()->getSubject();

        $this->sendRequestNotification($host, $guest, $host, $request, $subject, 'request', false);
    }

    private function sendHostReplyNotification(
        Member $host,
        Member $guest,
        Message $request,
        string $subject,
        bool $requestChanged
    ): void {
        $this->sendRequestNotification(
            $host,
            $guest,
            $host,
            $request,
            $subject,
            'reply_from_host',
            $requestChanged
        );
    }

    /**
     * The requestChanged parameter triggers a PHPMD warning which is out of place in this case.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param mixed $subject
     * @param mixed $template
     * @param mixed $requestChanged
     */
    public function sendRequestNotification(
        Member $sender,
        Member $receiver,
        Member $host,
        Message $request,
        $subject,
        $template,
        $requestChanged
    ) {
        // Send mail notification
        $this->mailer->sendMessageNotificationEmail($sender, $receiver, $template, [
            'host' => $host,
            'subject' => $subject,
            'message' => $request,
            'request' => $request->getRequest(),
            'changed' => $requestChanged,
        ]);

        return true;
    }
}