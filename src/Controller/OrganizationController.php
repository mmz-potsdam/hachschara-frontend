<?php

// src/Controller/OrganizationController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
class OrganizationController extends BaseController
{
    protected $pageSize = 500;

    #[Route(path: '/organization', name: 'organization-index')]
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        TranslatorInterface $translator
    ) {
        $locale = $request->getLocale();

        $qb = $entityManager
            ->createQueryBuilder();

        $nameSort = 'O.name';
        if ($locale != \App\Entity\Site::$defaultLocale) {
            $nameSort = sprintf(
                "CONCAT_WS('', JSON_UNQUOTE(JSON_EXTRACT(O.translations ,'$.%s.name')), %s)",
                $locale,
                $nameSort
            );
        }

        $qb->select([
            'O',
            $nameSort . ' HIDDEN nameSort',
        ])
            ->distinct()
            ->from('\App\Entity\Organization', 'O')
            ->innerJoin('O.siteReferences', 'SR')
            ->innerJoin('SR.site', 'PR')
            ->where('O.status IN (0,1)')
            ->andWhere('PR.status IN (1)')
            ->orderBy('nameSort')
        ;

        $pagination = $this->buildPagination($request, $paginator, $qb->getQuery(), [
            // the following leads to wrong display in combination with our
            // helper.pagination_sortable()
            // 'defaultSortFieldName' => 'nameSort', 'defaultSortDirection' => 'asc',
        ]);

        return $this->render('Organization/index.html.twig', [
            'pageTitle' => $translator->trans('Organizations'),
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/organization/gnd/{gnd}.jsonld', requirements: ['gnd' => '[0-9xX\-]+'], name: 'organization-by-gnd-jsonld')]
    #[Route(path: '/organization/gnd/{gnd}', requirements: ['gnd' => '[0-9xX\-]+'], name: 'organization-by-gnd')]
    #[Route(path: '/organization/{id}.jsonld', name: 'organization-jsonld', requirements: ['id' => '\d+'])]
    #[Route(path: '/organization/{id}', name: 'organization', requirements: ['id' => '\d+'])]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        $id = null,
        $gnd = null
    ) {
        $criteria = new \Doctrine\Common\Collections\Criteria();

        if (!empty($id)) {
            $criteria->where($criteria->expr()->eq('id', $id));
        }
        else if (!empty($gnd)) {
            $criteria->where($criteria->expr()->eq('gnd', $gnd));
        }
        else {
            return $this->redirectToRoute('organization-index');
        }

        $criteria->andWhere($criteria->expr()->neq('status', -1));

        $orgRepo = $entityManager
                ->getRepository('App\Entity\Organization');
        $organizations = $orgRepo->matching($criteria);

        if (0 == count($organizations)) {
            return $this->redirectToRoute('organization-index');
        }

        $organization = $organizations[0];
        // $organization->setDateModified(\App\Search\PersonListBuilder::fetchDateModified($entityManager->getConnection(), $organization->getId()));

        $locale = $request->getLocale();
        if (in_array($request->get('_route'), [ 'organization-jsonld', 'organization-by-gnd-jsonld' ])) {
            return new JsonLdResponse($organization->jsonLdSerialize($locale));
        }

        $routeName = 'organization';
        $routeParams = [ 'id' => $organization->getId() ];
        if (!empty($organization->getGnd())) {
            $routeName = 'organization-by-gnd';
            $routeParams = [ 'gnd' => $organization->getGnd() ];
        }

        if ($organization->hasInfo()) {
            $citeProc = $this->instantiateCiteProc($locale);
            // expand the publications
            $organization->buildInfoFull($entityManager, $citeProc);
        }

        return $this->render('Organization/detail.html.twig', [
            'pageTitle' => $organization->getName(), // TODO: lifespan in brackets
            'organization' => $organization,
            'mapMarkers' => $this->buildMapMarkers($entityManager, $organization),
            'pageMeta' => [
                'jsonLd' => $organization->jsonLdSerialize($locale),
                // 'og' => $this->buildOg($person, $routeName, $routeParams),
                // 'twitter' => $this->buildTwitter($person, $routeName, $routeParams),
            ],
        ]);
    }

    protected function buildMapMarkers(EntityManagerInterface $entityManager, $entity)
    {
        $markers = [];

        $places = [];

        $foundingLocation = $entity->getFoundingLocationInfo();
        if (!empty($foundingLocation) && !empty($foundingLocation['geo'])) {
            $places[] = [
                'info' => $foundingLocation,
                'label' => 'Founding Location',
            ];
        }

        // places of activity
        $addresses = $entity->getAddressesSeparated(null, false, true);
        // we currently have geo, so get all tgn
        $ids = array_filter(array_unique(array_column($addresses, 'place_id')));
        $placesByIds = [];
        if (!empty($ids)) {
            foreach ($this->hydratePlacesByIds($entityManager, $ids, true) as $place) {
                $placesByIds[$place->getId()] = $place;
            }
        }

        foreach ($addresses as $address) {
            $id = $address['place_id'];
            if (!empty($id) && array_key_exists($id, $placesByIds)) {
                $place = $placesByIds[$id];
                $places[] = [
                    'info' => [
                        'geo' => $place->getGeo(),
                        'name' => $place->getNameLocalized(),
                        'tgn' => $place->getTgn(),
                        'address' => $address,
                    ],
                    'label' => 'Place of Activity',
                ];
            }
        }

        foreach ($places as $place) {
            $value = $group = null;
            switch ($place['label']) {
                case 'Place of Birth':
                case 'Place of Death':
                    $group = 'birthDeath';
                    $value = [
                        'icon' => 'Place of Death' == $place['label'] ? 'blackIcon' : 'violetIcon',
                        'html' => sprintf(
                            '<b>%s</b>: <a href="%s">%s</a>',
                            $place['label'],
                            htmlspecialchars($this->generateUrl('place-by-tgn', [
                                'tgn' => $place['info']['tgn'],
                            ])),
                            htmlspecialchars($place['info']['name'], ENT_QUOTES)
                        ),
                    ];
                    break;

                case 'Place of Activity':
                    $group = 'birthDeath';
                    $value = [
                        'icon' => 'yellowIcon',
                        'html' => sprintf(
                            '<b>%s</b>: %s<a href="%s">%s</a>',
                            $place['label'],
                            !empty($place['info']['address']['address'])
                                            ? htmlspecialchars($place['info']['address']['address'], ENT_QUOTES) . ', '
                                            : '',
                            htmlspecialchars($this->generateUrl('place-by-tgn', [
                                'tgn' => $place['info']['tgn'],
                            ])),
                            htmlspecialchars($place['info']['name'], ENT_QUOTES)
                        ),
                    ];
                    break;
            }

            if (is_null($value)) {
                continue;
            }

            if (!array_key_exists($geo = $place['info']['geo'], $markers)) {
                $markers[$geo] = [
                    'place' => $place['info'],
                    'groupedEntries' => [],
                ];
            }

            if (!array_key_exists($group, $markers[$geo]['groupedEntries'])) {
                $markers[$geo]['groupedEntries'][$group] = [];
            }

            $markers[$geo]['groupedEntries'][$group][] = $value;
        }

        return $markers;
    }

    #[Route(path: '/organization/gnd/beacon', name: 'organization-gnd-beacon')]
    public function gndBeaconAction(EntityManagerInterface $entityManager, TranslatorInterface $translator, \Twig\Environment $twig)
    {
        $ret = '#FORMAT: BEACON' . "\n"
             . '#PREFIX: http://d-nb.info/gnd/'
             . "\n";
        $ret .= sprintf(
            '#TARGET: %s/gnd/{ID}',
            $this->generateUrl('organization-index', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)
        )
              . "\n";

        $globals = $twig->getGlobals();

        $ret .= '#NAME: ' . /** @ignore */$translator->trans($globals['site_name'], [], 'additional')
              . "\n";
        // $ret .= '#MESSAGE: ' . "\n";

        $repo = $entityManager
                ->getRepository('App\Entity\Organization');

        $query = $repo
                ->createQueryBuilder('O')
                ->where('O.status >= 0')
                ->andWhere('O.gnd IS NOT NULL')
                ->orderBy('O.gnd')
                ->getQuery()
        ;

        foreach ($query->execute() as $actor) {
            $ret .=  $actor->getGnd() . "\n";
        }

        return new Response($ret, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
