<?php

// src/Controller/PersonController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
class PersonController extends BaseController
{
    protected $pageSize = 500;

    #[Route(path: '/person', name: 'person-index')]
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        TranslatorInterface $translator
    ) {
        $qb = $entityManager
            ->createQueryBuilder();

        $qb->select([
            'P',
            "CONCAT(COALESCE(P.familyName,P.givenName), ' ', COALESCE(P.givenName, '')) HIDDEN nameSort",
        ])
            ->distinct()
            ->from('\App\Entity\Person', 'P')
            ->innerJoin('P.siteReferences', 'SR')
            ->innerJoin('SR.site', 'PR')
            ->where('P.status IN (0,1)')
            ->andWhere('PR.status IN (1)')
            ->orderBy('nameSort')
        ;

        $pagination = $this->buildPagination($request, $paginator, $qb->getQuery(), [
            // the following leads to wrong display in combination with our
            // helper.pagination_sortable()
            // 'defaultSortFieldName' => 'nameSort', 'defaultSortDirection' => 'asc',
        ]);

        return $this->render('Person/index.html.twig', [
            'pageTitle' => $translator->trans('Persons'),
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/person/gnd/{gnd}.jsonld', requirements: ['gnd' => '[0-9xX]+'], name: 'person-by-gnd-jsonld')]
    #[Route(path: '/person/gnd/{gnd}', requirements: ['gnd' => '[0-9xX]+'], name: 'person-by-gnd')]
    #[Route(path: '/person/{id}.jsonld', name: 'person-jsonld', requirements: ['id' => '\d+'])]
    #[Route(path: '/person/{id}', name: 'person', requirements: ['id' => '\d+'])]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        $id = null,
        $gnd = null
    ) {
        $criteria = \Doctrine\Common\Collections\Criteria::create(true);

        if (!empty($id)) {
            $criteria->where($criteria->expr()->eq('id', $id));
        }
        else if (!empty($gnd)) {
            $criteria->where($criteria->expr()->eq('gnd', $gnd));
        }
        else {
            return $this->redirectToRoute('person-index');
        }

        $criteria->andWhere($criteria->expr()->neq('status', -1));

        $personRepo = $entityManager
                ->getRepository('App\Entity\Person');
        $persons = $personRepo->matching($criteria);

        if (0 == count($persons)) {
            return $this->redirectToRoute('person-index');
        }

        $person = $persons[0];
        // $person->setDateModified(\App\Search\PersonListBuilder::fetchDateModified($entityManager->getConnection(), $person->getId()));

        $locale = $request->getLocale();
        if (in_array($request->get('_route'), [ 'person-jsonld', 'person-by-gnd-jsonld' ])) {
            return new JsonLdResponse($person->jsonLdSerialize($locale));
        }

        $routeName = 'person';
        $routeParams = [ 'id' => $person->getId() ];
        if (!empty($person->getGnd())) {
            $routeName = 'person-by-gnd';
            $routeParams = [ 'gnd' => $person->getGnd() ];
        }

        if ($person->hasInfo()) {
            $citeProc = $this->instantiateCiteProc($locale);
            // expand the publications
            $person->buildInfoFull($entityManager, $citeProc);
        }

        return $this->render('Person/detail.html.twig', [
            'pageTitle' => $person->getFullname(true), // TODO: lifespan in brackets
            'person' => $person,
            'mapMarkers' => $this->buildMapMarkers($entityManager, $person),
            'pageMeta' => [
                'jsonLd' => $person->jsonLdSerialize($locale),
                // 'og' => $this->buildOg($person, $routeName, $routeParams),
                // 'twitter' => $this->buildTwitter($person, $routeName, $routeParams),
            ],
        ]);
    }

    protected function buildMapMarkers(EntityManagerInterface $entityManager, $person)
    {
        $markers = [];

        $places = [];

        $birthPlace = $person->getBirthPlaceInfo();
        if (!empty($birthPlace) && !empty($birthPlace['geo'])) {
            $places[] = [
                'info' => $birthPlace,
                'label' => 'Place of Birth',
            ];
        }

        $deathPlace = $person->getDeathPlaceInfo();
        if (!empty($deathPlace) && !empty($deathPlace['geo'])) {
            $places[] = [
                'info' => $deathPlace,
                'label' => 'Place of Death',
            ];
        }

        // places of activity
        $addresses = $person->getAddressesSeparated(null, false, true);
        // we currently have geo, so get all tgn
        $tgns = array_filter(array_unique(array_column($addresses, 'place_tgn')));
        $placesByTgn = [];
        if (!empty($tgns)) {
            foreach ($this->hydratePlacesByTgns($entityManager, $tgns, true) as $place) {
                if (!empty($place->getTgn())) {
                    $placesByTgn[$place->getTgn()] = $place;
                }
            }
        }

        foreach ($addresses as $address) {
            $tgn = $address['place_tgn'];
            if (!empty($tgn) && array_key_exists($tgn, $placesByTgn)) {
                $place = $placesByTgn[$tgn];
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
                            '<b>%s</b>: %s',
                            $place['label'],
                            /*
                            htmlspecialchars($this->generateUrl('place-by-tgn', [
                                 'tgn' => $place['info']['tgn'],
                            ])),
                            */
                            htmlspecialchars($place['info']['name'], ENT_QUOTES)
                        ),
                    ];
                    break;

                case 'Place of Activity':
                    $group = 'birthDeath';
                    $value = [
                        'icon' => 'yellowIcon',
                        'html' => sprintf(
                            '<b>%s</b>: %s%s',
                            $place['label'],
                            !empty($place['info']['address']['address'])
                                            ? htmlspecialchars($place['info']['address']['address'], ENT_QUOTES) . ', '
                                            : '',
                            /*
                            htmlspecialchars($this->generateUrl('place-by-tgn', [
                                 'tgn' => $place['info']['tgn'],
                            ])),
                            */
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

    #[Route(path: '/person/gnd/beacon', name: 'person-gnd-beacon')]
    public function gndBeaconAction(EntityManagerInterface $entityManager, TranslatorInterface $translator, \Twig\Environment $twig)
    {
        $ret = '#FORMAT: BEACON' . "\n"
             . '#PREFIX: http://d-nb.info/gnd/'
             . "\n";
        $ret .= sprintf(
            '#TARGET: %s/gnd/{ID}',
            $this->generateUrl('person-index', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)
        )
              . "\n";

        $globals = $twig->getGlobals();

        $ret .= '#NAME: ' . /** @ignore */$translator->trans($globals['site_name'], [], 'additional')
              . "\n";
        // $ret .= '#MESSAGE: ' . "\n";

        $repo = $entityManager
                ->getRepository('App\Entity\Person');

        $query = $repo
                ->createQueryBuilder('P')
                ->where('P.status >= 0')
                ->andWhere('P.gnd IS NOT NULL')
                ->orderBy('P.gnd')
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
