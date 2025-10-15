<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class Shortcode {
    public function init(): void {
        add_shortcode('idc_designer', [$this, 'render']);
    }

    public function render($atts, $content = ''): string {
        // Check if user is logged in - redirect to custom login if not
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
            exit;
        }
        
        if (!current_user_can('idc_read')) {
            return '<p>You do not have permission to view this designer.</p>';
        }
        
        // Enqueue designer-specific assets
        $this->enqueue_designer_assets();
        
        ob_start();
        include IDC_CARD_DIR . 'public/templates/designer.php';
        return ob_get_clean();
    }
    
    private function enqueue_designer_assets(): void {
        // Designer CSS/JS
        wp_enqueue_style('idc-designer', IDC_CARD_URL . 'assets/css/designer.css', [], IDC_CARD_VERSION);

        // QR lib
        wp_enqueue_script('idc-qrcode', IDC_CARD_URL . 'assets/js/qrcode.min.js', [], IDC_CARD_VERSION, true);
        wp_enqueue_script('idc-designer', IDC_CARD_URL . 'assets/js/designer.js', ['idc-qrcode'], IDC_CARD_VERSION, true);

        // Pass runtime config to JS
        $settings = get_option('idc_settings', []);
        wp_localize_script('idc-designer', 'IDC_CONFIG', [
            'rest' => [
                'url'   => esc_url_raw(rest_url('idc/v1/')),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            'assets' => [
                'front' => $this->media_url($settings['front_png_id'] ?? 0, 'assets/img/placeholder_front.png'),
                'back'  => $this->media_url($settings['back_png_id']  ?? 0, 'assets/img/placeholder_back.png'),
                'font'  => $this->media_url($settings['garet_font_id'] ?? 0, 'assets/fonts/garet.woff2'),
            ],
            'coords' => $settings['coords'] ?? [],
            'card'   => ['width_mm' => 53.98, 'height_mm' => 85.6],
        ]);
    }
    
    private function media_url($attachment_id, string $fallback_rel): string {
        if ($attachment_id) {
            $u = wp_get_attachment_url((int)$attachment_id);
            if ($u) return $u;
        }
        return IDC_CARD_URL . ltrim($fallback_rel, '/');
    }
}
