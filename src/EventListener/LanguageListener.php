<?php

/*
 * Show 404 in domain-dependant localization
 *
 * see http://donna-oberes.blogspot.de/2014/01/symfony-internalizationlocalization-and.html
 *
 * though we use the logic from
 * https://github.com/schmittjoh/JMSI18nRoutingBundle/blob/master/EventListener/LocaleChoosingListener.php
 *
 * register the listener in services.yml
 * services:
 *   # ...
 *
 *  # language-specific layout in 404
 *  App\EventListener\LanguageListener:
 *      arguments: [ '%app.default_locale%', '%app.supported_locales%' ]
 *      tags:
 *         - { name: kernel.event_listener, event: kernel.exception, method: setLocale }
 *
 */

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class LanguageListener
{
    protected $defaultLocale;
    protected $locales;

    public function __construct($defaultLocale, array $locales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->locales = $locales;
    }

    public function setLocale(RequestEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        $locale = $this->defaultLocale;

        $pathInfo = $request->getPathInfo();

        // the following works with controllers.prefix,
        // but not with localized domain in conntrollers.host
        foreach ($this->locales as $localeCandidate) {
            if ($localeCandidate == $this->defaultLocale) {
                continue;
            }

            $needle = '/' . $localeCandidate . '/';
            if (strncmp($pathInfo, $needle, strlen($needle)) === 0) {
                $locale = $localeCandidate;
                break;
            }
        }

        $request->setLocale($locale);
    }
}
