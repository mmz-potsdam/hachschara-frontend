<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;

use Knp\Component\Pager\PaginatorInterface;

/**
 *
 */
class BaseController
extends AbstractController
{
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
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

        return new \Seboettg\CiteProc\CiteProc(file_get_contents($path), $locale);
    }
}
