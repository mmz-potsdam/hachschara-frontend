<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;

use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
class BaseController
extends AbstractController
{
    protected $kernel;
    protected $doctrine;

    public function __construct(KernelInterface $kernel,
                                ManagerRegistry $doctrine)
    {
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    protected function expandCountryCode($countryCode, $labelUnknown = '[unknown]')
    {
        if (empty($countryCode)) {
            return $labelUnknown;
        }

        return Countries::getName($countryCode);
    }

    protected function buildPagination(Request $request,
                                       PaginatorInterface $paginator,
                                       $query, $options = [])
    {
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

    protected function instantiateCiteProc($locale)
    {
        $path = $this->kernel->getProjectDir() . '/data/csl/infoclio-de.csl.xml';

        $wrapSpan = function ($renderedText, $class) {
            return '<span class="citeproc-'. $class . '">' . $renderedText . '</span>';
        };

        $additionalMarkup = [];
        foreach ([
                'creator' => 'creator',
                'title' => 'title',
                'in' => 'in',
                'volumes' => 'volumes',
                'book-series' => 'book-series',
                'place' => 'place',
                'date' => 'data',
                'URL' => 'URL',
                'DOI' => 'DOI',
            ] as $key => $class)
        {
            $additionalMarkup[$key] = function($cslItem, $renderedText) use ($wrapSpan, $class) {
                return $wrapSpan($renderedText, $class);
            };
        }

        return new \Seboettg\CiteProc\CiteProc(file_get_contents($path), $locale);
    }
}
