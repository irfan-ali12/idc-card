<?php
/**
 * Plugin Name: IDC Card – Pixel-Perfect ID Cards
 * Description: Pixel-perfect CR80 portrait ID cards with live QR, exact 2-page print, roles, DB versioning, and audit log.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Gbcodies
 * Author URI: https://gbcodies.com
 * Text Domain: idc-card
 */

if (!defined('ABSPATH')) { exit; }

define('IDC_CARD_VERSION', '0.1.0');
define('IDC_CARD_FILE', __FILE__);
define('IDC_CARD_DIR', plugin_dir_path(__FILE__));
define('IDC_CARD_URL', plugin_dir_url(__FILE__));

// Load core classes
require_once IDC_CARD_DIR . 'includes/class-activator.php';
require_once IDC_CARD_DIR . 'includes/class-deactivator.php';

// Core plugin files
require_once IDC_CARD_DIR . 'includes/class-assets.php';
require_once IDC_CARD_DIR . 'includes/class-db.php';
require_once IDC_CARD_DIR . 'includes/class-rest.php';
require_once IDC_CARD_DIR . 'public/class-shortcode.php';
require_once IDC_CARD_DIR . 'public/class-login-handler.php';
require_once IDC_CARD_DIR . 'public/class-admin-dashboard.php';
require_once IDC_CARD_DIR . 'public/class-student-dashboard.php';
require_once IDC_CARD_DIR . 'includes/admin/class-menu.php';

register_activation_hook(__FILE__, ['IDC\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['IDC\\Deactivator', 'deactivate']);

// Late-load optional modules when present
add_action('plugins_loaded', function () {
    // Assets
    $assets = IDC_CARD_DIR . 'includes/class-assets.php';
    if (file_exists($assets)) { require_once $assets; (new IDC\Assets())->init(); }

    // DB helper (loaded by REST/admin when needed)
    $dbfile = IDC_CARD_DIR . 'includes/class-db.php';
    if (file_exists($dbfile)) { require_once $dbfile; }

    // REST
    $rest = IDC_CARD_DIR . 'includes/class-rest.php';
    if (file_exists($rest)) { require_once $rest; (new IDC\REST())->init(); }

    // Shortcode
    $short = IDC_CARD_DIR . 'public/class-shortcode.php';
    if (file_exists($short)) { require_once $short; (new IDC\Shortcode())->init(); }
    
    // Admin Dashboard Shortcode
    $admin_dashboard = IDC_CARD_DIR . 'public/class-admin-dashboard.php';
    if (file_exists($admin_dashboard)) { require_once $admin_dashboard; (new IDC\AdminDashboard())->init(); }

    // Operator Dashboard Shortcode
    $operator_dashboard = IDC_CARD_DIR . 'public/class-operator-dashboard.php';
    if (file_exists($operator_dashboard)) { require_once $operator_dashboard; (new IDC\OperatorDashboard())->init(); }

    // Student Dashboard Shortcode
    $student_dashboard = IDC_CARD_DIR . 'public/class-student-dashboard.php';
    if (file_exists($student_dashboard)) { require_once $student_dashboard; (new IDC\StudentDashboard())->init(); }

    // Login Handler
    $login_handler = IDC_CARD_DIR . 'public/class-login-handler.php';
    if (file_exists($login_handler)) { require_once $login_handler; }

    // Admin
    if (is_admin()) {
        $menu = IDC_CARD_DIR . 'includes/admin/class-menu.php';
        if (file_exists($menu)) { require_once $menu; (new IDC\Admin\Menu())->init(); }
    }
    
    // Check for database migrations on every load
    IDC\Activator::check_migrations();
    
    // Hide admin bar for all users
    add_filter('show_admin_bar', '__return_false');
    
    // Remove admin bar completely (including CSS)
    add_action('wp_head', function() {
        echo '<style type="text/css">
            html { margin-top: 0 !important; }
            #wpadminbar { display: none !important; }
        </style>';
    });
    
    // Remove admin bar from admin area as well
    add_action('admin_head', function() {
        echo '<style type="text/css">
            #wpadminbar { display: none !important; }
            html.wp-toolbar { padding-top: 0 !important; }
        </style>';
    });
    
    // Disable admin bar for all users in their profile
    add_action('init', function() {
        // Remove admin bar for all users
        if (current_user_can('read')) {
            add_filter('show_admin_bar', '__return_false');
        }
    });
    
    // Remove admin bar preference from user profile
    add_action('personal_options', function($user) {
        echo '<style type="text/css">
            .show-admin-bar { display: none !important; }
        </style>';
    });
});
