<?php

// src/Controller/BaseController.php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;

/**
 * Common Base
 */
abstract class BaseController extends AbstractController
{
    protected $kernel;
    protected $twigEnvironment;
    protected $globals = null;
    protected $pageSize = 50;

    public function __construct(
        KernelInterface $kernel,
        \Twig\Environment $twigEnvironment
    ) {
        $this->kernel = $kernel;
        $this->twigEnvironment = $twigEnvironment;
    }

    protected function expandCountryCode($countryCode, $labelUnknown = '[unknown]')
    {
        if (empty($countryCode)) {
            return $labelUnknown;
        }

        return Countries::getName($countryCode);
    }

    protected function getDataDir()
    {
        return $this->kernel->getProjectDir() . '/data';
    }

    protected function getGlobal($key)
    {
        if (is_null($this->globals)) {
            $this->globals = $this->twigEnvironment->getGlobals();
        }

        return array_key_exists($key, $this->globals)
            ? $this->globals[$key] : null;
    }

    protected function buildPagination(
        Request $request,
        PaginatorInterface $paginator,
        $query,
        $options = []
    ) {
        $limit = $this->pageSize;
        if (array_key_exists('pageSize', $options)) {
            $limit = $options['pageSize'];
        }

        return $paginator->paginate(
            $query, // query, NOT result
            $request->query->getInt('page', 1), // page number
            $limit, // limit per page
            $options
        );
    }

    protected function hydratePlacesByTgns(EntityManagerInterface $entityManager, array $tgns, bool $preserveOrder = false)
    {
        // hydrate with doctrine entity
        $qb = $entityManager
            ->createQueryBuilder();
        $hydrationQuery = $qb->select([ 'PL', 'field(PL.tgn, :tgns) as HIDDEN field', 'COALESCE(PL.alternateName, PL.name) HIDDEN nameSort' ])
            ->from('AppBundle\Entity\Place', 'PL')
            ->where('PL.tgn IN (:tgns)')
            ->orderBy($preserveOrder ? 'field' : 'nameSort')
            ->getQuery();
        ;

        $hydrationQuery->setParameter('tgns', $tgns);

        return $hydrationQuery->getResult();
    }

    protected function hydratePlacesByIds(EntityManagerInterface $entityManager, array $ids, bool $preserveOrder = false)
    {
        // hydrate with doctrine entity
        $qb = $entityManager
            ->createQueryBuilder();
        $hydrationQuery = $qb->select([ 'PL', 'field(PL.id, :ids) as HIDDEN field', 'COALESCE(PL.alternateName, PL.name) HIDDEN nameSort' ])
            ->from('AppBundle\Entity\Place', 'PL')
            ->where('PL.id IN (:ids)')
            ->orderBy($preserveOrder ? 'field' : 'nameSort')
            ->getQuery();
        ;

        $hydrationQuery->setParameter('ids', $ids);

        return $hydrationQuery->getResult();
    }

    protected function instantiateCiteProc($cslLocale)
    {
        $cslPath = $this->getDataDir() . '/csl/infoclio-de.csl.xml';

        $wrapSpan = function ($renderedText, $class) {
            return '<span class="citeproc-' . $class . '">' . $renderedText . '</span>';
        };

        $additionalMarkup = [
            'URL' => function ($cslItem, $renderedText) {
                return sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $renderedText,
                    $renderedText
                );
            },
            'DOI' => function ($cslItem, $renderedText) {
                return sprintf(
                    '<a href="https://doi.org/%s" target="_blank">doi:%s</a>',
                    $renderedText,
                    $renderedText
                );
            },
        ];

        foreach ([
            'author' => 'creator',
            'editor' => 'creator',
            'title' => 'title',
            'in' => 'in',
            'volumes' => 'volumes',
            'book-series' => 'book-series',
            'place' => 'place',
            'date' => 'data',
        ] as $key => $class) {
            $additionalMarkup[$key] = function ($cslItem, $renderedText) use ($wrapSpan, $class) {
                return $wrapSpan($renderedText, $class);
            };
        }

        return new \App\Utils\CiteProc(file_get_contents($cslPath), $cslLocale, $additionalMarkup);
    }
}
