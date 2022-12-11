<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class BibliographyController
extends BaseController
{
    /**
     * @Route("/bibliography", name="bibliography-index")
     */
    public function indexAction(Request $request,
                                EntityManagerInterface $entityManager,
                                TranslatorInterface $translator)
    {
        /*
        $qb = $entityManager
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
        */

        /* vertical-align: super doesn't render nicely:
           http://stackoverflow.com/a/1530819/2114681
        */
        /*
        $bibliographyHtml = preg_replace('/style="([^"]*)vertical\-align\:\s*super;([^"]*)"/',
                            'style="\1vertical-align: top; font-size: 66%;\2"', $bibliographyHtml);
        */

        $contents = $this->renderView('Bibliography/index.html.twig', [
            'pageTitle' => $translator->trans('Literature'),
            // 'bibliography' => $bibliographyHtml,
        ]);

        if ('de' != $request->getLocale()) {
            // hack for pages
            $contents = preg_replace('/S\.\s*/', 'pp. ', $contents);
        }

        return new Response($contents);
    }
}
