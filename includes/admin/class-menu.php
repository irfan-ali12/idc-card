<?php
namespace IDC\Admin;

use IDC\DB;

if (!defined('ABSPATH')) { exit; }

class Menu {
    public function init(): void {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu(): void {
        add_menu_page(
            'ID Cards',
            'ID Cards',
            'idc_read',
            'idc-dashboard',
            [$this, 'customers_page'],
            'dashicons-id-alt',
            26
        );

        add_submenu_page(
            'idc-dashboard',
            'Customers',
            'Customers',
            'idc_read',
            'idc-dashboard',
            [$this, 'customers_page']
        );

        add_submenu_page(
            'idc-dashboard',
            'Settings',
            'Settings',
            'idc_manage',
            'idc-settings',
            [$this, 'settings_page']
        );

        add_submenu_page(
            'idc-dashboard',
            'Audit Log',
            'Audit Log',
            'idc_manage',
            'idc-audit',
            [$this, 'audit_page']
        );
    }

    public function customers_page(): void {
        // Minimal placeholder for now; real template in next chunk
        echo '<div class="wrap"><h1>ID Cards — Customers</h1><p>Customer list/search UI will appear here.</p></div>';
    }

    public function settings_page(): void {
        include IDC_CARD_DIR . 'templates/admin-settings.php';
    }

    public function audit_page(): void {
        // Minimal placeholder for now; real template in later chunk
        echo '<div class="wrap"><h1>ID Cards — Audit Log</h1><p>Audit table will appear here.</p></div>';
    }
}
