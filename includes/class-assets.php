<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class Assets {
    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'admin']);
        add_action('wp_enqueue_scripts',    [$this, 'public']);
    }

    public function admin(): void {
        wp_enqueue_style ('idc-admin',  IDC_CARD_URL . 'assets/css/admin.css', [], IDC_CARD_VERSION);
        wp_enqueue_script('idc-admin',  IDC_CARD_URL . 'assets/js/admin.js', ['jquery'], IDC_CARD_VERSION, true);

        // Media frame for settings page
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }
    }

    public function public(): void {
        // Public-facing assets can be added here if needed
        // Designer assets are now loaded specifically by the shortcode


    }

    private static function media_url($attachment_id, string $fallback_rel): string {
        if ($attachment_id) {
            $u = wp_get_attachment_url((int)$attachment_id);
            if ($u) return $u;
        }
        return IDC_CARD_URL . ltrim($fallback_rel, '/');
    }
}
