<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A person (alive, dead, undead, or fictional).
 *
 * @see https://schema.org/Person Documentation on Schema.org
 *
 *
 */
#[ORM\Entity]
class Person extends Agent implements \JsonSerializable, JsonLdSerializable /*, OgSerializable */
{
    use AddressesTrait;

    use InfoTrait;

    const FLAGS_PRIMARY_PERSON = 0x1000;
    const FLAGS_SECONDARY_PERSON = 0x4000;
    const FLAGS_NSVICTIM = 0x2000;

    static $genderMap = [ 'F' => 'female', 'M' => 'male' ]; /* for og:serialize */
    static $denominationMap = [
        'jd' => 'jüdisch',
        'ev' => 'evangelisch',
        'un' => 'evangelisch-uniert',
        'lu' => 'lutherisch',
        're' => 'reformiert',
        'rk' => 'römisch-katholisch',
        'ob' => 'ohne Bekenntnis',

        'ak' => 'alt-katholisch',
        'ce' => 'anglikanisch',
        'ap' => 'apostolisch',
        'ba' => 'baptistisch',
        'bd' => 'buddhistisch',
        'ca' => 'calvinistisch',
        // 'di' => 'Dissident',
        'lt' => 'evangelisch-augsburgisch',
        'fr' => 'französisch-reformiert',
        'go' => 'griechisch-orthodox',
        'ht' => 'Heilige der letzten Tage',
        'is' => 'islamisch',
        'ka' => 'katholisch',
        'me' => 'mennonitisch',
        'mt' => 'methodistisch',
        'na' => 'neuapostolisch',
        'ox' => 'orthodox',
        'pr' => 'presbyterianisch',
        'gf' => 'Quäker',
        'ro' => 'russisch-orthodox',
        'so' => 'sonstige',
        'wr' => 'wallonisch-refomiert',
        'zj' => 'Zeugen Jehovas',
    ];

    static function splitDenomination($entry)
    {
        $ret = [];
        if (!isset($entry)) {
            return $ret;
        }

        $lines = preg_split('/\n/', $entry);
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (!preg_match('/\S/', $line)) {
                continue;
            }

            $parts = preg_split('/\t/', $line, 3);
            $name = '';
            if (1 == count($parts)) {
                $from = $until = '';
                $code = $parts[0];
            }
            else {
                $code = $parts[1];
                $from_until = preg_split('/\-/', $parts[0], 2);
                $from = $from_until[0];
                $until = count($from_until) > 1 ? $from_until[1] : '';
                if (3 == count($parts) && preg_match($regexp = '/\[(.*?)\]\s*$/', $parts[2], $matches)) {
                    $name = $matches[1];
                }
            }

            $idx = empty($ret) ? 0 : count($ret['code']);
            $ret['from'][$idx] = $from;
            $ret['until'][$idx] = $until;
            $ret['code'][$idx] = $code;
            $ret['name'][$idx] = $name;
        }

        return $ret;
    }

    protected $info = [];
    protected $extractFromNotes = [ 'name', 'birth_death' ];

    /**
     * @var string|null An additional name for a Person.
     */
    protected $additionalName;

    /**
     * @var string|null Denomination.
     *
     */
    #[ORM\Column(nullable: true)]
    protected $denomination;

    /**
     * @var string|null Date of birth.
     *
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $birthDate;

    /**
     * @var string|null Cause of death.
     *
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $deathCause;

    /**
     * @var string|null Date of death.
     *
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $deathDate;

    /**
     * @var string|null Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property.
     *
     */
    #[ORM\Column(name: 'family_name', nullable: true)]
    protected $familyName;

    /**
     * @var string|null Gender of the person.
     *
     */
    #[ORM\Column(name: 'gender', nullable: true)]
    protected $gender;

    /**
     * @var string|null Given name. In the U.S., the first name of a Person. This can be used along with familyName instead of the name property.
     *
     */
    #[ORM\Column(name: 'given_name', nullable: true)]
    protected $givenName;

    /**
     * @var string|null Nationality of the person.
     *
     */
    #[ORM\Column(name: 'nationality', nullable: true)]
    protected $nationality;

    /**
     * @var Place|null The place where the person was born.
     *
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Place')]
    #[ORM\JoinColumn(name: 'birth_place_id', referencedColumnName: 'id')]
    protected $birthPlace;

    /**
     * @var string|null Name of the birthPlace.
     *
     */
    #[ORM\Column(nullable: true, name: 'birth_place')]
    protected $birthPlaceLabel;

    /**
     * @var Place|null The place where the person died.
     *
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Place')]
    #[ORM\JoinColumn(name: 'death_place_id', referencedColumnName: 'id')]
    protected $deathPlace;

    /**
     * @var string|null Name of the deathPlace.
     *
     */
    #[ORM\Column(nullable: true, name: 'death_place')]
    protected $deathPlaceLabel;

    /**
     * @var array|null The addresses of the person.
     */
    protected $addresses;

    /**
     * @var string|null An honorific prefix preceding a Person's name such as Dr.
     *
     */
    #[ORM\Column(name: 'honorific_prefix', nullable: true)]
    protected $honorificPrefix;

    /**
     * @var string|null An honorific suffix following a Person's name such as M.A.
     */
    protected $honorificSuffix;

    #[ORM\OneToMany(targetEntity: 'PersonMedia', mappedBy: 'person', fetch: 'EAGER')]
    #[ORM\OrderBy(['name' => 'ASC', 'ord' => 'ASC'])]
    protected $media;

    /**
     * Sets additionalName.
     *
     * @param string|null $additionalName
     *
     * @return $this
     */
    public function setAdditionalName($additionalName)
    {
        $this->additionalName = $additionalName;

        return $this;
    }

    /**
     * Gets additionalName.
     *
     * @return string|null
     */
    public function getAdditionalName()
    {
        return $this->additionalName;
    }

    /**
     * Sets denomination.
     *
     * @param string|null $denomination
     *
     * @return $this
     */
    public function setDenomination($denomination)
    {
        $this->denomination = $denomination;

        return $this;
    }

    /**
     * Gets denomination.
     *
     * @return string|null
     */
    public function getDenomination()
    {
        return $this->denomination;
    }

    /**
     * Gets multiple denomination entries as separate lines, with from-until and code expanded.
     *
     * @return string
     */
    public function getDenominationExpanded()
    {
        $lines = [];

        $entries = self::splitDenomination($this->denomination);

        if (!empty($entries)) {
            $count = count($entries['code']);

            for ($i = 0; $i < $count; $i++) {
                $parts = [];
                $from = isset($entries['from'][$i]) ? trim($entries['from'][$i]) : '';
                $until = isset($entries['until'][$i]) ? trim($entries['until'][$i]) : '';

                if ('' !== $from || '' != $until) {
                    $parts[] = join('-', [ $from, $until ]);
                }

                $code = $entries['code'][$i];
                if (array_key_exists($code, self::$denominationMap)) {
                    $code = self::$denominationMap[$code];
                }

                $parts[] = $code;

                $lines[] = join(' ', $parts);
            }
        }

        return join("\n", $lines);
    }

    /**
     * Sets birthDate.
     *
     * @param string|null $birthDate
     *
     * @return $this
     */
    public function setBirthDate($birthDate = null)
    {
        $this->birthDate = self::formatDateIncomplete($birthDate);

        return $this;
    }

    /**
     * Gets birthDate.
     *
     * @return string|null
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Sets deathDate.
     *
     * @param string|null $deathDate
     *
     * @return $this
     */
    public function setDeathDate($deathDate = null)
    {
        $this->deathDate = self::formatDateIncomplete($deathDate);

        return $this;
    }

    /**
     * Gets deathDate.
     *
     * @return string|null
     */
    public function getDeathDate()
    {
        if (is_null($this->deathDate) || '0000-00-00' == $this->deathDate) {
            return null;
        }

        return $this->deathDate;
    }

    /**
     * Gets deathCause.
     *
     * @return string|null
     */
    public function getDeathCause()
    {
        return $this->deathCause;
    }

    /**
     * Sets familyName.
     *
     * @param string|null $familyName
     *
     * @return $this
     */
    public function setFamilyName($familyName)
    {
        $this->familyName = $familyName;

        return $this;
    }

    /**
     * Gets familyName.
     *
     * @return string|null
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * Sets gender.
     *
     * @param string|null $gender
     *
     * @return $this
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Gets gender.
     *
     * @return string|null
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Gets expanded gender label.
     *
     * @return string|null
     */
    public function getGenderLabel()
    {
        $ret = $this->gender;

        if (!is_null($ret) && array_key_exists($ret, self::$genderMap)) {
            $ret = self::$genderMap[$ret];
        }

        return $ret;
    }

    /**
     * Sets givenName.
     *
     * @param string|null $givenName
     *
     * @return $this
     */
    public function setGivenName($givenName)
    {
        $this->givenName = $givenName;

        return $this;
    }

    /**
     * Gets givenName.
     *
     * @return string|null
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * Sets nationality.
     *
     * @param string|null $nationality
     *
     * @return $this
     */
    public function setNationality($nationality)
    {
        $this->nationality = $nationality;

        return $this;
    }

    /**
     * Gets nationality.
     *
     * @return string|null
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * Sets birthPlace.
     *
     * @param Place|null $birthPlace
     *
     * @return $this
     */
    public function setBirthPlace(?Place $birthPlace = null)
    {
        $this->birthPlace = $birthPlace;

        return $this;
    }

    /**
     * Gets birthPlace.
     *
     * @return Place|null
     */
    public function getBirthPlace()
    {
        return $this->birthPlace;
    }

    /**
     * Gets name of birthPlace.
     *
     * @return string|null
     */
    public function getBirthPlaceLabel()
    {
        return $this->birthPlaceLabel;
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
     * Gets birthPlace info.
     *
     * return array|null
     *
     */
    public function getBirthPlaceInfo($locale = 'en')
    {
        if (!is_null($this->birthPlace)) {
            return self::buildPlaceInfo($this->birthPlace, $locale);
        }

        $placeInfo = self::buildPlaceInfoFromEntityfacts($this->getEntityfacts($locale), 'placeOfBirth');
        if (!empty($placeInfo)) {
            return $placeInfo;
        }

        if (!empty($this->birthPlaceLabel)) {
            return [
                'name' => $this->birthPlaceLabel,
            ];
        }

        return null;
    }

    /**
     * Sets deathPlace.
     *
     * @param Place|null $deathPlace
     *
     * @return $this
     */
    public function setDeathPlace(?Place $deathPlace = null)
    {
        $this->deathPlace = $deathPlace;

        return $this;
    }

    /**
     * Gets deathPlace.
     *
     * @return Place|null
     */
    public function getDeathPlace()
    {
        return $this->deathPlace;
    }

    /**
     * Gets name of deathPlace.
     *
     * @return string|null
     */
    public function getDeathPlaceLabel()
    {
        return $this->deathPlaceLabel;
    }

    /**
     * Gets deathPlace info
     *
     * @return array|null
     */
    public function getDeathPlaceInfo($locale = 'en')
    {
        if (!is_null($this->deathPlace)) {
            return self::buildPlaceInfo($this->deathPlace, $locale);
        }

        $placeInfo = self::buildPlaceInfoFromEntityfacts($this->getEntityfacts($locale), 'placeOfDeath');
        if (!empty($placeInfo)) {
            return $placeInfo;
        }

        if (!empty($this->deathPlaceLabel)) {
            return [
                'name' => $this->deathPlaceLabel,
            ];
        }

        return null;
    }

    /**
     * Sets honorificPrefix.
     *
     * @param string|null $honorificPrefix
     *
     * @return $this
     */
    public function setHonorificPrefix($honorificPrefix)
    {
        $this->honorificPrefix = $honorificPrefix;

        return $this;
    }

    /**
     * Gets honorificPrefix.
     *
     * @return string|null
     */
    public function getHonorificPrefix()
    {
        return $this->honorificPrefix;
    }

    /**
     * Sets honorificSuffix.
     *
     * @param string|null $honorificSuffix
     *
     * @return $this
     */
    public function setHonorificSuffix($honorificSuffix)
    {
        $this->honorificSuffix = $honorificSuffix;

        return $this;
    }

    /**
     * Gets honorificSuffix.
     *
     * @return string|null
     */
    public function getHonorificSuffix()
    {
        return $this->honorificSuffix;
    }

    /**
     * Returns
     *  familyName, givenName
     * or
     *  givenName familyName
     * depending on $givenNameFirst
     *
     * @return string
     */
    public function getFullname($givenNameFirst = false)
    {
        $parts = [];
        foreach ([ 'familyName', 'givenName' ] as $key) {
            if (!empty($this->$key)) {
                $parts[] = $this->$key;
            }
        }

        if (empty($parts)) {
            return '';
        }

        return $givenNameFirst
            ? implode(' ', array_reverse($parts))
            : implode(', ', $parts);
    }

    /**
     * Returns PersonMedia
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
     * Build route name and route parameters, preferring person-by-gnd
     *
     * return array
     */
    public function getRouteInfo()
    {
        $route = 'person';
        $routeParams = [ 'id' => $this->id ];

        foreach ([ 'ulan', 'gnd' ] as $key) {
            if (!empty($this->$key)) {
                $route = 'person-by-' . $key;
                $routeParams = [ $key => $this->$key ];
                break;
            }
        }

        return [ $route, $routeParams ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'fullname' => $this->getFullname(),
            'honorificPrefix' => $this->getHonorificPrefix(),
            'description' => $this->getDescription(),
            'gender' => $this->getGender(),
            'gnd' => $this->gnd,
            'slug' => $this->slug,
        ];
    }

    /**
     * Serializes entity according to Schema.org.
     *
     * @return array
     */
    public function jsonLdSerialize($locale, $omitContext = false): array
    {
        static $genderMap = [
            'F' => 'http://schema.org/Female',
            'M' => 'http://schema.org/Male',
        ];

        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'Person',
            'name' => $this->getFullname(true),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        foreach ([ 'birth', 'death'] as $lifespan) {
            $property = $lifespan . 'Date';
            if (!empty($this->$property)) {
                $ret[$property] = \App\Utils\JsonLd::formatDate8601($this->$property);
            }

            $property = $lifespan . 'Place';
            if (!is_null($this->$property)) {
                $ret[$property] = $this->$property->jsonLdSerialize($locale, true);
            }
        }

        $description = $this->getDescription($locale);
        if (!empty($description)) {
            $ret['description'] = $description;
        }

        foreach ([ 'givenName', 'familyName', 'url' ] as $property) {
            if (!empty($this->$property)) {
                $ret[$property] = $this->$property;
            }
        }

        if (!empty($this->honorificPrefix)) {
            $ret['honorificPrefix'] = $this->honorificPrefix;
        }

        if (!is_null($this->gender) && array_key_exists($this->gender, $genderMap)) {
            $ret['gender'] = $genderMap[$this->gender];
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
     * return array
     */
    public function ogSerialize($locale, $baseUrl): array
    {
        $ret = [
            'og:type' => 'profile',
            'og:title' => $this->getFullname(true),
        ];

        $parts = [];

        $description = $this->getDescription($locale);
        if (!empty($description)) {
            $parts[] = $description;
        }

        $datesOfLiving = '';
        if (!empty($this->birthDate)) {
            $datesOfLiving = \App\Utils\Formatter::dateIncomplete($this->birthDate, $locale);
        }

        if (!empty($this->deathDate)) {
            $datesOfLiving .= ' - ' . \App\Utils\Formatter::dateIncomplete($this->deathDate, $locale);
        }

        if (!empty($datesOfLiving)) {
            $parts[] = '[' . $datesOfLiving . ']';
        }

        if (!empty($parts)) {
            $ret['og:description'] = join(' ', $parts);
        }

        // TODO: maybe get og:image

        if (!empty($this->givenName)) {
            $ret['profile:first_name'] = $this->givenName;
        }

        if (!empty($this->familyName)) {
            $ret['profile:last_name'] = $this->familyName;
        }

        if (!is_null($this->gender) && array_key_exists($this->gender, self::$genderMap)) {
            $ret['profile:gender'] = self::$genderMap[$this->gender];
        }

        return $ret;
    }
}
