<?php

// src/Menu/Builder.php

// see http://symfony.com/doc/current/bundles/KnpMenuBundle/index.html

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Builder
{
    private $factory;
    private $translator;
    private $requestStack;
    private $router;

    /**
     * @param FactoryInterface $factory
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     * @param Router $router
     *
     * Add any other dependency you need
     */
    public function __construct(
        FactoryInterface $factory,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        RouterInterface $router
    ) {
        $this->factory = $factory;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('main', [
            'childrenAttributes' => [
                'id' => 'menu-main',
                'class' => 'container navbar-nav nav-fill w-100 ms-auto flex-nowrap',
            ],
        ]);

        // add menu items
        $menu->addChild('site', [
            'label' => $this->translator->trans('Sites'),
            'route' => 'site-map',
            'attributes' => [
                'class' => 'nav-item',
            ],
            'linkAttributes' => [
                'class' => 'nav-link',
            ],
        ]);

        $menu->addChild('lookup', [
            'label' => $this->translator->trans('Look-up'),
            'uri' => '#',
            'attributes' => [
                'id' => 'dropdownLookupMenuButton',
                'class' => 'nav-item dropdown',
            ],
            'linkAttributes' => [
                'class' => 'nav-link dropdown-toggle',
                'dropdown' => true,
                'role' => 'button',
                'data-bs-toggle' => 'dropdown',
                'aria-expanded' => 'false',
            ],
            'childrenAttributes' => [
                'class' => 'dropdown-menu dropdown-menu-center',
                'aria-labelledby' => 'dropdownLookupMenuButton',
            ],
        ]);

        $menu['lookup']
            ->addChild('person', [
                'label' => $this->translator->trans('Persons'),
                'route' => 'person-index',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['lookup']
            ->addChild('organization', [
                'label' => $this->translator->trans('Organizations'),
                'route' => 'organization-index',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['lookup']
            ->addChild('glossary', [
                'label' => $this->translator->trans('Glossary'),
                'route' => 'glossary-index',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['lookup']
            ->addChild('bibliography', [
                'label' => $this->translator->trans('Literature'),
                'route' => 'bibliography-index',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu->addChild('about', [
            'label' => $this->translator->trans('About'),
            'route' => 'about',
            'attributes' => [
                'id' => 'dropdownAboutMenuButton',
                'class' => 'nav-item dropdown',
            ],
            'linkAttributes' => [
                'class' => 'nav-link dropdown-toggle',
                'dropdown' => true,
                'role' => 'button',
                'data-bs-toggle' => 'dropdown',
                'aria-expanded' => 'false',
            ],
            'childrenAttributes' => [
                'class' => 'dropdown-menu dropdown-menu-center',
                'aria-labelledby' => 'dropdownAboutMenuButton',
            ],
        ]);

        $menu['about']
            ->addChild('about-hakhshara', [
                'label' => $this->translator->trans('Hakhshara'),
                'route' => 'about-hakhshara',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['about']
            ->addChild('about', [
                'label' => $this->translator->trans('The Project'),
                'route' => 'about',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['about']
            ->addChild('about-network', [
                'label' => $this->translator->trans('The Network'),
                'route' => 'about-network',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        $menu['about']
            ->addChild('imprint', [
                'label' => $this->translator->trans('Imprint'),
                'uri' => $this->router->generate('imprint') . '#imprint',
                'linkAttributes' => [
                    'class' => 'dropdown-item',
                ],
            ]);

        // attempt to set the current item for hierarchical entries
        $currentRoute = $this->requestStack->getCurrentRequest()->get('_route');

        if (!is_null($currentRoute)) {
            if (preg_match('/^(site|about)/', $currentRoute, $matches)) {
                $menu[$matches[1]]->setCurrent(true);
            }
            else if (preg_match('/^(person|organization|glossary|bibliography)/', $currentRoute, $matches)) {
                $menu['lookup']->setCurrent(true);
            }
            else if (preg_match('/^(user|imprint)/', $currentRoute, $matches)) {
                $menu['about']->setCurrent(true);
            }
        }

        return $menu;
    }
}
