<?php

/**
 *
 * Shared methods
 */

namespace App\Entity;

use Doctrine\ORM\EntityManagerInterface;

trait InfoTrait
{
    /*
     * Expanded $info
     */
    protected $infoExpanded = [];

    public function hasInfo($category = null)
    {
        if (!empty($this->extractFromNotes)) {
            foreach ($this->extractFromNotes as $key) {
                if (array_key_exists($key, $this->notes) && !empty($this->notes[$key])) {
                    $this->info[$key] = [];
                    foreach ($this->notes[$key] as $entry) {
                        $this->info[$key][] = $entry;
                    }
                }
            }
        }

        if (!is_null($category)) {
            return array_key_exists($category, $this->info)
                && !empty($this->info[$category]);
        }

        return !empty($this->info);
    }

    public function buildInfoFull(EntityManagerInterface $em, $citeProc)
    {
        // lookup publications
        $publicationsById = [];
        $journalsById = [];
        foreach ($this->info as $key => $entries) {
            foreach ($entries as $entry) {
                if (!empty($entry['id_publication'])) {
                    if (preg_match('/^journal:(\d+)$/', $entry['id_publication'], $matches)) {
                        $journalsById[$matches[1]] = null;;
                    }
                    else {
                        $publicationsById[$entry['id_publication']] = null;
                    }
                }
            }
        }

        if (!empty($publicationsById)) {
            $qb = $em->createQueryBuilder();

            $qb->select([ 'B' ])
                ->from('App\Entity\Bibitem', 'B')
                ->andWhere('B.id IN (:ids) AND B.status <> -1')
                ->setParameter('ids', array_keys($publicationsById))
                ;

            $results = $qb->getQuery()
                ->getResult();

            foreach ($results as $bibitem) {
                $publicationsById[$bibitem->getId()] = $bibitem;
            }
        }

        $this->infoExpanded = [];
        foreach ($this->info as $key => $entries) {
            foreach ($entries as $entry) {
                if (!empty($entry['id_publication'])
                    && !is_null($publicationsById[$entry['id_publication']]))
                {
                    $bibitem = $publicationsById[$entry['id_publication']];
                    if ($bibitem instanceof Journal) {
                        $citation = $bibitem->getName();
                        if (!empty($entry['pages'])) {
                            $citation .= ', ' . $entry['pages'];
                        }

                        $entry['citation'] = htmlspecialchars($citation . '.', ENT_COMPAT, 'utf-8');
                    }
                    else {
                        if (!empty($entry['pages'])) {
                            $bibitem->setPagination($entry['pages']);
                        }

                        $entry['citation'] = $bibitem->renderCitationAsHtml($citeProc);
                    }
                }

                if (!array_key_exists($key, $this->infoExpanded)) {
                    $this->infoExpanded[$key] = [];
                }

                $this->infoExpanded[$key][] = $entry;
            }
        }
    }

    public function getInfoExpanded($category)
    {
        if (!array_key_exists($category, $this->infoExpanded)) {
            return [];
        }

        return $this->infoExpanded[$category];
    }
}
