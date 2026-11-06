<?php

namespace App\Support;

/**
 * Navigation shared by the admin sidebar (desktop) and bottom tab bar (mobile and tablet).
 */
class AdminNavigation
{
    /**
     * @return list<array{label: string, route: string, active: string, icon: string}>
     */
    public static function main(): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'layout-dashboard'],
            ['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
            ['label' => 'Registration Management', 'route' => 'admin.registrations.index', 'active' => 'admin.registrations.*', 'icon' => 'clipboard-list'],
            ['label' => 'QR Scanner', 'route' => 'admin.scanner.index', 'active' => 'admin.scanner.*', 'icon' => 'scan-qr-code'],
        ];
    }
}
