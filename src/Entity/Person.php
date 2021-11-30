<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A person (alive, dead, undead, or fictional).
 *
 * @see https://schema.org/Person Documentation on Schema.org
 *
 * @ORM\Entity
 *
 */
class Person
extends Agent
implements \JsonSerializable /*, JsonLdSerializable, OgSerializable */
{
    use AddressesTrait;

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

    /**
     * @var string An additional name for a Person, can be used for a middle name.
     *
     */
    protected $additionalName;

    /**
     * @var string Denomination.
     *
     * @ORM\Column(nullable=true)
     */
    protected $denomination;

    /**
     * @var string Date of birth.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $birthDate;

    /**
     * @var string Cause of death.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $deathCause;

    /**
     * @var string Date of death.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $deathDate;

    /**
     * @var string Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property.
     *
     * @ORM\Column(name="family_name", nullable=true)
     */
    protected $familyName;

    /**
     * @var string Gender of the person.
     *
     * @ORM\Column(name="gender", nullable=true)
     */
    protected $gender;

    /**
     * @var string Given name. In the U.S., the first name of a Person. This can be used along with familyName instead of the name property.
     *
     * @ORM\Column(name="given_name", nullable=true)
     */
    protected $givenName;

    /**
     * @var string Nationality of the person.
     *
     * @ORM\Column(name="nationality", nullable=true)
     */
    protected $nationality;

    /**
     * @var Place The place where the person was born.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Place")
     * @ORM\JoinColumn(name="birth_place_id", referencedColumnName="id")
     */
    protected $birthPlace;

    /**
     * @var string Name of the birthPlace.
     *
     * @ORM\Column(nullable=true,name="birth_place")
     */
    protected $birthPlaceLabel;

    /**
     * @var Place The place where the person died.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Place")
     * @ORM\JoinColumn(name="death_place_id", referencedColumnName="id")
     */
    protected $deathPlace;

    /**
     * @var string Name of the deathplace.
     *
     * @ORM\Column(nullable=true,name="death_place")
     */
    protected $deathPlaceLabel;

    /**
     * @var
     *
     * xORM\Column(name="actionplace", type="json_array", nullable=true)
     */
    protected $addresses;

    /**
     * @var string
     *
     * @ORM\Column(name="honorific_prefix", nullable=true)
     */
    protected $honorificPrefix;

    /**
     * @var string
     *
     */
    protected $honorificSuffix;

    /**
     * Sets additionalName.
     *
     * @param string $additionalName
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
     * @return string
     */
    public function getAdditionalName()
    {
        return $this->additionalName;
    }

    /**
     * Sets denomination.
     *
     * @param string $denomination
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
     * @return string
     */
    public function getDenomination()
    {
        return $this->denomination;
    }

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
     * @param string $birthDate
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
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Sets deathDate.
     *
     * @param string $deathDate
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
     * @return string
     */
    public function getDeathDate()
    {
        return $this->deathDate;
    }

    /**
     * Gets deathCause.
     *
     * @return string
     */
    public function getDeathCause()
    {
        return $this->deathCause;
    }

    /**
     * Sets familyName.
     *
     * @param string $familyName
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
     * @return string
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * Sets gender.
     *
     * @param string $gender
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
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

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
     * @param string $givenName
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
     * @return string
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * Sets nationality.
     *
     * @param string $nationality
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
     * @return string
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * Sets birthPlace.
     *
     * @param Place $birthPlace
     *
     * @return $this
     */
    public function setBirthPlace(Place $birthPlace = null)
    {
        $this->birthPlace = $birthPlace;

        return $this;
    }

    /**
     * Gets birthPlace.
     *
     * @return Place
     */
    public function getBirthPlace()
    {
        return $this->birthPlace;
    }

    /**
     * Gets birthPlace.
     *
     * @return Place
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
    public function getAddressesSeparated($filterExhibition = null, $linkPlace = false, $returnStructure = false)
    {
        $addresses = $this->buildAddresses($this->addresses, false, $filterExhibition, $linkPlace, $returnStructure);

        if (!$returnStructure) {
            // lookup exhibitions
            for ($i = 0; $i < count($addresses); $i++) {
                $addresses[$i]['exhibitions'] = !empty($addresses[$i]['id_exhibitions'])
                    ? $this->getExhibitions(-1, $addresses[$i]['id_exhibitions'])
                    : [];
            }
        }

        return $addresses;
    }

    /**
     * Gets birthPlace info
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
    }

    /**
     * Sets deathPlace.
     *
     * @param Place $deathPlace
     *
     * @return $this
     */
    public function setDeathPlace(Place $deathPlace = null)
    {
        $this->deathPlace = $deathPlace;

        return $this;
    }

    /**
     * Gets deathPlace.
     *
     * @return Place
     */
    public function getDeathPlace()
    {
        return $this->deathPlace;
    }

    /**
     * Gets deathPlace.
     *
     * @return string
     */
    public function getDeathPlaceLabel()
    {
        return $this->deathPlaceLabel;
    }

    /**
     * Gets deathPlace info
     *
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
    }

    /**
     * Sets honorificPrefix.
     *
     * @param string $honorificPrefix
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
     * @return string
     */
    public function getHonorificPrefix()
    {
        return $this->honorificPrefix;
    }

    /**
     * Sets honorificSuffix.
     *
     * @param string $honorificSuffix
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
     * @return string
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
     * We prefer person-by-ulan
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

    public function jsonLdSerialize($locale, $omitContext = false)
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

        $description = $this->getDescriptionLocalized($locale);
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
     */
    public function ogSerialize($locale, $baseUrl)
    {
        $ret = [
            'og:type' => 'profile',
            'og:title' => $this->getFullname(true),
        ];

        $parts = [];

        $description = $this->getDescriptionLocalized($locale);
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
