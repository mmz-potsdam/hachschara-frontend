<?php
// src/Controller/GlossaryController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class GlossaryController
extends BaseController
{
    /**
     * @Route("/glossary", name="glossary-index")
     */
    public function indexAction(Request $request,
                                EntityManagerInterface $entityManager,
                                TranslatorInterface $translator)
    {
        $qb = $entityManager
                ->createQueryBuilder();

        if ('de' == $request->getLocale()) {
            $sortExpression = 'T.name';
        }
        else {
            $sortExpression = sprintf("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(T.title, '$.%s')), T.name)",
                                      $request->getLocale());
        }

        $qb->select([
                'T',
                $sortExpression . " HIDDEN nameSort",

            ])
            ->from('\App\Entity\Term', 'T')
            ->where("T.category='glossary' AND T.status IN (0,1)")
            ->orderBy('nameSort')
            ;

        $query = $qb->getQuery();

        return $this->render('Glossary/index.html.twig', [
            'pageTitle' => $translator->trans('Glossary'),
            'terms' => $query->getResult(),
        ]);
    }
}
