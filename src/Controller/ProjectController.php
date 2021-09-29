<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use Knp\Component\Pager\PaginatorInterface;

use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

/**
 *
 */
class ProjectController
extends BaseController
{
    protected $pageSize = 200;

    /**
     * @Route("/sites/map", name="project-map")
     * @Route("/sites", name="project-index")
     */
    public function indexAction(Request $request,
                                PaginatorInterface $paginator,
                                TranslatorInterface $translator,
                                FilterBuilderUpdaterInterface $queryBuilderUpdater)
    {
        $routeName = $request->get('_route');

        $qb = $this->getDoctrine()
                ->getManager()
                ->createQueryBuilder();

        $qb->select([
                'PR',
                "PR.name HIDDEN nameSort"
            ])
            ->from('App:Project', 'PR')
            ->leftJoin('PR.location', 'P')
            ->leftJoin('P.country', 'C')
            ->where("PR.status IN (0, 1)")
            ->orderBy('nameSort')
            ;

        $form = $this->createForm(\App\Filter\ProjectFilterType::class, [
            // 'choices' => array_flip($this->buildCountries()),
        ]);

        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));

            // build the query from the given form object
            $queryBuilderUpdater->addFilterConditions($form, $qb);
        }

        if ('project-map' == $routeName) {
            $data = [];
            foreach ($qb->getQuery()->getResult() as $result) {
                $geo = $result->getGeo();
                if (!empty($geo)) {
                    $parts = explode(',', $geo, 2);
                    $info = [ (double)$parts[0], (double)$parts[1] ];
                    $info[] = sprintf('<a href="%s">%s</a>',
                                      $this->generateUrl('project', [ 'id' => $result->getId() ]),
                                      $result->getName());
                    $info[] = '';
                    $data[] = $info;
                }
            }
            // dd($data);

            return $this->render('Project/map.html.twig', [
                'pageTitle' => $translator->trans('Sites'),
                // 'pagination' => $pagination,
                'form' => $form->createView(),
                'bounds' => [],
                'disableClusteringAtZoom' => '',
                'data' => $data,
            ]);
        }

        $pagination = $this->buildPagination($request, $paginator, $qb->getQuery(), [
            // the following leads to wrong display in combination with our
            // helper.pagination_sortable()
            // 'defaultSortFieldName' => 'nameSort', 'defaultSortDirection' => 'asc',
        ]);

        return $this->render('Project/index.html.twig', [
            'pageTitle' => $translator->trans('Sites'),
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/sites/{id}.jsonld", name="project-jsonld", requirements={"id"="\d+"})
     * @Route("/sites/{id}", name="project", requirements={"id"="\d+"})
     */
    public function detailAction(Request $request, $id = null, $ulan = null, $gnd = null)
    {
        $criteria = new \Doctrine\Common\Collections\Criteria();
        $projectRepo = $this->getDoctrine()
                ->getRepository('App:Project');

        if (!empty($id)) {
            $criteria->where($criteria->expr()->eq('id', $id));
        }
        else if (!empty($ulan)) {
            $criteria->where($criteria->expr()->eq('ulan', $ulan));
        }
        else if (!empty($gnd)) {
            $criteria->where($criteria->expr()->eq('gnd', $gnd));
        }

        $criteria->andWhere($criteria->expr()->neq('status', -1));

        $projects = $projectRepo->matching($criteria);

        if (0 == count($projects)) {
            return $this->redirectToRoute('project-index');
        }

        $project = $projects[0];
        // $project->setDateModified(\App\Search\PersonListBuilder::fetchDateModified($this->getDoctrine()->getConnection(), $project->getId()));

        $locale = $request->getLocale();
        if (in_array($request->get('_route'), [ 'project-jsonld' ])) {
            return new JsonLdResponse($project->jsonLdSerialize($locale));
        }

        $citeProc = $this->instantiateCiteProc($request->getLocale());
        if ($project->hasInfo()) {
            // expand the publications
            $project->buildInfoFull($this->getDoctrine()->getManager(), $citeProc);
        }

        $routeName = 'project';
        $routeParams = [ 'id' => $project->getId() ];

        return $this->render('Project/detail.html.twig', [
            'pageTitle' => $project->getName(), // TODO: lifespan in brackets
            'project' => $project,
            'mapMarkers' => $this->buildMapMarkers($project),
            'pageMeta' => [
                'jsonLd' => $project->jsonLdSerialize($locale),
                // 'og' => $this->buildOg($person, $routeName, $routeParams),
                // 'twitter' => $this->buildTwitter($person, $routeName, $routeParams),
            ],
        ]);
    }

    protected function buildMapMarkers($entity)
    {
        $markers = [];

        $places = [];

        $location = $entity->getLocationInfo();
        if (!empty($foundingLocation) && !empty($foundingLocation['geo'])) {
            $places[] = [
                'info' => $foundingLocation,
                'label' => 'Location',
            ];
        }

        foreach ($places as $place) {
            $value = $group = null;
            switch ($place['label']) {
                default:
                    $group = 'birthDeath';
                    $value = [
                        'icon' => 'Place of Death' == $place['label'] ? 'blackIcon' : 'violetIcon',
                        'html' => sprintf('<b>%s</b>: <a href="%s">%s</a>',
                                          $place['label'],
                                          htmlspecialchars($this->generateUrl('place-by-tgn', [
                                               'tgn' => $place['info']['tgn'],
                                          ])),
                                          htmlspecialchars($place['info']['name'], ENT_QUOTES))
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
}
