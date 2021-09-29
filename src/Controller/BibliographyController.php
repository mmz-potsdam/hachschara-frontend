<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 *
 */
class BibliographyController
extends AbstractController
{
    private function instantiateCiteProc($locale)
    {
        $projectRoot = $this->getParameter('kernel.project_dir');

        $path = $projectRoot . '/data/csl/infoclio-de.csl.xml';

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

        return new \Seboettg\CiteProc\CiteProc(file_get_contents($path), $locale, $additionalMarkup);
    }

    /**
     * @Route("/bibliography", name="bibliography-index")
     */
    public function indexAction(Request $request,
                                TranslatorInterface $translator)
    {
        $qb = $this->getDoctrine()
                ->getManager()
                ->createQueryBuilder();

        $qb->select([ 'B'])
            ->from('\App\Entity\Bibitem', 'B')
            ->where('B.status IN (0,1)')
            ;
        $query = $qb->getQuery();

        $data = [];
        foreach ($query->getResult() as $item) {
            $data[] = $item->jsonSerialize();
        }

        $citeProc = $this->instantiateCiteProc($request->getLocale());

        $dataAsObject = json_decode(json_encode($data));
        $bibliographyHtml = @$citeProc->render($dataAsObject);

        /* vertical-align: super doesn't render nicely:
           http://stackoverflow.com/a/1530819/2114681
        */
        $bibliographyHtml = preg_replace('/style="([^"]*)vertical\-align\:\s*super;([^"]*)"/',
                            'style="\1vertical-align: top; font-size: 66%;\2"', $bibliographyHtml);

        return $this->render('Bibliography/index.html.twig', [
            'pageTitle' => $translator->trans('Literature'),
            'bibliography' => $bibliographyHtml,
        ]);
    }
}
