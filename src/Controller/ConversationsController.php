<?php

namespace App\Controller;

use App\Entity\Member;
use App\Form\CustomDataClass\MessageIndexRequest;
use App\Form\MessageIndexFormType;
use App\Model\ConversationsModel;
use App\Pagerfanta\ConversationsAdapter;
use App\Pagerfanta\ConversationsWithAdapter;
use App\Pagerfanta\DeletedAdapter;
use App\Pagerfanta\InvitationsAdapter;
use App\Pagerfanta\MessagesAdapter;
use App\Pagerfanta\RequestsAdapter;
use App\Pagerfanta\SpamAdapter;
use App\Utilities\TranslatedFlashTrait;
use App\Utilities\TranslatorTrait;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This controller handles all requests regarding lists of conversations (messages, hosting requests and invitations).
 */
class ConversationsController extends AbstractController
{
    use TranslatedFlashTrait;
    use TranslatorTrait;

    protected ConversationsModel $conversationsModel;

    public function __construct(ConversationsModel $conversationsModel)
    {
        $this->conversationsModel = $conversationsModel;
    }

    /**
     * @Route("/conversations/spam/", name="conversations_spam")
     */
    public function showSpamConversations(Request $request): Response
    {
        return $this->handleRequest($request, 'spam');
    }

    /**
     * @Route("/conversations/deleted/", name="conversations_deleted")
     */
    public function showDeletedConversations(Request $request): Response
    {
        return $this->handleRequest($request, 'deleted');
    }

    /**
     * @Route("/{conversationsType}/", name="conversations",
     *     requirements={"conversationsType":"conversations|messages|requests|invitations"})
     */
    public function showConversations(Request $request, string $conversationsType): Response
    {
        return $this->handleRequest($request, $conversationsType);
    }

    /**
     * @Route("/conversations/with/{username}", name="conversations_with")
     *
     * @throws InvalidArgumentException
     */
    public function allConversationsWithMember(Request $request, Member $other, EntityManagerInterface $entityManager): Response
    {
        /** @var Member $member */
        $member = $this->getUser();
        $page = $request->query->get('page', '1');

        $messages = new PagerFanta(new ConversationsWithAdapter($entityManager, $member, $other));
        $messages->setMaxPerPage(15);
        $messages->setCurrentPage($page);

        return $this->render('conversations/between.html.twig', [
            'items' => $messages,
            'otherMember' => $other,
        ]);
    }

    private function getAdapterFromConversationsType(string $conversationsType): string
    {
        $conversationsAdapter = '';
        switch ($conversationsType) {
            case 'conversations':
                $conversationsAdapter = ConversationsAdapter::class;
                break;
            case 'messages':
                $conversationsAdapter = MessagesAdapter::class;
                break;
            case 'requests':
                $conversationsAdapter = RequestsAdapter::class;
                break;
            case 'invitations':
                $conversationsAdapter = InvitationsAdapter::class;
                break;
            case 'spam':
                $conversationsAdapter = SpamAdapter::class;
                break;
            case 'deleted':
                $conversationsAdapter = DeletedAdapter::class;
                break;
        }

        return $conversationsAdapter;
    }

    private function handleRequest(Request $request, string $active): Response
    {
        /** @var Member $member */
        $member = $this->getUser();

        $page = $request->query->get('page', '1');
        $unreadOnly = '1' === $request->query->get('unread_only', '0');
        $initiator = $request->query->get('initiator', '2');

        $adapter = $this->getAdapterFromConversationsType($active);
        $conversationsAdapter = new $adapter(
            $this->getDoctrine()->getManager(),
            $member,
            $initiator,
            $unreadOnly
        );

        $conversations = new Pagerfanta($conversationsAdapter);
        $conversations->setMaxPerPage(15);
        $conversations->setCurrentPage($page);

        $messageIds = [];
        foreach ($conversations->getIterator() as $key => $val) {
            $messageIds[$key] = $val->getId();
        }

        $messageRequest = new MessageIndexRequest();
        $form = $this->createForm(MessageIndexFormType::class, $messageRequest, [
            'folder' => $active,
            'ids' => $messageIds,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $clickedButton = $form->getClickedButton()->getName();

            $redirectResponse = $this->handleButtonClick($active, $clickedButton, $member, $request, $data);
            if (null !== $redirectResponse) {
                return $redirectResponse;
            }
        }

        return $this->render('conversations/conversations.html.twig', [
            'form' => $form->createView(),
            'conversations' => $conversations,
            'showUnreadOnly' => $unreadOnly,
            'initiator' => $initiator,
            'submenu' => [
                'active' => $active,
                'items' => $this->getSubMenuItems(),
            ],
        ]);
    }

    /**
     * @param mixed $data
     */
    private function handleButtonClick(
        string $active,
        string $clickedButton,
        Member $member,
        Request $request,
        $data
    ): ?RedirectResponse {
        $conversationIds = $data->getMessages();
        if ('purge' === $clickedButton) {
            $this->conversationsModel->markConversationsPurged($member, $conversationIds);
            $this->addTranslatedFlash('notice', 'flash.purged');

            return $this->redirect($request->getRequestUri());
        }
        if ('delete' === $clickedButton) {
            if ('deleted' === $active) {
                $this->conversationsModel->unmarkConversationsDeleted($member, $conversationIds);
                $this->addTranslatedFlash('notice', 'flash.undeleted');

                return $this->redirect($request->getRequestUri());
            }
            $this->conversationsModel->markConversationsDeleted($member, $conversationIds);
            $this->addTranslatedFlash('notice', 'flash.deleted');

            return $this->redirect($request->getRequestUri());
        }
        if ('spam' === $clickedButton) {
            if ('spam' === $active) {
                $this->conversationsModel->unmarkConversationsAsSpam($member, $conversationIds);
                $this->addTranslatedFlash('notice', 'flash.marked.nospam');

                return $this->redirect($request->getRequestUri());
            }
            $this->conversationsModel->markConversationsAsSpam($member, $conversationIds);
            $this->addTranslatedFlash('notice', 'flash.marked.spam');

            return $this->redirect($request->getRequestUri());
        }

        return null;
    }

    private function getSubMenuItems()
    {
        return [
            'conversations' => [
                'key' => 'conversations',
                'url' => $this->generateConversationsUrl('conversations'),
            ],
            'messages' => [
                'key' => 'messages',
                'url' => $this->generateConversationsUrl('messages'),
            ],
            'requests' => [
                'key' => 'requests',
                'url' => $this->generateConversationsUrl('requests'),
            ],
            'invitations' => [
                'key' => 'invitations',
                'url' => $this->generateConversationsUrl('invitations'),
            ],
            'spam' => [
                'key' => 'conversations.spam',
                'url' => $this->generateConversationsUrl('spam'),
            ],
            'deleted' => [
                'key' => 'conversations.deleted',
                'url' => $this->generateConversationsUrl('deleted'),
            ],
        ];
    }

    private function generateConversationsUrl(string $conversationsType): string
    {
        if ('spam' === $conversationsType || 'deleted' === $conversationsType) {
            return $this->generateUrl('conversations_' . $conversationsType);
        }

        return $this->generateUrl('conversations', ['conversationsType' => $conversationsType]);
    }
}
