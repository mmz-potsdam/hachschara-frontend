<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Site
 *
 * @ORM\Table(name="Project")
 * @ORM\Entity
 */
class Site
implements JsonLdSerializable
{
    protected static $termsById = null;

    public static function initTerms($em)
    {
        if (!is_null(self::$termsById)) {
            return; // already initialized
        }

        self::$termsById = [];

        $qb = $em->createQueryBuilder();

        $qb->select([ 'T' ])
            ->from('App\Entity\Term', 'T')
            ->andWhere('T.category IN (:categories) AND T.status <> -1')
            ->setParameter('categories', [
                'type',
                'roleActor',
                'condition',
                'education',
            ])
            ;

        foreach ($qb->getQuery()->getResult() as $term) {
            self::$termsById[$term->getId()] = $term;
        }
    }

    use ContributorTrait;

    use InfoTrait;

    protected $info = [];
    protected $extractFromNotes = [ 'address', 'general' ];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status = '0';

    /**
     * @ORM\Column(name="type", type="simple_array", nullable=false)
     */
    private $types;

    /**
     * @var Place The location of, for example, where an event is happening, where an organization is located, or where an action takes place. .
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", inversedBy="sites")
     * @ORM\JoinColumn(name="locality_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var string Name of the location.
     *
     * @ORM\Column(nullable=true,name="locality")
     */
    protected $locationLabel;

    /**
     * @var string Street Address of the location.
     *
     * @ORM\Column(nullable=true,name="street_address")
     */
    protected $streetAddress;

    /**
     * @var string Postal Code of the location.
     *
     * @ORM\Column(nullable=true,name="postal_code")
     */
    protected $postalCode;

    /**
     * @var double The latitude of the place.
     *
     * @ORM\Column(nullable=true)
     *
     */
    protected $latitude;

    /**
     * @var double The longitude of the place.
     *
     * @ORM\Column(nullable=true)
     *
     */
    protected $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="start_date", type="string", nullable=true)
     */
    private $startDate;

    /**
     * @var string
     *
     * @ORM\Column(name="realized_date", type="string", nullable=true)
     */
    private $realizedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="end_date", type="string", nullable=true)
     */
    private $endDate;

    /**
     * @var string
     *
     * @ORM\Column(name="destruction_date", type="string", nullable=true)
     */
    private $destructionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=511, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alternate_name", type="text", nullable=true)
     */
    private $alternateName;

    /**
     * @var array
     *
     * @ORM\Column(name="description", type="json", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var double
     *
     * @ORM\Column(name="operating_area", type="decimal", nullable=true)
     */
    private $operatingArea;

    /**
     * @var array
     *
     * @ORM\Column(name="operating_area_description", type="json", length=65535, nullable=true)
     */
    private $operatingAreaDescription;

    /**
     * @ORM\Column(name="educations", type="simple_array", length=4096, nullable=true)
     */
    private $educations;

    /**
     * @var array
     *
     * @ORM\Column(name="educations_description", type="json", length=65535, nullable=true)
     */
    private $educationsDescription;

    /**
     * @var integer
     *
     * @ORM\Column(name="condition", type="integer", nullable=true)
     */
    private $condition = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="project_history", type="text", nullable=true)
     */
    private $projectHistory;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AgentSite",
     *   mappedBy="site",
     *   cascade={"persist", "remove"},
     *   orphanRemoval=TRUE
     * )
     * @ORM\OrderBy({"ord" = "ASC"})
     */
    private $agentReferences;

    /**
     * @var array|null
     *
     * @ORM\Column(name="notes", type="json", length=65535, nullable=true)
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="changed", type="datetime", nullable=true)
     */
    private $changedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="changed_by", type="integer", nullable=true)
     */
    private $changedBy;

    /**
     * @var integer
     *
     * xORM\Column(name="flags", type="integer", nullable=false)
     */
    private $flags = '0';

    /**
     * xORM\ManyToMany(targetEntity="Person", mappedBy="sites")
     * xORM\OrderBy({"familyName" = "ASC", "givenName" = "ASC"})
     */
    protected $persons;

    /**
     * @ORM\OneToMany(targetEntity="SiteMedia", mappedBy="site", fetch="EAGER")
     * @ORM\OrderBy({"name" = "ASC", "ord" = "ASC"})
     */
    protected $media;

    public static function extractYear($datetime)
    {
        if (is_null($datetime)) {
            return $datetime;
        }

        if (preg_match('/^([\d]+)/', $datetime, $matches)) {
            return (int)($matches[1]);
        }
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
     * Gets name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Gets status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Builds status label.
     *
     * @return string
     */
    public function buildStatusLabel()
    {
        if (is_null($this->status) || !array_key_exists($this->status, \App\Search\SearchListBuilder::$STATUS_LABELS)) {
            return '';
        }

        return \App\Search\SearchListBuilder::$STATUS_LABELS[$this->status];
    }

    /**
     * Gets term ids.
     *
     * @return array|null
     */
    private function getTermIds($property)
    {
        if (empty($this->$property)) {
            return $this->$property;
        }

        // simple_array doesn't trim space after ,
        return array_map('trim', $this->$property);
    }

    /**
     * Gets terms.
     *
     * @return ArrayCollection<int, Term>
     */
    private function getTerms($property, $termsIds = null)
    {
        $terms = [];

        if (is_null($termsIds)) {
            $termsIds = $this->getTermIds($property);
        }

        if (empty($termsIds) || is_null(self::$termsById)) {
            return $terms;
        }

        foreach ($termsIds as $termId) {
            if (array_key_exists($termId, self::$termsById)) {
                $terms[] = self::$termsById[$termId];
            }
        }

        return $terms;
    }

    /**
     * Helper to extract localized property or (German) fallback.
     *
     * @return string
     */
    private function getLocalizedProperty($property, $locale = 'de')
    {
        if (empty($this->$property)) {
            return;
        }

        if (is_array($this->$property)) {
            if (array_key_exists($locale, $this->$property)
                && !empty($this->$property[$locale]))
            {
                return $this->$property[$locale];
            }

            // fallback
            if (array_key_exists('de', $this->$property)
                && !empty($this->$property['de']))
            {
                return $this->$property['de'];
            }
        }
    }

    /**
     * Gets types.
     *
     * @return ArrayCollection<int, Term>
     */
    public function getTypes()
    {
        return $this->getTerms('types');
    }

    /**
     * Gets educations.
     *
     * @return ArrayCollection<int, Term>
     */
    public function getEducations()
    {
        return $this->getTerms('educations');
    }

    /**
     * Gets condition.
     *
     * @return Term|null
     */
    public function getCondition()
    {
        if (empty($this->condition)) {
            return;
        }

        $terms = $this->getTerms('condition', [ $this->condition ]);

        if (empty($terms)) {
            return;
        }

        return $terms[0];
    }

    /**
     * Gets projectHistory.
     *
     * @return string
     */
    public function getProjectHistory()
    {
        return $this->projectHistory;
    }

    /**
     * Gets localized educations description.
     *
     * @return string
     */
    public function getEducationsDescriptionLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('educationsDescription', $locale);
    }

    /**
     * Sets founding location.
     *
     * @param Place $foundingLocation
     *
     * @return $this
     */
    public function setFoundingLocation(Place $foundingLocation = null)
    {
        $this->foundingLocation = $foundingLocation;

        return $this;
    }

    /**
     * Gets location.
     *
     * @return Place
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Gets locationLabel.
     *
     * @return string
     */
    public function getLocationLabel()
    {
        return $this->locationLabel;
    }

    /**
     * Gets location info.
     *
     */
    public function getLocationInfo($locale = 'en')
    {
        if (!is_null($this->location)) {
            return Agent::buildPlaceInfo($this->location, $locale);
        }

        if (!empty($this->locationLabel)) {
            return [
                'name' => $this->locationLabel,
            ];
        }
    }

    /**
     * Gets street address.
     *
     * @return string
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Gets postal code.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Gets geo.
     *
     * @return string
     */
    public function getGeo()
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            if (!is_null($this->location)) {
                return $this->location->getGeo();
            }

            return null;
        }

        return implode(',', [$this->latitude, $this->longitude]);
    }

    /**
     * Gets start date.
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate; // self::stripTime($this->startDate);
    }

    /**
     * Gets realized date.
     *
     * @return string
     */
    public function getRealizedDate()
    {
        return $this->realizedDate; // self::stripTime($this->endDate);
    }

    /**
     * Gets end date.
     *
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate; // self::stripTime($this->endDate);
    }

    /**
     * Gets start year.
     *
     * @return string
     */
    public function getStartear()
    {
        return self::extractYear($this->startDate);
    }

    /**
     * Gets operating area.
     *
     * @return double
     */
    public function getOperatingArea()
    {
        return $this->operatingArea;
    }

    /**
     * Gets localized operating area description.
     *
     * @return string
     */
    public function getOperatingAreaDescriptionLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('operatingAreaDescription', $locale);
    }

    /**
     * Gets localized description.
     *
     * @return string
     */
    public function getDescriptionLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('description', $locale);
    }

    /**
     * Gets organizations.
     *
     * @return ArrayCollection<int, Organization>
     */
    public function getOrganizations()
    {
        return $this->organizations;
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
     * Gets agent references.
     *
     * @return ArrayCollection<int, AgentSite>
     */
    public function getAgentReferences()
    {
        return $this->agentReferences;
    }

    /**
     * Gets person references.
     *
     * @return ArrayCollection<int, AgentSite>
     */
    public function getPersonReferences()
    {
        return $this->getAgentReferences()->filter(function(AgentSite $agentSite) {
            return $agentSite->getAgent() instanceof Person;
        });
    }

    /**
     * Gets organization references.
     *
     * @return ArrayCollection<int, AgentSite>
     */
    public function getOrganizationReferences()
    {
        return $this->getAgentReferences()->filter(function(AgentSite $agentSite) {
            return $agentSite->getAgent() instanceof Organization;
        });
    }

    /**
     * Returns SiteMedia
     *
     * @return ArrayCollection|null
     */
    public function getMedia($name = null)
    {
        if (is_null($this->media)) {
            return $this->media;
        }

        return $this->media->filter(
            function ($entry) use ($name) {
                if ($entry->getStatus() <= 0) {
                    return false;
                }

                return is_null($name)
                    || strpos($entry->getName(), $name) === 0 // str_starts_with for PHP < 8.0
                    ;
            }
        );
    }

    /**
     * Serializes entity according to Schema.org.
     *
     * @return array
     */
    public function jsonLdSerialize($locale, $omitContext = false)
    {
        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'CreateAction',
            'name' => $this->getName(),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        foreach ([ 'start', 'end'] as $key) {
            $property = $key . 'date';
            if (!empty($this->$property)) {
                $ret[$property . 'Time'] = \AppBundle\Utils\JsonLd::formatDate8601($this->$property);
            }
        }

        if (!empty($this->location)) {
            $ret['location'] = [
                '@type' => 'Place',
                'name' => $this->location->getName(),
            ];

            // TODO
            $addresses = [];
            /*
            $addresses = array_map(function ($address) { return $address['info']; }, $this->location->getAddressesSeparated());
            if (!empty($addresses)) {
                $ret['location']['address'] = join(', ', $addresses);
            }
            */

            /*
            // TODO
            $place = $this->location->getPlace();
            if (!empty($place)) {
                $ret['location']['containedInPlace'] = $place->jsonLdSerialize($locale, true);
            }
            */
        }

        $description = $this->getDescriptionLocalized($locale);
        if (!empty($description)) {
            $ret['description'] = $description;
        }

        foreach ([ 'url' ] as $property) {
            if (!empty($this->$property)) {
                $ret[$property] = $this->$property;
            }
        }

        return $ret;
    }

    /* make private properties public through a generic __get / __set */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            return $this->$name = $value;
        }
    }
}
