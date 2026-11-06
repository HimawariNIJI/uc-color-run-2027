<?php

namespace App\Support;

/**
 * Navigation shared by the site header and footer.
 */
class SiteNavigation
{
    /**
     * @return list<array{label: string, route: string, active: string, children?: list<array{label: string, route: string, icon: string}>}>
     */
    public static function main(): array
    {
        return [
            ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
            ['label' => 'About', 'route' => 'about', 'active' => 'about'],
            [
                'label' => 'Race Info',
                'route' => 'race.category',
                'active' => 'race.*',
                'children' => [
                    ['label' => 'Race Category', 'route' => 'race.category', 'icon' => 'flag'],
                    ['label' => 'Race Schedule', 'route' => 'race.schedule', 'icon' => 'calendar'],
                    ['label' => 'Race Regulation', 'route' => 'race.regulation', 'icon' => 'file-text'],
                ],
            ],
            ['label' => 'Registration', 'route' => 'registration.type', 'active' => 'registration.*'],
            ['label' => 'FAQ', 'route' => 'faq', 'active' => 'faq'],
            ['label' => 'Contact', 'route' => 'contact', 'active' => 'contact'],
        ];
    }

    /**
     * @return list<array{label: string, route: string, icon: string}>
     */
    public static function account(): array
    {
        return [
            ['label' => 'My Registration', 'route' => 'my-registration.show', 'icon' => 'clipboard-list'],
            ['label' => 'My BIB', 'route' => 'my-bib.show', 'icon' => 'ticket'],
        ];
    }
}
