<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PersonMedia
 *
 * @ORM\Entity
 */
class SiteMedia
extends Media
{
    /**
     * @var integer
     */
    protected $type = 0; // $GLOBALS['TYPE_PROJECT'] - must match DiscriminatorMap

    /**
     *
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="media", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
     */
    protected $site;

    public function getPathPrefix()
    {
        return 'project';
    }

    public function getReferencedId()
    {
        return $this->site->getId();
    }
}
