<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A organization.
 *
 * @see https://schema.org/Organization Documentation on Schema.org
 *
 *
 */
#[ORM\Entity]
class Organization extends Agent implements \JsonSerializable /*, JsonLdSerializable, OgSerializable */
{
    use AddressesTrait;

    use InfoTrait;

    protected $info = [];
    protected $extractFromNotes = [ 'name', 'birth_death' ];

    /**
     * @var string Date of birth.
     *
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $foundingDate;

    /**
     * @var string Date of death.
     *
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $dissolutionDate;

    /**
     * @var string name.
     *
     */
    #[ORM\Column(name: 'name', nullable: true)]
    protected $name;

    /**
     * @var Place The place where the organization was founded.
     *
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Place')]
    #[ORM\JoinColumn(name: 'founding_location_id', referencedColumnName: 'id')]
    protected $foundingLocation;

    /**
     * @var string Name of the foundingLocation.
     *
     */
    #[ORM\Column(nullable: true, name: 'founding_location')]
    protected $foundingLocationLabel;

    /**
     * @var
     *
     * xORM\Column(name="actionplace", type="json", nullable=true)
     */
    protected $addresses;

    /**
     * Sets foundingDate.
     *
     * @param string|null $foundingDate
     *
     * @return $this
     */
    public function setFoundingDate($foundingDate = null)
    {
        $this->foundingDate = self::formatDateIncomplete($foundingDate);

        return $this;
    }

    /**
     * Gets foundingDate.
     *
     * @return string|null
     */
    public function getFoundingDate()
    {
        return $this->foundingDate;
    }

    /**
     * Sets dissolutionDate.
     *
     * @param string|null $dissolutionDate
     *
     * @return $this
     */
    public function setDissolutionDate($dissolutionDate = null)
    {
        $this->dissolutionDate = self::formatDateIncomplete($dissolutionDate);

        return $this;
    }

    /**
     * Gets dissolutionDate.
     *
     * @return string|null
     */
    public function getDissolutionDate()
    {
        return $this->dissolutionDate;
    }

    /**
     * Sets name.
     *
     * @param string $name
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
    public function getName($locale = null)
    {
        return $this->getTranslatedProperty('name', $locale);
    }

    /**
     * Sets foundingLocation.
     *
     * @param Place|null $foundingLocation
     *
     * @return $this
     */
    public function setFoundingLocation(?Place $foundingLocation = null)
    {
        $this->foundingLocation = $foundingLocation;

        return $this;
    }

    /**
     * Gets foundingLocation.
     *
     * @return Place|null
     */
    public function getFoundingLocation()
    {
        return $this->foundingLocation;
    }

    /**
     * Gets foundingLocationLabel.
     *
     * @return string|null
     */
    public function getFoundingLocationLabel()
    {
        return $this->foundingLocationLabel;
    }

    /**
     * Gets indivdual addresses.
     *
     * @return array
     */
    public function getAddressesSeparated($linkPlace = false, $returnStructure = false)
    {
        $addresses = $this->buildAddresses($this->addresses, false, $linkPlace, $returnStructure);

        return $addresses;
    }

    /**
     * Gets foundingLocation info
     *
     */
    public function getFoundingLocationInfo($locale = 'en')
    {
        if (!is_null($this->foundingLocation)) {
            return self::buildPlaceInfo($this->foundingLocation, $locale);
        }

        $placeInfo = self::buildPlaceInfoFromEntityfacts($this->getEntityfacts($locale), 'placeOfBirth');
        if (!empty($placeInfo)) {
            return $placeInfo;
        }

        if (!empty($this->foundingLocationLabel)) {
            return [
                'name' => $this->foundingLocationLabel,
            ];
        }
    }

    /**
     * We prefer organization-by-gnd
     */
    public function getRouteInfo()
    {
        $route = 'organization';
        $routeParams = [ 'id' => $this->id ];

        foreach ([ 'gnd', 'ulan' ] as $key) {
            if (!empty($this->$key)) {
                $route = 'organization-by-' . $key;
                $routeParams = [ $key => $this->$key ];
                break;
            }
        }

        return [ $route, $routeParams ];
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'gnd' => $this->gnd,
            'slug' => $this->slug,
        ];
    }

    public function jsonLdSerialize($locale, $omitContext = false)
    {
        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'Organization',
            'name' => $this->getName($locale),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        foreach ([ 'founding', 'dissolution'] as $lifespan) {
            $property = $lifespan . 'Date';
            if (!empty($this->$property)) {
                $ret[$property] = \App\Utils\JsonLd::formatDate8601($this->$property);
            }

            $property = $lifespan . 'Location';
            if (property_exists($this, $property) && !is_null($this->$property)) {
                $ret[$property] = $this->$property->jsonLdSerialize($locale, true);
            }
        }

        $description = $this->getDescription($locale);
        if (!empty($description)) {
            $ret['description'] = $description;
        }

        foreach ([ 'url' ] as $property) {
            if (!empty($this->$property)) {
                $ret[$property] = $this->$property;
            }
        }

        $sameAs = [];
        if (!empty($this->ulan)) {
            $sameAs[] = 'http://vocab.getty.edu/ulan/' . $this->ulan;
        }

        if (!empty($this->gnd)) {
            $sameAs[] = 'https://d-nb.info/gnd/' . $this->gnd;
        }

        if (!empty($this->wikidata)) {
            $sameAs[] = 'http://www.wikidata.org/entity/' . $this->wikidata;
        }

        if (count($sameAs) > 0) {
            $ret['sameAs'] = (1 == count($sameAs)) ? $sameAs[0] : $sameAs;
        }

        return $ret;
    }

    /**
     * See https://developers.facebook.com/docs/reference/opengraph/object-type/profile/
     *
     */
    public function ogSerialize($locale, $baseUrl)
    {
        $ret = [
            'og:type' => 'profile',
            'og:title' => $this->getName($locale),
        ];

        $parts = [];

        $description = $this->getDescription($locale);
        if (!empty($description)) {
            $parts[] = $description;
        }

        $datesOfExistence = '';
        if (!empty($this->foundingDate)) {
            $datesOfExistence = \App\Utils\Formatter::dateIncomplete($this->foundingDate, $locale);
        }

        if (!empty($this->dissolutionDate)) {
            $datesOfExistence .= ' - ' . \App\Utils\Formatter::dateIncomplete($this->dissolutionDate, $locale);
        }

        if (!empty($datesOfExistence)) {
            $parts[] = '[' . $datesOfExistence . ']';
        }

        if (!empty($parts)) {
            $ret['og:description'] = join(' ', $parts);
        }

        // TODO: maybe get og:image
        return $ret;
    }
}
