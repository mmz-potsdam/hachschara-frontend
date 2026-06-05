<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Site
 */
#[ORM\Entity]
#[ORM\Table(name: 'Project')]
class Site implements JsonLdSerializable
{
    use HasTranslationsTrait;

    use ContributorTrait;

    use InfoTrait;

    protected static $termsById = null;

    /**
     * Fetch Term for display purpose
     */
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

    protected $info = [];
    protected $extractFromNotes = [ 'address', 'general', 'publication' ];

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status', type: 'integer', nullable: false)]
    private $status = 0;

    /**
     * @var \DateTime|null Date of first broadcast/publication.
     */
    #[Assert\Date]
    #[ORM\Column(name: 'published', type: 'datetime', nullable: true)]
    private $datePublished;

    /**
     * @var \DateTime|null The date on which the CreativeWork was most recently modified or when the item's entry was modified .
     */
    #[Assert\Date]
    #[ORM\Column(name: 'modified', type: 'datetime', nullable: true)]
    private $dateModified;

    /**
     * @var string|null A license document that applies to this content, typically indicated by URL.
     */
    #[Assert\Type(type: 'string')]
    #[ORM\Column(name: 'license', nullable: true)]
    protected $license;

    /**
     * @var array
     */
    #[ORM\Column(name: 'type', type: 'simple_array', nullable: false)]
    private $types;

    /**
     * @var Place|null The location of, for example, where an event is happening, where an organization is located, or where an action takes place. .
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Place', inversedBy: 'sites')]
    #[ORM\JoinColumn(name: 'locality_id', referencedColumnName: 'id')]
    protected $location;

    /**
     * @var string|null Name of the location.
     *
     */
    #[ORM\Column(nullable: true, name: 'locality')]
    protected $locationLabel;

    /**
     * @var string|null Street Address of the location.
     *
     */
    #[ORM\Column(nullable: true, name: 'street_address')]
    protected $streetAddress;

    /**
     * @var string|null Postal Code of the location.
     *
     */
    #[ORM\Column(nullable: true, name: 'postal_code')]
    protected $postalCode;

    /**
     * @var double|null The latitude of the site.
     */
    #[ORM\Column(nullable: true)]
    protected $latitude;

    /**
     * @var double|null The longitude of the site.
     */
    #[ORM\Column(nullable: true)]
    protected $longitude;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'start_date', type: 'string', nullable: true)]
    private $startDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'realized_date', type: 'string', nullable: true)]
    private $realizedDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'end_date', type: 'string', nullable: true)]
    private $endDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'destruction_date', type: 'string', nullable: true)]
    private $destructionDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: 'string', length: 511, nullable: true)]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'alternate_name', type: 'text', nullable: true)]
    private $alternateName;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'abstract', type: 'json', length: 65535, nullable: true)]
    private $abstract;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'description', type: 'json', length: 65535, nullable: true)]
    private $description;

    /**
     * @var double|null
     */
    #[ORM\Column(name: 'operating_area', type: 'decimal', nullable: true)]
    private $operatingArea;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'operating_area_description', type: 'json', length: 65535, nullable: true)]
    private $operatingAreaDescription;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'educations', type: 'simple_array', length: 4096, nullable: true)]
    private $educations;

    /**
     * @var mixed|null
     */
    #[ORM\Column(name: 'educations_description', type: 'json', length: 65535, nullable: true)]
    private $educationsDescription;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'condition', type: 'integer', nullable: true)]
    private $condition = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'project_history', type: 'text', nullable: true)]
    private $projectHistory;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: true)]
    private $url;

    #[ORM\OneToMany(targetEntity: 'AgentSite', mappedBy: 'site', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ord' => 'ASC'])]
    private $agentReferences;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'notes', type: 'json', length: 65535, nullable: true)]
    private $notes;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'created', type: 'datetime', nullable: true)]
    private $createdAt;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'created_by', type: 'integer', nullable: true)]
    private $createdBy;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'changed', type: 'datetime', nullable: true)]
    private $changedAt;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'changed_by', type: 'integer', nullable: true)]
    private $changedBy;

    /**
     * @var int
     */
    private $flags = 0;

    /**
     */
    protected $persons;

    #[ORM\OneToMany(targetEntity: 'SiteMedia', mappedBy: 'site', fetch: 'EAGER')]
    #[ORM\OrderBy(['name' => 'ASC', 'ord' => 'ASC'])]
    protected $media;

    public static function extractYear($datetime)
    {
        if (is_null($datetime)) {
            return $datetime;
        }

        if (preg_match('/^([\d]+)/', $datetime, $matches)) {
            return (int) ($matches[1]);
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
     * @return string|null
     */
    public function getName($locale = null)
    {
        return $this->getTranslatedProperty('name', $locale);
    }

    /**
     * Gets alternateName.
     *
     * @return string|null
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
        /*
        if (is_null($this->status) || !array_key_exists($this->status, \App\Search\SearchListBuilder::$STATUS_LABELS)) {
            return '';
        }

        return \App\Search\SearchListBuilder::$STATUS_LABELS[$this->status];
        */
        return '';
    }

    /**
     * Gets datePublished.
     *
     * @return \DateTime|null
     */
    public function getDatePublished()
    {
        return $this->datePublished;
    }

    /**
     * Gets dateModified.
     *
     * @return \DateTime|null
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Gets license.
     *
     * @return array|null
     */
    public function getLicense()
    {
        if (is_null($this->license)) {
            return null;
        }

        $license = [];
        switch ($this->license) {
            case 'CC BY-NC-ND':
                $license['url'] = 'https://creativecommons.org/licenses/by-nc-nd/4.0/';
                $license['rights'] = 'license.by-nc-nd';
                break;

            case 'CC BY-SA':
                $license['url'] = 'https://creativecommons.org/licenses/by-sa/4.0/';
                $license['rights'] = 'license.by-sa';
                break;

            case 'restricted':
                $license['rights'] = 'license.restricted';
                break;
        }

        return $license;
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
     * @return array
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
     * @return string|null
     */
    private function getLocalizedProperty($property, $locale = 'de')
    {
        if (empty($this->$property)) {
            return null;
        }

        if (is_array($this->$property)) {
            if (array_key_exists($locale, $this->$property)
                && !empty($this->$property[$locale])) {
                return $this->$property[$locale];
            }

            // fallback
            if (array_key_exists('de', $this->$property)
                && !empty($this->$property['de'])) {
                return $this->$property['de'];
            }
        }

        return null;
    }

    /**
     * Gets types.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->getTerms('types');
    }

    /**
     * Gets educations.
     *
     * @return array
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
            return null;
        }

        $terms = $this->getTerms('condition', [ $this->condition ]);

        if (0 == count($terms)) {
            return null;
        }

        return $terms[0];
    }

    /**
     * Gets project history.
     *
     * @return string
     */
    public function getProjectHistory($locale = 'de')
    {
        return $this->getTranslatedProperty('projectHistory', $locale);
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
     * Gets location.
     *
     * @return Place|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Gets locationLabel.
     *
     * @return string|null
     */
    public function getLocationLabel()
    {
        return $this->locationLabel;
    }

    /**
     * Gets location info.
     *
     * return array|null
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

        return null;
    }

    /**
     * Gets street address.
     *
     * @return string|null
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Gets postal code.
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Gets geo.
     *
     * @return string|null
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
     * @return string|null
     */
    public function getStartDate()
    {
        return $this->startDate; // self::stripTime($this->startDate);
    }

    /**
     * Gets realized date.
     *
     * @return string|null
     */
    public function getRealizedDate()
    {
        return $this->realizedDate; // self::stripTime($this->endDate);
    }

    /**
     * Gets end date.
     *
     * @return string|null
     */
    public function getEndDate()
    {
        return $this->endDate; // self::stripTime($this->endDate);
    }

    /**
     * Gets start year.
     *
     * @return string|null
     */
    public function getStartear()
    {
        return self::extractYear($this->startDate);
    }

    /**
     * Gets operating area.
     *
     * @return double|null
     */
    public function getOperatingArea()
    {
        return $this->operatingArea;
    }

    /**
     * Gets localized operating area description.
     *
     * @return string|null
     */
    public function getOperatingAreaDescriptionLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('operatingAreaDescription', $locale);
    }

    /**
     * Gets localized abstract.
     *
     * @return string|null
     */
    public function getAbstractLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('abstract', $locale);
    }

    /**
     * Gets localized description.
     *
     * @return string|null
     */
    public function getDescriptionLocalized($locale = 'de')
    {
        return $this->getLocalizedProperty('description', $locale);
    }

    /**
     * Sets url.
     *
     * @param string|null $url
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
     * @return string|null
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
        return $this->getAgentReferences()->filter(function (AgentSite $agentSite) {
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
        return $this->getAgentReferences()->filter(function (AgentSite $agentSite) {
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
    public function jsonLdSerialize($locale, $omitContext = false): array
    {
        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'Article',
            'name' => $this->getName($locale),
            'headline' => $this->getName($locale),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        if (!is_null($this->datePublished)) {
            $ret['datePublished'] = \App\Utils\JsonLd::formatDate8601($this->datePublished);

            if (!is_null($this->dateModified)) {
                $dateModified = \App\Utils\JsonLd::formatDate8601($this->dateModified);

                if ($dateModified != $ret['datePublished']) {
                    $ret['dateModified'] = $dateModified;
                }
            }
        }

        $authors = [];
        foreach ($this->getAuthors() as $author) {
            $authors[] = $author->jsonLdSerialize($locale, true);
        }

        if (count($authors) > 0) {
            $ret['author'] = (1 == count($authors)) ? $authors[0] : $authors;
        }

        $about = [
            '@type' => 'LandmarksOrHistoricalBuildings',
            'name' => $this->getName($locale),
        ];

        // TODO: move to App\Utils\JsonLd
        $geo = $this->getGeo();
        if (!(empty($geo) || false === strpos($geo, ','))) {
            [$lat, $long] = explode(',', $geo, 2);
            $about['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' =>  (float) $lat,
                'longitude' => (float) $long,
            ];
        }

        $address = [];

        if (!empty($this->streetAddress)) {
            $address['streetAddress'] = $this->streetAddress;
        };

        if (!empty($this->postalCode)) {
            $address['postalCode'] = $this->postalCode;
        }

        if (!empty($address)) {
            $about['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $this->locationLabel,
            ] + $address;
        }

        if (!is_null($this->location)) {
            $about['containedInPlace'] = $this->location->jsonLdSerialize($locale, true);
        }

        if (false) {
            foreach ([ 'start', 'end'] as $key) {
                $property = $key . 'date';
                if (!empty($this->$property)) {
                    $ret[$property . 'Time'] = \App\Utils\JsonLd::formatDate8601($this->$property);
                }
            }
        }

        foreach ([ 'url' ] as $property) {
            if (!empty($this->$property)) {
                $about[$property] = $this->$property;
            }
        }

        $ret['about'] = $about;

        $abstract = $this->getAbstractLocalized($locale);
        if (!empty($abstract)) {
            $ret['description'] = $abstract;
        }

        $description = $this->getDescriptionLocalized($locale);
        if (!empty($description)) {
            $ret['articleBody'] = $description;
        }

        if (!empty($this->license)) {
            switch ($this->license) {
                case '#public-domain':
                    $ret['license'] = 'https://creativecommons.org/publicdomain/mark/1.0/deed.' . $locale;
                    break;

                case 'CC BY-NC-ND':
                    $parts = explode(' ', $this->license, 2);
                    $ret['license'] = sprintf(
                        'https://creativecommons.org/licenses/%s/4.0/',
                        strtolower($parts[1])
                    );
                    break;

                default:
                    $ret['license'] = $this->license;
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
            $this->$name = $value;
        }
    }
}
