<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An agent, such as a person or an organization.
 *
 * @ORM\Entity
 * @ORM\Table(name="Agent")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"person" = "Person", "organization" = "Organization"})
 */
abstract class Agent
{
    protected static $entityfactsLocales = [ 'en' ]; // enabled locales in preferred order

    /**
     * appends -00-00 or -00 to dates without month / day
     */
    protected static function formatDateIncomplete($dateStr)
    {
        if (preg_match('/^\d{4}$/', $dateStr)) {
            $dateStr .= '-00-00';
        }
        else if (preg_match('/^\d{4}\-\d{2}$/', $dateStr)) {
            $dateStr .= '-00';
        }

        return $dateStr;
    }

    public static function buildPlaceInfo($place, $locale)
    {
        $placeInfo = [
            'name' => $place->getNameLocalized($locale),
            'id' => $place->getId(),
            'tgn' => $place->getTgn(),
            'geo' => $place->getGeo(),
        ];

        return $placeInfo;
    }

    protected static function buildPlaceInfoFromEntityfacts($entityfacts, $key)
    {
        if (is_null($entityfacts) || !array_key_exists($key, $entityfacts)) {
            return;
        }

        $place = $entityfacts[$key][0];
        if (empty($place)) {
            return;
        }

        $placeInfo = [ 'name' => $place['preferredName'] ];

        if (!empty($place['@id'])) {
            $uri = $place['@id'];
            if (preg_match('/^'
                           . preg_quote('http://d-nb.info/gnd/', '/')
                           . '(\d+\-?[\dxX]?)$/', $uri, $matches))
            {
                $placeInfo['gnd'] = $matches[1];
            }
        }

        return $placeInfo;
    }

    protected static function buildPlaceInfoFromWikidata($wikidata, $key)
    {
        if (is_null($wikidata) || !array_key_exists($key, $wikidata)) {
            return;
        }

        return [ 'name' => $wikidata[$key] ];
    }

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $status = 0;

    /**
     * @var string Additional name forms.
     *
     * @ORM\Column(name="alternate_name", nullable=true)
     */
    protected $alternateName;

    /**
     * @var string A short description of the item.
     *
     * @ORM\Column(name="description", type="string", nullable=true)
     *
     */
    protected $description;

    /**
     * @var string A description of the item.
     *
     * @ORM\Column(name="disambiguating_description", type="string", nullable=true)
     *
     */
    protected $disambiguatingDescription;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $ulan;

    /**
     * @var string
     * @ORM\Column(name="gnd",type="string", nullable=true)
     */
    protected $gnd;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $viaf;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $wikidata;

    /**
    * xORM\Column(type="json", nullable=true)
    */
    protected $entityfacts;

    /**
     * @ORM\OneToMany(targetEntity="AgentSite", mappedBy="agent", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    protected $siteReferences;

    /**
     * @var
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="changed", type="datetime")
     */
    protected $changedAt;

    /**
     * @var \DateTime The date on which the Person or one of its related entities were last modified.
     */
    protected $dateModified;

    /**
     * @var string
     *
     */
    protected $slug;

    /**
     * @var string URL of the item.
     *
     * @ORM\Column(nullable=true)
     */
    protected $url;

    /**
     * Sets id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Gets alternateName.
     *
     * @return string
     */
    public function getAlternateName()
    {
        return $this->alternateName;
    }

    /**
     * Sets description.
     *
     * @param array|null $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getDescriptionLocalized($locale)
    {
        if (empty($this->description)) {
            return;
        }

        if (is_array($this->description)) {
            if (array_key_exists($locale, $this->description)) {
                return $this->description[$locale];
            }
        }
        else {
            return $this->description;
        }
    }

    /**
     * Gets disambiguatingDescription.
     *
     * @return string|null
     */
    public function getDisambiguatingDescription()
    {
        return $this->disambiguatingDescription;
    }

    /**
     * Sets url.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets gnd.
     *
     * @param string $gnd
     *
     * @return $this
     */
    public function setGnd($gnd)
    {
        $this->gnd = $gnd;

        return $this;
    }

    /**
     * Gets gnd.
     *
     * @return string
     */
    public function getGnd()
    {
        return $this->gnd;
    }

    /**
     * Sets ulan.
     *
     * @param string $ulan
     *
     * @return $this
     */
    public function setUlan($ulan)
    {
        $this->ulan = $ulan;

        return $this;
    }

    /**
     * Gets ulan.
     *
     * @return string
     */
    public function getUlan()
    {
        return $this->ulan;
    }

    /**
     * Sets viaf.
     *
     * @param string $viaf
     *
     * @return $this
     */
    public function setViaf($viaf)
    {
        $this->viaf = $viaf;

        return $this;
    }

    /**
     * Gets viaf.
     *
     * @return string
     */
    public function getViaf()
    {
        return $this->viaf;
    }

    /**
     * Sets wikidata.
     *
     * @param string $wikidata
     *
     * @return $this
     */
    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;

        return $this;
    }

    /**
     * Gets wikidata.
     *
     * @return string
     */
    public function getWikidata()
    {
        return $this->wikidata;
    }

    /**
     * Sets entityfacts.
     *
     * @param array $entityfacts
     *
     * @return $this
     */
    public function setEntityfacts($entityfacts, $locale = 'en')
    {
        if (in_array($locale, self::$entityfactsLocales)) {
            if (is_null($this->entityfacts)) {
                $this->entityfacts = [];
            }

            $this->entityfacts[$locale] = $entityfacts;
        }

        return $this;
    }

    /**
     * Gets entityfacts.
     *
     * @return array
     */
    public function getEntityfacts($locale = 'en', $forceLocale = false)
    {
        if (is_null($this->entityfacts)) {
            return null;
        }

        // preferred locale
        if (array_key_exists($locale, $this->entityfacts)) {
            return $this->entityfacts[$locale];
        }

        if (!$forceLocale) {
            // try to use fallback
            foreach (self::$entityfactsLocales as $locale) {
                if (array_key_exists($locale, $this->entityfacts)) {
                    return $this->entityfacts[$locale];
                }
            }
        }

        return null;
    }

    /**
     * Sets notes.
     *
     * @param array $notes
     *
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Gets notes.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets slug.
     *
     * @param string $slug
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Gets slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Sets dateModified.
     *
     * @param \DateTime $dateModified
     *
     * @return $this
     */
    public function setDateModified(\DateTime $dateModified = null)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Gets dateModified.
     *
     * @return \DateTime
     */
    public function getDateModified()
    {
        if (!is_null($this->dateModified)) {
            return $this->dateModified;
        }

        return $this->changedAt;
    }

    /**
     * Gets site references.
     *
     * @return ArrayCollection<int, AgentSite>
     */
    public function getSiteReferences()
    {
        return $this->siteReferences;
    }
}
