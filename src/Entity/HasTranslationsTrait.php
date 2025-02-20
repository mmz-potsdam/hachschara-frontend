<?php

/**
 * Lookup properties in translations property
 */

namespace App\Entity;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping as ORM;

trait HasTranslationsTrait
{
    public static $defaultLocale = 'de';

    /**
     * @var array
     *
     */
    #[ORM\Column(type: 'json', nullable: true)]
    protected $translations;

    /**
     * Helper to return translated property from $this->translations if available
     */
    protected function getTranslatedProperty($name, $locale = null, $fallback = true)
    {
        if (!is_null($locale)) {
            $inflector = InflectorFactory::create()->build();
            $key = $inflector->tableize($name); // we use _ and not camelCase

            if (!is_null($this->translations)
                && array_key_exists($locale, $this->translations)
                && array_key_exists($key, $this->translations[$locale])) {
                return $this->translations[$locale][$key];
            }

            if ($locale != self::$defaultLocale && !$fallback) {
                return;
            }
        }

        return $this->$name;
    }
}
