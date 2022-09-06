<?php

/**
 *
 * Shared methods
 */

namespace App\Entity;

use Doctrine\ORM\EntityManagerInterface;

trait ContributorTrait
{
    /**
     * @var array
     *
     * @ORM\Column(name="contributor", type="json", length=65535, nullable=true)
     */
    private $contributor;

    /*
     * Expanded $contributor
     */
    protected $contributorExpanded = [];

    public function hasContributor()
    {
        return !empty($this->contributor);
    }

    public function buildContributorFull(EntityManagerInterface $em)
    {
        // lookup users
        $usersById = [];
        foreach ($this->contributor as $key => $entry) {
            if (!empty($entry['id_user'])) {
                $usersById[$entry['id_user']] = null;
            }
        }

        if (!empty($usersById)) {
            $qb = $em->createQueryBuilder();

            $qb->select([ 'U' ])
                ->from('App\Entity\User', 'U')
                ->andWhere('U.id IN (:ids) AND U.status <> -1')
                ->setParameter('ids', array_keys($usersById))
                ;

            $results = $qb->getQuery()
                ->getResult();

            foreach ($results as $user) {
                $usersById[$user->getId()] = $user;
            }
        }

        $this->contributorExpanded = [];
        foreach ($this->contributor as $key => $entry) {
            if (!empty($entry['id_user']) && !is_null($usersById[$entry['id_user']])) {
                $this->contributor[$key]['user'] = $usersById[$entry['id_user']];
                $this->contributorExpanded[] = $this->contributor[$key];
            }
        }
    }

    private function getContributorsByRole($role = 0)
    {
        $ret = [];

        if (empty($this->contributorExpanded)) {
            return $ret;
        }

        foreach ($this->contributorExpanded as $contributor) {
            if (is_null($contributor['user'])) {
                continue;
            }

            switch ($role) {
                case 0:
                    if (!array_key_exists('role', $contributor)) {
                        $ret[] = $contributor['user'];
                    }
                    else if ($contributor['role'] == $role) {
                        $ret[] = $contributor['user'];
                    }
                    break;

                default:
                    if (!array_key_exists('role', $contributor)) {
                        ; // ignore
                    }
                    else if ($contributor['role'] == $role) {
                        $ret[] = $contributor['user'];
                    }
            }
        }

        return $ret;
    }

    public function getAuthors()
    {
        return $this->getContributorsByRole(0);
    }
}
