<?php

namespace App\Pagerfanta;

use App\Doctrine\TypicalOfferType;
use App\Entity\Location;
use App\Entity\Member;
use App\Form\CustomDataClass\SearchFormRequest;
use App\Utilities\SessionSingleton;
use App\Utilities\TranslatorSingleton;
use Doctrine\ORM\EntityManagerInterface;
use EnvironmentExplorer;
use Exception;
use Pagerfanta\Adapter\AdapterInterface;
use SearchModel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchAdapter implements AdapterInterface
{
    /** @var array */
    private $modelData;

    /** @var SearchModel */
    private $model;

    /**
     * SearchAdapter constructor.
     *
     * @param SearchFormRequest $data       The query parameters for the search
     * @param SessionInterface  $session
     * @param string            $dbHost
     * @param string            $dbName
     * @param string            $dbUser
     * @param string            $dbPassword
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws AccessDeniedException
     */
    public function __construct($data, $session, $dbHost, $dbName, $dbUser, $dbPassword, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        // Kick-start the Symfony session. This replaces session_start() in the
        // old code, which is now turned off.
        $session->start();

        if (!$session->has('IdMember')) {
            $rememberMeToken = unserialize($session->get('_security_main'));
            if (null === $rememberMeToken) {
                throw new AccessDeniedException();
            }
            if (false !== $rememberMeToken) {
                /** @var Member $user */
                $user = $rememberMeToken->getUser();
                if (null !== $user) {
                    $session->set('IdMember', $user->getId());
                    $session->set('MemberStatus', $user->getStatus());
                    $session->set('APP_User_id', $user->getId());
                }
            }
        }

        // Make sure the Rox classes find this session
        SessionSingleton::createInstance($session);
        TranslatorSingleton::createInstance($translator);

        // make sure everything's setup for the old code used below
        $environmentExplorer = new EnvironmentExplorer();
        $environmentExplorer->initializeGlobalState(
            $dbHost,
            $dbName,
            $dbUser,
            $dbPassword
        );
        $dbPassword = str_repeat('*', \strlen($dbPassword));
        $this->model = new SearchModel();
        $this->modelData = $this->prepareModelData($data);

        // Determine if we search for a country or an admin unit and call prepareQuery accordingly
        $admin1 = false;
        $country = false;
        $repository = $em->getRepository(Location::class);
        /** @var Location $location */
        $location = null;
        try {
            $location = $repository->find($data->location_geoname_id);
        } catch (Exception $e) {
            // nothing found?
            $e->getCode();
        }
        if (null !== $location && 'A' === $location->getFclass()) {
            // check if found unit is a country
            if (false === strstr($location->getFcode(), 'PCL')) {
                $admin1 = $location->getAdmin1();
            }
            $country = $location->getCountry()->getCountry();
        }
        $this->model->prepareQuery($this->modelData, $admin1, $country);
    }

    /**
     * Returns the number of results.
     */
    public function getNbResults(): int
    {
        return $this->model->getMembersCount();
    }

    /**
     * Returns full results.
     */
    public function getFullResults(): array
    {
        $results = $this->model->getResultsForLocation($this->modelData);

        return $results;
    }

    /**
     * Returns map data.
     */
    public function getMapResults(): array
    {
        $results = $this->model->getResultsForLocation($this->modelData);

        $results['members'] = null;
        $results['map'] = array_map(function ($value) {
            $value->Username = '';

            return $value;
        }, $results['map']);

        return $results;
    }

    /**
     * Returns an slice of the results.
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $this->modelData['search-number-items'] = $length;
        $this->modelData['search-page'] = ($offset / $length) + 1;
        $results = $this->model->getResultsForLocation($this->modelData);

        return $results['members'];
    }

    private function prepareModelData(SearchFormRequest $data): array
    {
        $vars = [];
        $vars['search-location'] = $data->location;
        $vars['location-geoname-id'] = $data->location_geoname_id;
        $vars['location-latitude'] = $data->location_latitude;
        $vars['location-longitude'] = $data->location_longitude;
        $vars['ne-latitude'] = $data->ne_latitude;
        $vars['ne-longitude'] = $data->ne_longitude;
        $vars['sw-latitude'] = $data->sw_latitude;
        $vars['sw-longitude'] = $data->sw_longitude;
        $vars['search-accommodation'] = [];

        if ($data->accommodation_anytime) {
            $vars['search-accommodation'][] = 'anytime';
        }

        if ($data->accommodation_neverask) {
            $vars['search-accommodation'][] = 'neverask';
        }

        $vars['search-has-profile-picture'] = $data->profile_picture;
        $vars['search-has-about-me'] = $data->about_me;
        $vars['search-has-comments'] = $data->has_comments;

        foreach (
            [
            'offerdinner' => TypicalOfferType::DINNER,
            'offertour' => TypicalOfferType::GUIDED_TOUR,
            'accessible' => TypicalOfferType::WHEELCHAIR_ACCESSIBLE,
            ] as $param => $value
        ) {
            if ($data->$param) {
                $vars['search-typical-offers'][] = $value;
            }
        }

        foreach (
            [
            'no_smoking' => 'NoSmoker',
            'no_alcohol' => 'NoAlchool',
            'no_drugs' => 'NoDrugs',
            ] as $param => $value
        ) {
            if ($data->$param) {
                $vars['search-restriction'][] = $value;
            }
        }

        $vars['search-distance'] = $data->distance;
        $vars['search-can-host'] = $data->can_host;

        $gender = [
            null => '',
            1 => 'male',
            2 => 'female',
            4 => 'other',
        ];

        $vars['search-gender'] = $gender[$data->gender] ?? '';
        $vars['search-age-minimum'] = $data->min_age;
        $vars['search-age-maximum'] = $data->max_age;
        $vars['search-groups'] = $data->groups;
        $vars['search-languages'] = $data->languages;
        $vars['search-text'] = $data->keywords;
        $vars['search-last-login'] = $data->last_login;
        $vars['search-page'] = $data->page ?? 1;
        $vars['search-sort-order'] = $data->order;
        $vars['search-sort-direction'] = $data->direction;
        $vars['search-number-items'] = $data->items;

        return $vars;
    }
}
