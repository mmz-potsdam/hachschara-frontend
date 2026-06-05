<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* The Country
*/
#[ORM\Entity]
#[ORM\Table(name: 'Country')]
class Country
{
    /**
     * @var string The ISO 3166-1 alpha-2 country code.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'cc', type: 'string', nullable: false)]
    protected $countryCode;

    /**
     * @var string The name of the country.
     */
    #[ORM\Column(type: 'string', nullable: false)]
    protected $name;

    /**
    */
    protected $geonames;

    /**
     * Sets countryCode (iso2).
     *
     * @param string $countryCode
     *
     * @return $this
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Gets countryCode (iso2).
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
