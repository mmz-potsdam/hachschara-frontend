<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * DefaultController for home- and about-pages
 */
class DefaultController
extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function homeAction()
    {
        return $this->render('Default/home.html.twig');
    }

    /**
     * @Route("/about", name="about")
     */
    public function aboutAction(Request $request)
    {
        return $this->render('Default/about.' . $request->getLocale() . '.html.twig');
    }

    /**
     * @Route("/about/hakhshara", name="about-hakhshara")
     */
    public function aboutTermAction(Request $request)
    {
        return $this->render('Default/about-hakhshara.' .  $request->getLocale() .  '.html.twig');
    }

    /**
     * @Route("/about/network ", name="about-network")
     */
    public function aboutNetworkAction(Request $request, EntityManagerInterface $entityManager)
    {
        $qb = $entityManager
                ->createQueryBuilder();

        $qb->select([
                "JSON_EXTRACT(PR.contributor, '$[*].id_user') AS ids"
            ])
            ->distinct()
            ->from('App\Entity\Site', 'PR')
            ->where("PR.status IN (1)")
            ;

        $userIds = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            $decoded = json_decode($result['ids'], true);
            if (!empty($decoded)) {
                $userIds = array_unique(array_merge($userIds, $decoded));
            }
        }

        $users = [];
        if (!empty($userIds)) {
            $qb->select([
                    'U',
                    "CONCAT(COALESCE(U.familyName,U.givenName), ' ', COALESCE(U.givenName, '')) HIDDEN nameSort",
                ])
                ->from('App\Entity\User', 'U')
                ->andWhere('U.id IN (:ids) AND U.status <> -1')
                ->setParameter('ids', $userIds)
                ->orderBy('nameSort')
                ;

            $users = $qb->getQuery()->getResult();
        }

        return $this->render('Default/about-network.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/imprint", name="imprint")
     */
    public function imprintAction(Request $request)
    {
        return $this->render('Default/imprint.' . $request->getLocale() . '.html.twig');
    }
}
