<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class StudentDashboard {
    public function init(): void {
        add_shortcode('idc_student_dashboard', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts(): void {
        global $post;
        if (!is_a($post, 'WP_Post')) return;

        if (has_shortcode($post->post_content, 'idc_student_dashboard')) {
            // Enqueue jQuery for compatibility
            wp_enqueue_script('jquery');
            
            // Localize script for REST API
            wp_localize_script('jquery', 'idc_student', [
                'rest_url' => rest_url('idc/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'plugin_url' => IDC_CARD_URL,
                'user_id' => get_current_user_id(),
            ]);
        }
    }

    public function render($atts, $content = ''): string {
        // Check if user is logged in - redirect to custom login if not
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
            exit;
        }

        // Only viewers should see this dashboard
        if (!current_user_can('idc_read')) {
            return '<div class="idc-error"><p>You do not have permission to view this dashboard.</p></div>';
        }

        try {
            // Test if DB class exists
            if (!class_exists('IDC\DB')) {
                return '<div class="idc-error"><p>Database class not found. Please ensure the plugin is properly activated.</p></div>';
            }

            // Get user's existing data if any
            $db = new DB();
            $user_id = get_current_user_id();
            
            // Try to get existing customer record for this user
            $existing_data = $this->get_user_card_data($user_id);
            
            // Get card settings for background images
            $settings = get_option('idc_settings', []);
            
            // Prepare data for the dashboard
            $dashboard_data = [
                'user_data' => $existing_data,
                'settings' => $settings,
                'user_id' => $user_id,
            ];

            ob_start();
            include IDC_CARD_DIR . 'public/templates/student-dashboard.php';
            return ob_get_clean();

        } catch (Exception $e) {
            return '<div class="idc-error"><p>Error loading student dashboard: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    private function get_user_card_data($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'idc_customers';
        
        // Look for existing record by WordPress user ID
        $user = get_userdata($user_id);
        if (!$user) return null;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE wp_user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
        
        return $result;
    }
}