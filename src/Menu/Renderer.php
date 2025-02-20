<?php

// src/Menu/Renderer.php

// see https://symfony.com/bundles/KnpMenuBundle/current/custom_renderer.html

namespace App\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class Renderer extends \Knp\Menu\Renderer\ListRenderer
{
    protected $requestStack;
    protected $router;
    protected $locales;

    public function __construct(
        MatcherInterface $matcher,
        array $defaultOptions,
        string $charset,
        RequestStack $requestStack,
        RouterInterface $router,
        array $locales = []
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->locales = $locales;

        parent::__construct($matcher, $defaultOptions, $charset);
    }

    /**
     * Override to prepend the locale-switch for mobile view
     */
    protected function renderChildren(ItemInterface $item, array $options): string
    {
        $prepend = '';

        if ('main' == $item->getName() && count($this->locales) > 1) {
            $request = $this->requestStack->getCurrentRequest();

            $currentLocale = $request->getLocale();

            $routeName = $request->attributes->get('_route'); // can be null on 404

            if (!empty($routeName)) {
                $routeParameters = $request->attributes->get('_route_params');

                $prepend = '<li class="nav-item d-md-none"><ul class="text-end list-inline">';
                for ($i = 0; $i < count($this->locales); $i++) {
                    $last = $i == count($this->locales) - 1;

                    $locale = $this->locales[$i];
                    $routeParameters['_locale'] = $locale;

                    $a = sprintf(
                        '<a class="nav-link%s" href="%s">%s</a>',
                        $last ? '' : ' divider',
                        $this->router->generate($routeName, $routeParameters),
                        mb_strtoupper($locale, 'UTF-8')
                    );

                    $prepend .= sprintf(
                        '<li class="list-inline-item%s">%s</li>',
                        $locale == $currentLocale ? ' active' : '',
                        $a
                    );
                }

                $prepend .= '</ul></li>';
            }
        }

        return $prepend . parent::renderChildren($item, $options);
    }
}
