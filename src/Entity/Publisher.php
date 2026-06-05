<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Publisher
 * TODO: extend from Organization: An organization such as a school, NGO, corporation, club, etc.
 *
 * @see http://schema.org/Organization Documentation on Schema.org
 *
 */
#[ORM\Entity]
#[ORM\Table(name: 'Publisher')]
class Publisher implements \JsonSerializable, JsonLdSerializable
{
    public static function formatDateIncomplete($dateStr)
    {
        if (preg_match('/^\d{4}$/', $dateStr)) {
            $dateStr .= '-00-00';
        }
        else if (preg_match('/^\d{4}\-\d{2}$/', $dateStr)) {
            $dateStr .= '-00';
        }
        else if (preg_match('/^(\d+)\.(\d+)\.(\d{4})$/', $dateStr, $matches)) {
            $dateStr = join('-', [ $matches[3], $matches[2], $matches[1] ]);
        }

        return $dateStr;
    }

    public static function stripAt($name)
    {
        return preg_replace('/(\s+)@/', '\1', $name);
    }

    /**
     * @var int
     *
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer', nullable: false)]
    protected $status = 0;

    /**
     * @var string|null The name of the item.
     */
    #[Assert\Type(type: 'string')]
    #[ORM\Column(nullable: true)]
    protected $name;

    /**
     * @var string|null URL of the item.
     */
    #[Assert\Url]
    #[ORM\Column(nullable: true)]
    protected $url;

    /**
     * @var string|null The GND identifier of the item.
     */
    protected $gnd;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'created', type: 'datetime')]
    protected $createdAt;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'changed', type: 'datetime')]
    protected $changedAt;

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
     * Gets localized name.
     *
     * @return string|null
     */
    public function getNameLocalized($locale)
    {
        return $this->getName();
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
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'gnd' => $this->gnd,
        ];
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
            '@type' => 'Organization',
            'name' => $this->getNameLocalized($locale),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        foreach ([ 'url' ] as $property) {
            if (!empty($this->$property)) {
                $ret[$property] = $this->$property;

            }
        }

        if (!empty($this->gnd)) {
            $ret['sameAs'] = 'https://d-nb.info/gnd/' . $this->gnd;
        }

        return $ret;
    }
}
