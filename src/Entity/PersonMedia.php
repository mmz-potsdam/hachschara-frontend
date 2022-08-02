<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonMedia
 *
 * @ORM\Entity
 */
class PersonMedia
extends Media
{
    /**
     * @var integer
     */
    protected $type = 10; // $GLOBALS['TYPE_PERSON'] - must match DiscriminatorMap

    /**
     *
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="media", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
     */
    protected $person;

    public function getPathPrefix()
    {
        return 'person';
    }

    public function getReferencedId()
    {
        return $this->person->getId();
    }
}
