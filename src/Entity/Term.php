<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Term
 *
 * @ORM\Table(name="Term")
 * @ORM\Entity
 */
class Term
{
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
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=20, nullable=false)
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_parent", type="integer", nullable=true)
     */
    private $idParent;

    /**
     * @var integer
     *
     * @ORM\Column(name="ord", type="integer", nullable=false)
     */
    private $ord;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var array|null A localized title of the item.
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $title;

    /**
     * @var array|null A short description of the item.
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $created;

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
    private $changed;

    /**
     * @var integer
     *
     * @ORM\Column(name="changed_by", type="integer", nullable=true)
     */
    private $changedBy;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNameLocalized($locale)
    {
        if (!empty($this->title)) {
            if (is_array($this->title)) {
                if (array_key_exists($locale, $this->title)) {
                    return $this->title[$locale];
                }
            }
            else {
                return $this->title;
            }
        }

        return $this->getName();
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

    public function __toString()
    {
        return $this->getName();
    }
}
