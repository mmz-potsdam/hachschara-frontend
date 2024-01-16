<?php
// src/Controller/BibliographyController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 */
class BibliographyController
extends BaseController
{
    #[Route(path: '/bibliography', name: 'bibliography-index')]
    public function indexAction(Request $request,
                                TranslatorInterface $translator,
                                array $zoteroCollections)
    {
        $bibliographyBySection = [];

        foreach ($zoteroCollections as $basename => $descr) {
            $fnameFull =  $this->getDataDir() . '/csl/' . $basename . '.json';
            $dataAsObject = json_decode(file_get_contents($fnameFull));

            $citeProc = $this->instantiateCiteProc($request->getLocale());
            $bibliographyHtml = @$citeProc->render($dataAsObject->data);

            /* vertical-align: super doesn't render nicely:
               http://stackoverflow.com/a/1530819/2114681
            */
            /*
            $bibliographyHtml = preg_replace('/style="([^"]*)vertical\-align\:\s*super;([^"]*)"/',
                                'style="\1vertical-align: top; font-size: 66%;\2"', $bibliographyHtml);
            */
            /*
            if ('de' != $request->getLocale()) {
                // hack for pages
                $contents = preg_replace('/S\.\s*' . '/', 'pp. ', $contents);
            }
            */

            $bibliographyBySection[$basename] = $descr + [ 'contentHtml' => $bibliographyHtml ];
        }

        return $this->render('Bibliography/index.html.twig', [
            'pageTitle' => $translator->trans('Literature'),
            'bibliographyBySection' => $bibliographyBySection,
        ]);
    }
}
