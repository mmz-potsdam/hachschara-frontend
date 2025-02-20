<?php

// src/Controller/SiteController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;

/**
 *
 */
class SiteController extends BaseController
{
    protected $pageSize = 200;

    #[Route(path: '/site/map', name: 'site-map')]
    #[Route(path: '/site', name: 'site-index')]
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        TranslatorInterface $translator,
        FilterBuilderUpdaterInterface $queryBuilderUpdater
    ) {
        $routeName = $request->get('_route');
        $locale = $request->getLocale();

        $qb = $entityManager
                ->createQueryBuilder();

        $nameSort = 'PR.name';
        if ($locale != \App\Entity\Site::$defaultLocale) {
            $nameSort = sprintf(
                "CONCAT_WS('', JSON_UNQUOTE(JSON_EXTRACT(PR.translations ,'$.%s.name')), %s)",
                $locale,
                $nameSort
            );
        }

        $statusCondition = 'site-map' == $routeName
            ? 'PR.status <> -1'
            : 'PR.status IN (1)'
        ;

        $qb->select([
            'PR',
            $nameSort . ' HIDDEN nameSort',
        ])
            ->from('App\Entity\Site', 'PR')
            ->leftJoin('PR.location', 'P')
            ->leftJoin('P.country', 'C')
            ->where($statusCondition)
            ->orderBy('nameSort')
        ;

        $form = $this->createForm(\App\Filter\SiteFilterType::class, [
            // 'choices' => array_flip($this->buildCountries($entityManager)),
        ]);

        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->all()[$form->getName()]);

            // build the query from the given form object
            $queryBuilderUpdater->addFilterConditions($form, $qb);
        }

        if ('site-map' == $routeName) {
            $data = [];
            foreach ($qb->getQuery()->getResult() as $result) {
                $geo = $result->getGeo();
                if (!empty($geo)) {
                    $name = htmlspecialchars($result->getName($locale), ENT_COMPAT, 'utf-8');

                    $parts = explode(',', $geo, 2);
                    $info = [ (float) $parts[0], (float) $parts[1] ];

                    if ($result->getStatus() == 1) {
                        $info[] = sprintf(
                            '<a href="%s">%s</a>',
                            $this->generateUrl('site', [ 'id' => $result->getId() ]),
                            $name
                        );
                    }
                    else {
                        $info[] = $name;
                    }

                    $abstractLocalized = null;
                    switch ($result->getStatus()) {
                        case 1:
                            $linked = 1;
                            break;

                        default:
                            $abstractLocalized = $result->getAbstractLocalized($locale);
                            $linked = !empty($abstractLocalized) ? 0 : -1;
                    }

                    $info[] = $linked;

                    $info[] = 0 === $linked
                        ? htmlspecialchars($abstractLocalized, ENT_COMPAT, 'utf-8')
                        : null;

                    $data[] = $info;
                }
            }

            return $this->render('Site/map.html.twig', [
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

        return $this->render('Site/index.html.twig', [
            'pageTitle' => $translator->trans('Sites'),
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/site/{id}.jsonld', name: 'site-jsonld', requirements: ['id' => '\d+'])]
    #[Route(path: '/site/{id}.pdf', name: 'site-pdf', requirements: ['id' => '\d+'])]
    #[Route(path: '/site/{id}', name: 'site', requirements: ['id' => '\d+'])]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        \App\Utils\MpdfConverter $pdfConverter,
        $id
    ) {
        $repo = $entityManager
                ->getRepository('App\Entity\Site');

        $criteria = new \Doctrine\Common\Collections\Criteria();
        $criteria->where($criteria->expr()->eq('id', $id));
        $criteria->andWhere($criteria->expr()->neq('status', -1));

        $entities = $repo->matching($criteria);

        if (0 == count($entities)) {
            return $this->redirectToRoute('site-index');
        }

        \App\Entity\Site::initTerms($entityManager);

        $entity = $entities[0];

        $routeName = $request->get('_route');
        $routeParams = [ 'id' => $entity->getId() ];

        $locale = $request->getLocale();

        if ($entity->hasInfo()) {
            $citeProc = $this->instantiateCiteProc($locale);
            // expand the publications
            $entity->buildInfoFull($entityManager, $citeProc);
        }

        if ($entity->hasContributor()) {
            $entity->buildContributorFull($entityManager);
        }

        if (in_array($routeName, [ 'site-jsonld' ])) {
            return new JsonLdResponse($entity->jsonLdSerialize($locale));
        }

        if (in_array($routeName, [ 'site-pdf' ])) {
            $html = $this->renderView('Site/detail.html.twig', [
                'pageTitle' => $entity->getName($locale),
                'site' => $entity,
                'mapMarkers' => $this->buildMapMarkers($entity, $locale),
                'printview' => true,
                'pageMeta' => [
                    'jsonLd' => $entity->jsonLdSerialize($locale),
                    // 'og' => $this->buildOg($entity, $routeName, $routeParams),
                    // 'twitter' => $this->buildTwitter($entity, $routeName, $routeParams),
                ],
            ]);

            $htmlDoc = new \App\Utils\HtmlDocument();
            $htmlDoc->loadString($html);

            $pdfDoc = $pdfConverter->convert($htmlDoc);

            return new Response((string) $pdfDoc, Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        return $this->render('Site/detail.html.twig', [
            'pageTitle' => $entity->getName($locale),
            'site' => $entity,
            'mapMarkers' => $this->buildMapMarkers($entity, $locale),
            'pageMeta' => [
                'jsonLd' => $entity->jsonLdSerialize($locale),
                // 'og' => $this->buildOg($entity, $routeName, $routeParams),
                // 'twitter' => $this->buildTwitter($entity, $routeName, $routeParams),
            ],
        ]);
    }

    /**
     * $entity->getGeo() is used directly to place the marker
     * So this is currently an empty stub
     */
    protected function buildMapMarkers($entity, $locale = null)
    {
        $markers = [];

        return $markers;
    }
}
