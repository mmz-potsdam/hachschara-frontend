<?php

namespace App\EventListener;

/**
 * See https://github.com/prestaconcept/PrestaSitemapBundle/blob/master/Resources/doc/4-dynamic-routes-usage.md
 */

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Twig\AppExtension;

use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class SitemapSubscriber
implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AppExtension
     */
    private $twigAppExtension;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     */
    public function __construct(EntityManagerInterface $entityManager,
                                AppExtension $twigAppExtension,
                                RouterInterface $router,
                                TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->twigAppExtension = $twigAppExtension;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'populate',
        ];
    }

    /**
     * Build url in primary and alternate language and add it to sitemap
     */
    private function addUrl(UrlContainerInterface $urls, $volume, $resource = null)
    {
        $route = 'dynamic';
        $params = [
            'path' => $this->twigAppExtension->buildResourcePath(!is_null($resource) ? $resource : $volume),
        ];

        $url = $this->router->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);

        $datestamp = is_null($resource)
            ? $volume->getDatestamp() : $resource->getDatestamp();
        if (is_null($datestamp)) {
            $datestamp = new \DateTime();
        }

        $sitemapUrl = new UrlConcrete($url, $datestamp);

        $alternate = $this->buildLocaleSwitch($volume, $resource);

        if (!empty($alternate['route_params_locale_switch'])) {
            $sitemapUrl = new \Presta\SitemapBundle\Sitemap\Url\GoogleMultilangUrlDecorator($sitemapUrl);

            // add decorations for alternate language versions
            foreach ($alternate['route_params_locale_switch'] as $altLocale => $params) {
                $altUrl = $this->router->generate($route, $params + [ '_locale' => $altLocale ], UrlGeneratorInterface::ABSOLUTE_URL);

                $sitemapUrl->addLink($altUrl, $altLocale);
            }
        }

        $urls->addUrl($sitemapUrl, $volume->getId(true));
    }

    // see http://stackoverflow.com/a/10473026
    private function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    private function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    private function addUrlDescription(&$urlDescriptions, $key, $routeLocale, $localeUrlDescription)
    {
        if (!array_key_exists($key, $urlDescriptions)) {
            $urlDescriptions[$key] = [];
        }

        $urlDescriptions[$key][$routeLocale] = $localeUrlDescription;
    }

    /**
     * @param SitemapPopulateEvent $event
     */
    public function populate(SitemapPopulateEvent $event): void
    {
        $locale = $this->translator->getLocale();

        $urlDescriptions = [];

        foreach ($this->router->getRouteCollection() as $name => $route) {
            $defaults = $route->getDefaults();

            if (!($this->startsWith($defaults['_controller'], 'App'))) {
                // skip routes from other bundles
                continue;
            }

            if (!array_key_exists('_locale', $defaults)) {
                continue;
            }

            $urlset = 'default';
            // name is $locale__RG__$routeName
            $parts = explode('__', $name);
            $routeName = $parts[count($parts) - 1];

            if (in_array($routeName, [ 'place-inhabited' ])
                || preg_match('/^search\-select\-/', $routeName))
            {
                // omit certain routes from sitemap
                continue;
            }

            if (preg_match('/\{.*?\}/', $route->getPath())) {
                // handle the ones with parameters

                switch ($routeName) {
                    case 'site':
                    case 'person':
                    case 'organization':
                        $urlset = $routeName;
                        $qb = $this->entityManager
                            ->createQueryBuilder();

                        $qb->select([ 'E' ])
                            ->from('\App\Entity\\' . ucfirst($routeName), 'E')
                            ->where('site' == $routeName ? 'E.status IN (1)' : 'E.status IN (0,1)')
                            ;

                        $query = $qb->getQuery();
                        $entities = $query->getResult();
                        foreach ($entities as $entity) {
                            $gnd = null; // $entity->getGnd();
                            if (!empty($gnd)) {
                                $url = $this->router->generate($routeName . '-by-gnd', [ 'gnd' => $gnd, '_locale' => $defaults['_locale'] ],
                                                               UrlGeneratorInterface::ABSOLUTE_URL);
                            }
                            else {
                                $url = $this->router->generate($routeName, [ 'id' => $entity->getId(), '_locale' => $defaults['_locale'] ],
                                                               UrlGeneratorInterface::ABSOLUTE_URL);
                            }

                            $this->addUrlDescription($urlDescriptions, $routeName . $entity->getId(), $defaults['_locale'], [ 'url' => $url, 'urlset' => $urlset ]);
                        }

                        break;

                    default:
                        ; // ignore
                }
            }
            else {
                $url = $this->router->generate($routeName, [
                        '_locale' => $defaults['_locale'],
                    ], UrlGeneratorInterface::ABSOLUTE_URL);

                if (!$this->endsWith($url, '/beacon')) {
                    $this->addUrlDescription($urlDescriptions, $routeName, $defaults['_locale'], [ 'url' => $url, 'urlset' => $urlset ]);
                }
            }
        }

        foreach ($urlDescriptions as $urlDescription) {
            if (array_key_exists($locale, $urlDescription)) {
                $localeUrlDescription = $urlDescription[$locale];
                $url = new UrlConcrete(
                        $localeUrlDescription['url']
                        //,
                        // TODO: custom settings for lastMod, changeFreq, weight
                        // new \DateTime(),
                        // UrlConcrete::CHANGEFREQ_WEEKLY,
                        // 0.5
                    );

                $url = new \Presta\SitemapBundle\Sitemap\Url\GoogleMultilangUrlDecorator($url);

                // add decorations for alternate language versions
                foreach ($urlDescription as $altLocale => $localeUrlDescription) {
                    if ($altLocale != $locale) {
                        $url->addLink($localeUrlDescription['url'], $altLocale);
                    }
                }

                $event->getUrlContainer()->addUrl($url,
                    $localeUrlDescription['urlset']
                );
            }
        }
    }
}
