<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Inflector\InflectorFactory;

/**
 * Entities that have a somewhat fixed, physical extension.
 *
 * @see http://schema.org/Place Documentation on Schema.org
 */
#[ORM\Entity]
#[ORM\Table(name: 'Geoname')]
class Place implements \JsonSerializable, JsonLdSerializable
{
    static $zoomLevelByType = [
        'neighborhood' => 12,
        'city district' => 11,
        'district' => 11,
        'inhabited place' => 10,
    ];

    public static function buildTypeLabel($type)
    {
        if ('root' == $type) {
            return '';
        }

        if ('inhabited place' == $type) {
            return 'place';
        }

        return $type;
    }

    public static function buildPluralizedTypeLabel($type, $count)
    {
        if (empty($type)) {
            return '';
        }

        $label = self::buildTypeLabel($type);
        if ($count == 1) {
            $inflector = InflectorFactory::create()->build();

            $label = $inflector->singularize($label);
        }

        return ucfirst($label);
    }

    public static function ensureSortByPreferredLanguages($assoc, $default = null)
    {
        $language_preferred_ordered = [ 'de', 'en' ];

        if (is_null($assoc)) {
            $assoc = [];
        }

        foreach ($language_preferred_ordered as $lang) {
            if (!array_key_exists($lang, $assoc)) {
                $assoc[$lang] = $default;
            }
        }

        // make sure order is as in $language_preferred_ordered
        uksort($assoc, function ($langA, $langB) use ($language_preferred_ordered) {
            if ($langA == $langB) {
                return 0;
            }

            $langOrderA = array_search($langA, $language_preferred_ordered);
            if (false === $langOrderA) {
                $langOrderA = 99;
            }
            $langOrderB = array_search($langB, $language_preferred_ordered);
            if (false === $langOrderB) {
                $langOrderB = 99;
            }

            return ($langOrderA < $langOrderB) ? -1 : 1;
        });

        return $assoc;
    }

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    protected $id;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    protected $status = 0;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected $type = 'inhabited place';

    /**
     * @var double|null The latitude of the place.
     */
    #[ORM\Column(nullable: true)]
    protected $latitude;

    /**
     * @var double The longitude of the place.
     */
    #[ORM\Column(nullable: true)]
    protected $longitude;

    /**
     * @var string The name of the item.
     *
     */
    #[ORM\Column(nullable: false)]
    protected $name;

    /**
     * @var string|null An alternate name for the item.
     */
    #[ORM\Column(name: 'name_alternate', type: 'string', nullable: true)]
    protected $alternateName;


    /**
     * @var string|null The iso 3166-1 alpha-2 country code of the place.
     */
    #[ORM\Column(name: 'country_code', type: 'string', nullable: true)]
    protected $countryCode;

    /**
     * @var Country|null
     */
    #[ORM\ManyToOne(targetEntity: 'Country', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'country_code', referencedColumnName: 'cc', nullable: true)]
    protected $country;

    /**
     * @var Term|null The role.
     *
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Term')]
    #[ORM\JoinColumn(name: 'historical_region', referencedColumnName: 'id')]
    protected $historicalRegion;

    /**
     * @var string|null The Getty Thesaurus of Geographic Names Identifier of the place.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $tgn;

    /**
     * @var string|null The Getty Thesaurus of Geographic Names Identifier of the parent place.
     */
    #[ORM\Column(name: 'tgn_parent', type: 'string', nullable: true)]
    protected $parentTgn;

    /**
     * @var string|null The GND Identifier of the place.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $gnd;

    /**
     * @var string The GeoNames Identifier of the place.
     */
    #[ORM\Column(name: 'geonames_id', type: 'string', nullable: true)]
    protected $geonames;

    /**
     * @ArrayCollection|null The sites located in the place.
     */
    #[ORM\OneToMany(targetEntity: 'Site', mappedBy: 'location', cascade: ['all'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    protected $sites;

    /**
     * @var \DateTime|null The date on which the entity was created.
     *
     */
    #[ORM\Column(name: 'created', type: 'datetime')]
    protected $createdAt;

    /**
     * @var \DateTime|null The date on which the entity was last modified.
     *
     */
    #[ORM\Column(name: 'changed', type: 'datetime')]
    protected $changedAt;

    /**
     * @var \DateTime|null
     */
    protected $dateModified;

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
     * @return int|null
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
     * Sets type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets geo.
     *
     * @return string|null
     */
    public function getGeo()
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }

        return implode(',', [$this->latitude, $this->longitude]);
    }

    /**
     * Gets sites.
     *
     * @return ArrayCollection|null
     */
    public function getSites()
    {
        if (is_null($this->sites)) {
            return null;
        }

        return $this->sites->filter(
            function ($entity) {
                return 1 == $entity->getStatus();
            }
        );
    }

    /**
     * Determines whether a center marker should be shown for the place.
     */
    public function showCenterMarker($em): bool
    {
        $ancestorOrSelf = $this;

        while (!is_null($ancestorOrSelf)) {
            if ($ancestorOrSelf->type == 'inhabited places') {
                return true;
            }

            $ancestorOrSelf = $ancestorOrSelf->getParent($em);
        }

        return false;
    }

    /**
     * Gets default zoom level for the place.
     *
     * @return int
     */
    public function getDefaultZoomlevel(): int
    {
        if (array_key_exists($this->type, self::$zoomLevelByType)) {
            return self::$zoomLevelByType[$this->type];
        }

        return 8;
    }

    /**
     * Sets countryCode.
     *
     * @param string|null $countryCode
     *
     * @return $this
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Gets countryCode.
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Sets name.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets alternateName.
     *
     * @param string|null $alternateName
     *
     * @return $this
     */
    public function setAlternateName($alternateName)
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    /**
     * Gets alternateName.
     *
     * @return array|null
     */
    public function getAlternateName()
    {
        return self::ensureSortByPreferredLanguages($this->alternateName, $this->name);
    }

    /**
     * Gets normalized historical region.
     *
     * @return Term|null
     */
    public function getHistoricalRegion()
    {
        return $this->historicalRegion;
    }

    /**
     * Sets Getty Thesaurus of Geographic Names Identifier.
     *
     * @param string|null $tgn
     *
     * @return $this
     */
    public function setTgn($tgn)
    {
        $this->tgn = $tgn;

        return $this;
    }

    /**
     * Gets Getty Thesaurus of Geographic Names.
     *
     * @return string|null
     */
    public function getTgn()
    {
        return $this->tgn;
    }

    /**
     * Sets gnd.
     *
     * @param string|null $gnd
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
     * @return string|null
     */
    public function getGnd()
    {
        return $this->gnd;
    }

    /**
     * Sets geonames.
     *
     * @param string|null $geonames
     *
     * @return $this
     */
    public function setGeonames($geonames)
    {
        $this->geonames = $geonames;

        return $this;
    }

    /**
     * Gets geonames.
     *
     * @return string|null
     */
    public function getGeonames()
    {
        return $this->geonames;
    }

    /**
      * Gets parent place.
      *
      * @return Place|null
      */
    public function getParent($em)
    {
        if (is_null($this->parentTgn)) {
            return null;
        }

        return $em->getRepository('App\Entity\Place')
            ->findOneBy(['tgn' => $this->parentTgn]);
    }

    /**
     * Gets children places.
     *
     * @return Place[]|null
     */
    public function getChildren($em)
    {
        if (is_null($this->tgn)) {
            return null;
        }

        $qb = $em->createQueryBuilder();

        $qb->select([
            'P',
            "COALESCE(P.alternateName,P.name) HIDDEN nameSort",
        ])
            ->from('App\Entity\Place', 'P')
            ->where("P.parentTgn = :tgn")
            ->setParameter('tgn', $this->tgn)
            ->orderBy('nameSort')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets children places grouped by type.
     *
     * @return array|null
     */
    public function getChildrenByType($em)
    {
        $children = $this->getChildren($em);
        if (is_null($children)) {
            return null;
        }

        $ret = [];

        foreach ($children as $child) {
            $type = $child->getType();
            if (!array_key_exists($type, $ret)) {
                $ret[$type] = [];
            }
            $ret[$type][] = $child;
        }

        $typeWeights = [
            'continents' => -10,
            'nations' => 0,
            'dependent states' => 1,
            'former primary political entities' => 2,
            'states' => 3,
            'general regions' => 5,
            'communities' => 10,
            'historical regions' => 11,
            'inhabited places' => 15,
            'archipelagos' => 20,
        ];

        uksort($ret, function ($typeA, $typeB) use ($typeWeights) {
            if ($typeA == $typeB) {
                return 0;
            }

            $typeOrderA = array_key_exists($typeA, $typeWeights) ? $typeWeights[$typeA] : 99;
            $typeOrderB = array_key_exists($typeB, $typeWeights) ? $typeWeights[$typeB] : 99;

            return ($typeOrderA < $typeOrderB) ? -1 : 1;
        });

        return $ret;
    }

    /**
     * Gets localized name.
     *
     * @return string|null
     */
    public function getNameLocalized($locale = 'en')
    {
        if (!empty($this->alternateName)) {
            return $this->alternateName;
        }

        return $this->getName();
    }

    /**
     * Gets type label.
     *
     * @return string
     */
    public function getTypeLabel()
    {
        return self::buildTypeLabel($this->type);
    }

    /**
     * Gets path from root to place.
     *
     * @return array
     */
    public function getPath($em)
    {
        $path = [];
        $parent = $this->getParent($em);
        while ($parent != null) {
            $path[] = $parent;
            $parent = $parent->getParent($em);
        }

        return array_reverse($path);
    }

    /**
     * Sets dateModified.
     *
     * @param \DateTime|null $dateModified
     *
     * @return $this
     */
    public function setDateModified(?\DateTime $dateModified = null)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Gets dateModified.
     *
     * @return \DateTime|null
     */
    public function getDateModified()
    {
        if (!is_null($this->dateModified)) {
            return $this->dateModified;
        }

        return $this->changedAt;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'geo' => $this->getGeo(),
            'tgn' => $this->tgn,
            'gnd' => $this->gnd,
        ];
    }

    /**
     * Serializes entity according to Schema.org.
     *
     * @return array
     */
    public function jsonLdSerialize($locale, $omitContext = false, $em = null): array
    {
        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'Place',
            'name' => $this->getNameLocalized($locale),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        if (!empty($this->latitude) && !empty($this->longitude)) {
            $ret['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' =>  $this->latitude,
                'longitude' => $this->longitude,
            ];
        }

        if (!is_null($em)) {
            $parent = $this->getParent($em);
            if (!is_null($parent)) {
                $ret['containedInPlace'] = $parent->jsonLdSerialize($locale, true);
            }
        }

        $sameAs = [];
        if (!empty($this->tgn)) {
            $sameAs[] = 'http://vocab.getty.edu/tgn/' . $this->tgn;
        }
        if (!empty($this->gnd)) {
            $sameAs[] = 'https://d-nb.info/gnd/' . $this->gnd;
        }
        if (count($sameAs) > 0) {
            $ret['sameAs'] = (1 == count($sameAs)) ? $sameAs[0] : $sameAs;
        }

        return $ret;
    }
}
