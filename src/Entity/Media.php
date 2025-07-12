<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Media
 *
 */
#[ORM\Table(name: 'Media')]
#[ORM\Index(name: 'MediaItemName', columns: ['item_id', 'type', 'name'])]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'integer')]
#[ORM\DiscriminatorMap(['0' => 'SiteMedia', '10' => 'PersonMedia'])]
abstract class Media
{
    use HasTranslationsTrait;

    static $MEDIA_EXTENSIONS = [
        'image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/png' => '.png',
        'application/pdf' => '.pdf',
    ];

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'flags', type: 'integer', nullable: true)]
    private $flags;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'name', type: 'string', length: 20, nullable: false)]
    private $name;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'mimetype', type: 'string', length: 80, nullable: false)]
    private $mimetype;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'width', type: 'integer', nullable: true)]
    private $width;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'height', type: 'integer', nullable: true)]
    private $height;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    private $duration;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'ord', type: 'integer', nullable: true)]
    private $ord;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'caption', type: 'string', length: 1023, nullable: true)]
    private $caption;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'descr', type: 'text', nullable: true)]
    private $descr;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'copyright', type: 'string', length: 1023, nullable: true)]
    private $copyright;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'source', type: 'string', length: 1023, nullable: true)]
    private $source;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'displaydate', type: 'string', length: 127, nullable: true)]
    private $displaydate;

    /**
     * @var \DateTime
     *
     */
    #[ORM\Column(name: 'created', type: 'datetime', nullable: true)]
    private $created;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'created_by', type: 'integer', nullable: true)]
    private $createdBy;

    /**
     * @var \DateTime
     *
     */
    #[ORM\Column(name: 'changed', type: 'datetime', nullable: true)]
    private $changed;

    /**
     * @var integer
     *
     */
    #[ORM\Column(name: 'changed_by', type: 'integer', nullable: true)]
    private $changedBy;

    public function getName()
    {
        return $this->name;
    }

    public function getStatus()
    {
        return $this->flags & 0x01;
    }

    public function getCaption($locale = null)
    {
        $parts = [];

        $caption = $this->getTranslatedProperty('caption', $locale);

        if (!empty($caption)) {
            $parts[] = $caption;
        }

        if (!empty($this->source) || !empty($this->displaydate)) {
            $subparts = [];

            if (!empty($this->source)) {
                $subparts[] = $this->source;
            }

            if (!empty($this->displaydate)) {
                $subparts[] = $this->displaydate;
            }

            $sourceDate = join(', ', $subparts);

            if (count($parts) > 0) {
                $parts[0] .= ' (' . $sourceDate . ')';
            }
            else {
                $parts[] = $sourceDate;
            }
        }

        if (!empty($this->copyright)) {
            $parts[] = html_entity_decode('&#169; ', ENT_NOQUOTES, 'UTF-8') . $this->copyright;
        }

        if (!empty($parts)) {
            return implode("\n", $parts);
        }
    }

    abstract public function getReferencedId();
    abstract public function getPathPrefix();

    public function getPath()
    {
        $id = $this->getReferencedId();

        return sprintf(
            '%s.%03d/id%05d',
            $this->getPathPrefix(),
            intval($id / 32768),
            $id % 32768
        );
    }

    public function getFilename($variant = '')
    {
        return sprintf(
            '%s%s',
            $this->name . ('' !== $variant ? '_' . $variant : ''),
            self::$MEDIA_EXTENSIONS[$this->mimetype]
        );
    }

    public function getImgUrl($variant = '')
    {
        return implode('/', [
            $this->getPath(),
            $this->getFilename($variant),
        ]);
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
