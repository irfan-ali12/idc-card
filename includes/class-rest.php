<?php
namespace IDC;

use WP_REST_Request;
use WP_Error;

if (!defined('ABSPATH')) { exit; }

class REST {

    public function init(): void {
        add_action('rest_api_init', function () {
            register_rest_route('idc/v1', '/customer', [
                ['methods' => 'POST', 'callback' => [$this, 'create_customer'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/customer/(?P<id>\d+)', [
                ['methods' => 'GET',    'callback' => [$this, 'get_customer'],    'permission_callback' => [$this, 'can_read']],
                ['methods' => 'PUT',    'callback' => [$this, 'update_customer'], 'permission_callback' => [$this, 'can_edit']],
                ['methods' => 'DELETE', 'callback' => [$this, 'delete_customer'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/customers', [
                ['methods' => 'GET', 'callback' => [$this, 'list_customers'], 'permission_callback' => [$this, 'can_read']],
            ]);

            register_rest_route('idc/v1', '/card', [
                ['methods' => 'POST', 'callback' => [$this, 'create_card'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/card/(?P<id>\d+)', [
                ['methods' => 'GET', 'callback' => [$this, 'get_card'],    'permission_callback' => [$this, 'can_read']],
                ['methods' => 'PUT', 'callback' => [$this, 'update_card'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/customers/bulk-delete', [
                ['methods' => 'POST', 'callback' => [$this, 'bulk_delete_customers'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/audit-logs', [
                ['methods' => 'GET', 'callback' => [$this, 'list_audit_logs'], 'permission_callback' => [$this, 'can_read']],
            ]);

            register_rest_route('idc/v1', '/dashboard-stats', [
                ['methods' => 'GET', 'callback' => [$this, 'get_dashboard_stats'], 'permission_callback' => [$this, 'can_read']],
            ]);

            register_rest_route('idc/v1', '/card-settings', [
                ['methods' => 'GET', 'callback' => [$this, 'get_card_settings'], 'permission_callback' => [$this, 'can_read']],
            ]);

            register_rest_route('idc/v1', '/upload-photo', [
                ['methods' => 'POST', 'callback' => [$this, 'upload_photo'], 'permission_callback' => [$this, 'can_upload_photo']],
            ]);

            // Student dashboard endpoints
            register_rest_route('idc/v1', '/student/print-status', [
                ['methods' => 'GET', 'callback' => [$this, 'get_student_print_status'], 'permission_callback' => [$this, 'is_student']],
            ]);

            register_rest_route('idc/v1', '/student/request-print', [
                ['methods' => 'POST', 'callback' => [$this, 'student_request_print'], 'permission_callback' => [$this, 'is_student']],
            ]);

            // Student-specific customer management
            register_rest_route('idc/v1', '/student/my-card', [
                ['methods' => 'GET', 'callback' => [$this, 'get_student_card'], 'permission_callback' => [$this, 'is_student']],
                ['methods' => 'POST', 'callback' => [$this, 'create_student_card'], 'permission_callback' => [$this, 'is_student']],
                ['methods' => 'PUT', 'callback' => [$this, 'update_student_card'], 'permission_callback' => [$this, 'is_student']],
            ]);

            register_rest_route('idc/v1', '/student/change-password', [
                ['methods' => 'POST', 'callback' => [$this, 'change_student_password'], 'permission_callback' => [$this, 'is_student']],
            ]);

            // Admin/Operator print request management
            register_rest_route('idc/v1', '/print-requests', [
                ['methods' => 'GET', 'callback' => [$this, 'get_print_requests'], 'permission_callback' => [$this, 'can_read']],
            ]);

            register_rest_route('idc/v1', '/print-request/(?P<user_id>\d+)/approve', [
                ['methods' => 'POST', 'callback' => [$this, 'approve_print_request'], 'permission_callback' => [$this, 'can_edit']],
            ]);

            register_rest_route('idc/v1', '/print-request/(?P<user_id>\d+)/reject', [
                ['methods' => 'POST', 'callback' => [$this, 'reject_print_request'], 'permission_callback' => [$this, 'can_edit']],
            ]);
        });
    }

    /* ---------- permissions ---------- */
    public function can_edit(): bool { 
        // Allow admin/operator roles or fallback permissions
        $user = wp_get_current_user();
        return is_user_logged_in() && (
            in_array('idc_admin', $user->roles) || 
            in_array('idc_operator', $user->roles) ||
            current_user_can('edit_posts') || 
            current_user_can('idc_edit')
        ); 
    }
    
    public function can_read(): bool { 
        // Allow all logged in users to read
        return is_user_logged_in(); 
    }

    public function is_student(): bool {
        $user = wp_get_current_user();
        return is_user_logged_in() && in_array('idc_viewer', $user->roles);
    }

    public function can_upload_photo(): bool {
        // Allow all logged in users with any IDC role to upload photos
        $user = wp_get_current_user();
        return is_user_logged_in() && (
            in_array('idc_admin', $user->roles) || 
            in_array('idc_operator', $user->roles) ||
            in_array('idc_viewer', $user->roles) ||
            current_user_can('upload_files')
        );
    }

    /* ---------- handlers: customers ---------- */
    public function create_customer(WP_REST_Request $req) {
        error_log('IDC: create_customer called');
        $json_params = $req->get_json_params();
        error_log('IDC: Raw JSON params: ' . print_r($json_params, true));
        $p = $this->sanitize_customer($json_params);
        error_log('IDC: Sanitized params: ' . print_r($p, true));

        // Try update-if-exists by national_id (avoid duplicates)
        $db = new DB();
        if (!empty($p['national_id'])) {
            $existing = $db->find_customer_by_nid($p['national_id']);
            if ($existing) {
                $db->update_customer((int)$existing['id'], $p);
                return ['id' => (int)$existing['id'], 'updated' => true];
            }
        }

        if (empty($p['full_name']) || empty($p['national_id']) || empty($p['dob'])) {
            return new WP_Error('idc_bad_request', 'Missing required fields: full_name, national_id, dob', ['status' => 400]);
        }
        
        // Set default job_title if not provided
        if (empty($p['job_title'])) {
            $p['job_title'] = 'Student';
        }
        
        $id = $db->create_customer($p);
        error_log('IDC: Customer created successfully with ID: ' . $id);
        return ['id' => $id];
    }

    public function update_customer(WP_REST_Request $req) {
        $id = (int) $req['id'];
        $p  = $this->sanitize_customer($req->get_json_params());
        $db = new DB();
        $ok = $db->update_customer($id, $p);
        return ['ok' => (bool)$ok];
    }

    public function get_customer(WP_REST_Request $req) {
        $db = new DB();
        $row = $db->get_customer((int)$req['id']);
        return $row ?: new WP_Error('idc_not_found', 'Customer not found', ['status' => 404]);
    }

    public function list_customers(WP_REST_Request $req) {
        $q = sanitize_text_field($req->get_param('q') ?? '');
        $db = new DB();
        return $db->find_customers($q, 100);
    }

    /* ---------- handlers: cards ---------- */
    public function create_card(WP_REST_Request $req) {
        $p = $req->get_json_params();
        $customer_id = (int)($p['customer_id'] ?? 0);
        if (!$customer_id) {
            return new WP_Error('idc_bad_request', 'customer_id is required', ['status' => 400]);
        }
        $db = new DB();
        $id = $db->create_card($customer_id, [
            'qr_payload'      => wp_json_encode($p['qr_payload'] ?? []),
            'front_image_uri' => sanitize_text_field($p['front_image_uri'] ?? ''),
            'back_image_uri'  => sanitize_text_field($p['back_image_uri']  ?? ''),
            'note'            => sanitize_textarea_field($p['note'] ?? ''),
        ]);
        return ['id' => $id];
    }

    public function update_card(WP_REST_Request $req) {
        $id = (int)$req['id'];
        $p  = $req->get_json_params();
        $db = new DB();
        $ok = $db->bump_card_version($id, [
            'qr_payload' => wp_json_encode($p['qr_payload'] ?? []),
            'note'       => sanitize_textarea_field($p['note'] ?? ''),
        ]);
        return ['ok' => (bool)$ok];
    }

    public function get_card(WP_REST_Request $req) {
        $db = new DB();
        $row = $db->get_card((int)$req['id']);
        return $row ?: new WP_Error('idc_not_found', 'Card not found', ['status' => 404]);
    }

    public function delete_customer(WP_REST_Request $req) {
        $id = (int) $req['id'];
        $db = new DB();
        
        // Check if customer exists first
        $customer = $db->get_customer($id);
        if (!$customer) {
            return new WP_Error('idc_not_found', 'Customer not found', ['status' => 404]);
        }
        
        // Hard delete the customer from database
        $ok = $db->delete_customer($id);
        if (!$ok) {
            return new WP_Error('idc_delete_failed', 'Failed to delete customer', ['status' => 500]);
        }
        
        return ['ok' => true, 'message' => 'Customer deleted successfully'];
    }

    public function bulk_delete_customers(WP_REST_Request $req) {
        $data = $req->get_json_params();
        $ids = $data['ids'] ?? [];
        
        if (empty($ids) || !is_array($ids)) {
            return new WP_Error('idc_bad_request', 'No customer IDs provided', ['status' => 400]);
        }
        
        // Validate that all IDs are integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        
        if (empty($ids)) {
            return new WP_Error('idc_bad_request', 'No valid customer IDs provided', ['status' => 400]);
        }
        
        $db = new DB();
        $deleted_count = $db->bulk_delete_customers($ids);
        
        return [
            'ok' => true, 
            'message' => "Successfully deleted {$deleted_count} customer(s)",
            'deleted_count' => $deleted_count,
            'requested_count' => count($ids)
        ];
    }

    public function list_audit_logs(WP_REST_Request $req) {
        $entity = sanitize_text_field($req->get_param('entity') ?? '');
        $entity_id = (int)($req->get_param('entity_id') ?? 0);
        $limit = min(200, max(1, (int)($req->get_param('limit') ?? 50)));
        
        $db = new DB();
        return $db->list_audit($entity, $entity_id, $limit);
    }

    public function get_dashboard_stats(WP_REST_Request $req) {
        $db = new DB();
        $customers = $db->find_customers('', 1000); // Get a large sample for stats
        
        $total = count($customers);
        $active = count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active'));
        $inactive = count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'inactive'));
        $deleted = count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'deleted'));
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'deleted' => $deleted,
            'print_queue' => 0, // Could be enhanced to track actual print queue
        ];
    }

    /* ---------- sanitization ---------- */
    private function sanitize_customer(array $p): array {
        return [
            'first_name'     => isset($p['first_name'])     ? sanitize_text_field($p['first_name'])      : null,
            'last_name'      => isset($p['last_name'])      ? sanitize_text_field($p['last_name'])       : null,
            'full_name'      => sanitize_text_field($p['full_name']   ?? ''),
            'national_id'    => sanitize_text_field($p['national_id'] ?? ''),
            'dob'            => sanitize_text_field($p['dob']          ?? ''),
            'country'        => isset($p['country'])        ? sanitize_text_field($p['country'])         : null,
            'issued_on'      => isset($p['issued_on'])      ? sanitize_text_field($p['issued_on'])       : null,
            'passport_no'    => isset($p['passport_no'])    ? sanitize_text_field($p['passport_no'])     : null,
            'photo_media_id' => isset($p['photo_media_id']) ? (int)$p['photo_media_id']                  : null,
            'job_title'      => isset($p['job_title'])      ? sanitize_text_field($p['job_title'])       : 'Student',
            'status'         => sanitize_text_field($p['status']       ?? 'active'),
        ];
    }

    /**
     * Get card settings for printing (background images)
     */
    public function get_card_settings(WP_REST_Request $request) {
        $settings = get_option('idc_settings', []);
        
        $frontURL = isset($settings['front_png_id']) && $settings['front_png_id'] 
            ? wp_get_attachment_url((int)$settings['front_png_id']) 
            : IDC_CARD_URL . 'assets/img/placeholder_front.png';
            
        $backURL = isset($settings['back_png_id']) && $settings['back_png_id']
            ? wp_get_attachment_url((int)$settings['back_png_id'])
            : IDC_CARD_URL . 'assets/img/placeholder_back.png';

        return [
            'front' => $frontURL,
            'back' => $backURL,
        ];
    }

    /**
     * Handle photo upload for customer profiles
     */
    public function upload_photo(WP_REST_Request $request) {
        error_log('IDC: upload_photo called');
        
        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new WP_Error('no_file', 'No file was uploaded.', ['status' => 400]);
        }

        $file = $files['file'];
        error_log('IDC: File received - Name: ' . $file['name'] . ', Size: ' . $file['size'] . ', Type: ' . $file['type']);

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Only JPEG, PNG and GIF images are allowed.', ['status' => 400]);
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', 'File size must be less than 5MB.', ['status' => 400]);
        }

        // Use WordPress media handling
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $upload_overrides = [
            'test_form' => false,
            'test_size' => true,
            'test_upload' => true,
        ];

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            error_log('IDC: Upload error - ' . $uploaded_file['error']);
            return new WP_Error('upload_error', $uploaded_file['error'], ['status' => 500]);
        }

        // Create attachment
        $attachment_data = [
            'post_title' => sanitize_file_name(pathinfo($uploaded_file['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_mime_type' => $uploaded_file['type'],
        ];

        $attachment_id = wp_insert_attachment($attachment_data, $uploaded_file['file']);

        if (is_wp_error($attachment_id)) {
            error_log('IDC: Attachment creation failed - ' . $attachment_id->get_error_message());
            return $attachment_id;
        }

        // Generate attachment metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);

        error_log('IDC: Photo uploaded successfully - ID: ' . $attachment_id);

        return [
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'file' => $uploaded_file['file'],
        ];
    }

    /* ---------- student dashboard endpoints ---------- */
    public function get_student_print_status(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        // Check if user has a card record and its print approval status
        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user->ID);
        
        $can_print = false;
        $has_requested = false;
        
        if ($customer) {
            // Check if they have print approval (you can add a 'print_approved' field to DB)
            $print_meta = get_user_meta($user->ID, 'idc_print_approved', true);
            $request_meta = get_user_meta($user->ID, 'idc_print_requested', true);
            
            $can_print = ($print_meta === 'yes');
            $has_requested = ($request_meta === 'yes' && !$can_print);
        }
        
        return [
            'can_print' => $can_print,
            'has_requested' => $has_requested,
            'customer_exists' => !!$customer
        ];
    }

    public function student_request_print(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        // Check if user has a card record
        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user->ID);
        
        if (!$customer) {
            return new \WP_Error('no_card', 'Please create your ID card first', ['status' => 400]);
        }

        // Mark print as requested
        update_user_meta($user->ID, 'idc_print_requested', 'yes');
        update_user_meta($user->ID, 'idc_print_request_time', current_time('mysql'));
        
        // Add audit log
        $db->add_audit_log([
            'user_id' => $user->ID,
            'action' => 'print_requested', 
            'details' => "Student {$user->display_name} requested print approval for card ID: {$customer->id}",
            'customer_id' => $customer->id
        ]);

        return ['success' => true, 'message' => 'Print request submitted successfully'];
    }

    /* ---------- Student Card Management ---------- */
    public function get_student_card(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user->ID);
        
        if (!$customer) {
            return ['exists' => false, 'data' => null];
        }

        // Add photo URL if photo_media_id exists
        if ($customer->photo_media_id) {
            $customer->photo = wp_get_attachment_url($customer->photo_media_id);
        }

        return ['exists' => true, 'data' => $customer];
    }

    public function create_student_card(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        $db = new DB();
        
        // Check if card already exists
        $existing = $db->get_customer_by_wp_user($user->ID);
        if ($existing) {
            return new \WP_Error('card_exists', 'Card already exists, use PUT to update', ['status' => 400]);
        }

        $params = $req->get_json_params();
        $sanitized = $this->sanitize_customer($params);
        
        // Ensure wp_user_id is set
        $sanitized['wp_user_id'] = $user->ID;
        $sanitized['status'] = 'inactive'; // Students start inactive until approved
        
        $id = $db->create_customer($sanitized);
        
        return ['success' => true, 'id' => $id];
    }

    public function update_student_card(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user->ID);
        
        if (!$customer) {
            return new \WP_Error('no_card', 'No card found to update', ['status' => 404]);
        }

        $params = $req->get_json_params();
        $sanitized = $this->sanitize_customer($params);
        
        // Don't allow students to change their status or wp_user_id
        unset($sanitized['status'], $sanitized['wp_user_id']);
        
        $success = $db->update_customer($customer->id, $sanitized);
        
        return ['success' => $success];
    }

    public function change_student_password(WP_REST_Request $req) {
        $user = wp_get_current_user();
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('permission_denied', 'Access denied', ['status' => 403]);
        }

        $params = $req->get_json_params();
        $old_password = sanitize_text_field($params['old_password'] ?? '');
        $new_password = sanitize_text_field($params['new_password'] ?? '');
        $confirm_password = sanitize_text_field($params['confirm_password'] ?? '');

        // Validate required fields
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            return new \WP_Error('missing_fields', 'All fields are required', ['status' => 400]);
        }

        // Check if new passwords match
        if ($new_password !== $confirm_password) {
            return new \WP_Error('password_mismatch', 'New passwords do not match', ['status' => 400]);
        }

        // Verify old password
        if (!wp_check_password($old_password, $user->user_pass, $user->ID)) {
            return new \WP_Error('invalid_password', 'Current password is incorrect', ['status' => 400]);
        }

        // Update password
        wp_set_password($new_password, $user->ID);

        // Log the password change
        $db = new DB();
        $db->add_audit_log([
            'user_id' => $user->ID,
            'action' => 'password_changed',
            'details' => "Student {$user->display_name} changed their password",
            'customer_id' => null
        ]);

        return ['success' => true, 'message' => 'Password changed successfully'];
    }

    /* ---------- Print Request Management ---------- */
    public function get_print_requests(WP_REST_Request $req) {
        // Get all users with pending print requests
        $users_with_requests = get_users([
            'meta_key' => 'idc_print_requested',
            'meta_value' => 'yes',
            'role' => 'idc_viewer'
        ]);

        $db = new DB();
        $requests = [];

        foreach ($users_with_requests as $user) {
            $customer = $db->get_customer_by_wp_user($user->ID);
            $request_time = get_user_meta($user->ID, 'idc_print_request_time', true);
            $approved = get_user_meta($user->ID, 'idc_print_approved', true) === 'yes';

            $requests[] = [
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'request_time' => $request_time,
                'approved' => $approved,
                'customer_data' => $customer ? [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'national_id' => $customer->national_id,
                    'job_title' => $customer->job_title,
                    'photo' => $customer->photo_media_id ? wp_get_attachment_url($customer->photo_media_id) : null,
                    'status' => $customer->status
                ] : null
            ];
        }

        // Sort by request time, newest first
        usort($requests, function($a, $b) {
            return strtotime($b['request_time']) - strtotime($a['request_time']);
        });

        return $requests;
    }

    public function approve_print_request(WP_REST_Request $req) {
        $user_id = (int) $req->get_param('user_id');
        
        if (!$user_id) {
            return new \WP_Error('invalid_user', 'Invalid user ID', ['status' => 400]);
        }

        $user = get_user_by('ID', $user_id);
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('user_not_found', 'Student not found', ['status' => 404]);
        }

        // Approve the print request
        update_user_meta($user_id, 'idc_print_approved', 'yes');
        update_user_meta($user_id, 'idc_print_requested', 'no'); // Clear request flag
        update_user_meta($user_id, 'idc_print_approved_time', current_time('mysql'));
        update_user_meta($user_id, 'idc_print_approved_by', get_current_user_id());

        // Update customer status to active
        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user_id);
        if ($customer) {
            $db->update_customer($customer->id, ['status' => 'active']);
        }

        // Add audit log
        $current_user = wp_get_current_user();
        $db->add_audit_log([
            'user_id' => get_current_user_id(),
            'action' => 'print_approved',
            'details' => "{$current_user->display_name} approved print request for student {$user->display_name}",
            'customer_id' => $customer ? $customer->id : null
        ]);

        return ['success' => true, 'message' => 'Print request approved successfully'];
    }

    public function reject_print_request(WP_REST_Request $req) {
        $user_id = (int) $req->get_param('user_id');
        
        if (!$user_id) {
            return new \WP_Error('invalid_user', 'Invalid user ID', ['status' => 400]);
        }

        $user = get_user_by('ID', $user_id);
        if (!$user || !in_array('idc_viewer', $user->roles)) {
            return new \WP_Error('user_not_found', 'Student not found', ['status' => 404]);
        }

        // Reject the print request
        update_user_meta($user_id, 'idc_print_approved', 'no');
        update_user_meta($user_id, 'idc_print_requested', 'no');
        update_user_meta($user_id, 'idc_print_rejected_time', current_time('mysql'));
        update_user_meta($user_id, 'idc_print_rejected_by', get_current_user_id());

        // Add audit log
        $current_user = wp_get_current_user();
        $db = new DB();
        $customer = $db->get_customer_by_wp_user($user_id);
        
        $db->add_audit_log([
            'user_id' => get_current_user_id(),
            'action' => 'print_rejected',
            'details' => "{$current_user->display_name} rejected print request for student {$user->display_name}",
            'customer_id' => $customer ? $customer->id : null
        ]);

        return ['success' => true, 'message' => 'Print request rejected'];
    }
}
