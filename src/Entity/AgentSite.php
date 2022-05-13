<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="AgentProject")
 */
class AgentSite
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Agent", inversedBy="siteReferences")
     * @ORM\JoinColumn(name="id_agent", referencedColumnName="id", nullable=FALSE)
     */
    protected $agent;

    /**
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="agentReferences")
     * @ORM\JoinColumn(name="id_project", referencedColumnName="id", nullable=FALSE)
     */
    protected $site;

    /**
     * @ORM\Column(type="integer")
     */
    protected $ord;

    /**
     * @var Term The role.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Term")
     * @ORM\JoinColumn(name="role", referencedColumnName="id")
     */
    protected $role;

    public function setAgent(Agent $agent)
    {
        $this->agent = $agent;

        return $this;
    }

    public function getAgent()
    {
        return $this->agent;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function getRole()
    {
        return $this->role;
    }
}
