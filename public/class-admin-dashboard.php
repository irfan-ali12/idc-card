<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class AdminDashboard {
    public function init(): void {
        add_shortcode('idc_admin_dashboard', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts(): void {
        // Only enqueue if the shortcode is being used
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'idc_admin_dashboard')) {
            // Enqueue custom dashboard styles
            wp_enqueue_style('idc-admin-dashboard', IDC_CARD_URL . 'assets/css/admin-dashboard.css', [], IDC_CARD_VERSION);
            
            // Enqueue jQuery for compatibility
            wp_enqueue_script('jquery');
            
            // Localize script for REST API
            wp_localize_script('jquery', 'idc_admin', [
                'rest_url' => rest_url('idc/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'plugin_url' => IDC_CARD_URL,
            ]);
        }
    }

    public function render($atts, $content = ''): string {
        // Check if user is logged in - redirect to custom login if not
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
            exit;
        }
        
        // Only allow admin users (idc_manage capability) - operators should use operator dashboard
        if (!current_user_can('idc_manage') && !current_user_can('manage_options')) {
            return '<div class="idc-error"><p>You do not have permission to view this dashboard. This dashboard is for administrators only. Operators should use the operator dashboard.</p></div>';
        }

        try {
            // Test if DB class exists
            if (!class_exists('IDC\DB')) {
                return '<div class="idc-error"><p>Database class not found. Please ensure the plugin is properly activated.</p></div>';
            }

            // Get initial data for the dashboard
            $db = new DB();
            $customers = $db->find_customers('', 100); // Get all customers
            $audit_logs = $db->list_audit('', 0, 50); // Get recent audit logs

            // Transform data for the React component
            $dashboard_data = [
                'customers' => $this->format_customers_for_dashboard($customers),
                'audit_logs' => $audit_logs,
                'stats' => $this->get_dashboard_stats($customers),
            ];

            // Debug info (remove in production)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('IDC Dashboard Data: ' . wp_json_encode([
                    'customers_count' => count($customers),
                    'dashboard_data_keys' => array_keys($dashboard_data),
                    'user_can_idc_edit' => current_user_can('idc_edit'),
                    'user_can_manage_options' => current_user_can('manage_options'),
                ]));
            }

            ob_start();
            include IDC_CARD_DIR . 'public/templates/admin-dashboard.php';
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('IDC Dashboard Error: ' . $e->getMessage());
            }
            return '<div class="idc-error"><p>Error loading dashboard: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    private function format_customers_for_dashboard(array $customers): array {
        $formatted = [];
        foreach ($customers as $customer) {
            $photo_url = 'https://placehold.co/300x300?text=' . urlencode(substr($customer['full_name'] ?? 'U', 0, 1));
            
            // Try to get actual photo if photo_media_id exists
            if (!empty($customer['photo_media_id'])) {
                $photo_attachment = wp_get_attachment_image_src($customer['photo_media_id'], 'medium');
                if ($photo_attachment) {
                    $photo_url = $photo_attachment[0];
                }
            }

            $formatted[] = [
                'id' => 'c' . $customer['id'],
                'full_name' => $customer['full_name'] ?? '',
                'national_id' => $customer['national_id'] ?? '',
                'passport' => $customer['passport_no'] ?? '',
                'country' => $customer['country'] ?? '',
                'job_title' => $customer['job_title'] ?? 'Student',
                'created_at' => $customer['created_at'] ?? '',
                'status' => $customer['status'] ?? 'active',
                'photo' => $photo_url,
                'dob' => $customer['dob'] ?? '',
                'issued_on' => $customer['issued_on'] ?? '',
            ];
        }
        return $formatted;
    }

    private function get_dashboard_stats(array $customers): array {
        $total = count($customers);
        $active = count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active'));
        $inactive = $total - $active;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'print_queue' => 0, // This could be enhanced to track actual print queue
        ];
    }
}