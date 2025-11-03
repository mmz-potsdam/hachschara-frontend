<?php

// src/Controller/UserController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class UserController extends BaseController
{
    #[Route(path: '/about/network/gnd/{gnd}.jsonld', requirements: ['gnd' => '[0-9xX]+'], name: 'user-by-gnd-jsonld')]
    #[Route(path: '/about/network/gnd/{gnd}', requirements: ['gnd' => '[0-9xX]+'], name: 'user-by-gnd')]
    #[Route(path: '/about/network/{id}.jsonld', name: 'user-jsonld', requirements: ['id' => '\d+'])]
    #[Route(path: '/about/network/{id}', name: 'user', requirements: ['id' => '\d+'])]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        $id = null,
        $gnd = null
    ) {
        $criteria = \Doctrine\Common\Collections\Criteria::create(true);

        if (!empty($id)) {
            $criteria->where($criteria->expr()->eq('id', $id));
        }
        else if (!empty($gnd)) {
            $criteria->where($criteria->expr()->eq('gnd', $gnd));
        }
        else {
            return $this->redirectToRoute('about-network');
        }

        $criteria->andWhere($criteria->expr()->neq('status', -1));

        $userRepo = $entityManager
                ->getRepository('App\Entity\User');
        $users = $userRepo->matching($criteria);

        if (0 == count($users)) {
            return $this->redirectToRoute('about-network');
        }

        $user = $users[0];
        // $user->setDateModified(\App\Search\UserListBuilder::fetchDateModified($entityManager->getConnection(), $user->getId()));

        $locale = $request->getLocale();
        if (in_array($request->get('_route'), [ 'user-jsonld', 'user-by-gnd-jsonld' ])) {
            return new JsonLdResponse($user->jsonLdSerialize($locale));
        }

        $routeName = 'user';
        $routeParams = [ 'id' => $user->getId() ];
        if (!empty($user->getGnd())) {
            $routeName = 'user-by-gnd';
            $routeParams = [ 'gnd' => $user->getGnd() ];
        }

        // find related sites
        $qb = $entityManager
                ->createQueryBuilder();

        $json_contains = sprintf(
            "JSON_CONTAINS(PR.contributor, '%s') = 1",
            json_encode([ 'id_user' => (string) $user->getId() ])
        );

        $qb->select([
            'PR',
            "PR.name HIDDEN nameSort",
        ])
            ->from('App\Entity\Site', 'PR')
            ->leftJoin('PR.location', 'P')
            ->leftJoin('P.country', 'C')
            ->where("PR.status IN (1) AND " . $json_contains)
            ->orderBy('nameSort')
        ;
        $sites = $qb->getQuery()->getResult();

        return $this->render('User/detail.html.twig', [
            'pageTitle' => $user->getFullname(true), // TODO: lifespan in brackets
            'person' => $user,
            'sites' => $sites,
            'pageMeta' => [
                'jsonLd' => $user->jsonLdSerialize($locale),
                // 'og' => $this->buildOg($user, $routeName, $routeParams),
                // 'twitter' => $this->buildTwitter($user, $routeName, $routeParams),
            ],
        ]);
    }
}
