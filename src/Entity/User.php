<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A user.
 *
 * @ORM\Entity
 * @ORM\Table(name="User")
 */
class User
implements \JsonSerializable /*, JsonLdSerializable, OgSerializable */
{
    use HasTranslationsTrait;

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
     * @var string Additional name forms.
     *
     * xORM\Column(name="alternate_name", nullable=true)
     */
    protected $alternateName;

    /**
     * @var string A short description of the item.
     *
     * @ORM\Column(name="description", type="string", length="4096", nullable=true)
     *
     */
    protected $description;

    /**
     * @var string A description of the item.
     *
     * xORM\Column(name="disambiguating_description", type="string", nullable=true)
     *
     */
    protected $disambiguatingDescription;

    /**
     * @var string
     * xORM\Column(type="string", nullable=true)
     */
    protected $ulan;

    /**
     * @var string
     * @ORM\Column(name="gnd",type="string", nullable=true)
     */
    protected $gnd;

    /**
     * @var string
     * xORM\Column(type="string", nullable=true)
     */
    protected $viaf;

    /**
     * @var string
     * xORM\Column(type="string", nullable=true)
     */
    protected $wikidata;

    /**
    * xORM\Column(type="json", nullable=true)
    */
    protected $entityfacts;

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
     * @return string|null
     */
    public function getDescription($locale = null)
    {
        return $this->getTranslatedProperty('description', $locale);
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

        /*
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
        */

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
}
