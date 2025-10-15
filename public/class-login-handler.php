<?php
/**
 * Handle custom login page functionality
 */
class IDC_Login_Handler {
    
    public function __construct() {
        add_action('init', array($this, 'add_login_rewrite_rule'), 10, 0);
        add_filter('query_vars', array($this, 'add_login_query_vars'));
        add_action('template_redirect', array($this, 'handle_login_template'));
        add_action('template_redirect', array($this, 'handle_front_page_redirect'), 1);
        add_action('wp', array($this, 'handle_designer_page_redirect'), 1);
        add_action('wp_loaded', array($this, 'handle_early_designer_redirect'), 1);
        
        // Add admin action to manually flush rewrite rules
        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
        
        // Force flush on plugin activation
        register_activation_hook(IDC_CARD_FILE, array($this, 'force_flush_rules'));
    }
    
    /**
     * Add rewrite rule for custom login page
     */
    public function add_login_rewrite_rule() {
        add_rewrite_rule('^idc-login/?$', 'index.php?idc_login=1', 'top');
        add_rewrite_rule('^idc-signup/?$', 'index.php?idc_signup=1', 'top');
        
        // Debug: Log that rules are being added
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('IDC: Rewrite rules added - login and signup');
        }
        
        // Always flush rewrite rules to ensure they work
        flush_rewrite_rules();
    }
    
    /**
     * Maybe flush rewrite rules in admin
     */
    public function maybe_flush_rewrite_rules() {
        if (isset($_GET['idc_flush_rules']) && current_user_can('manage_options')) {
            flush_rewrite_rules();
            wp_redirect(admin_url('admin.php?page=idc-settings&flushed=1'));
            exit;
        }
    }
    
    /**
     * Force flush rewrite rules
     */
    public function force_flush_rules() {
        delete_option('rewrite_rules');
        flush_rewrite_rules();
    }
    
    /**
     * Add query vars for login page
     */
    public function add_login_query_vars($query_vars) {
        $query_vars[] = 'idc_login';
        $query_vars[] = 'idc_signup';
        return $query_vars;
    }
    
    /**
     * Handle front page redirect when designer is set as front page
     */
    public function handle_front_page_redirect() {
        // Check if we're on the front page
        if (is_front_page() || is_home()) {
            global $post;
            
            // Check if the front page contains the designer shortcode
            if ($post && has_shortcode($post->post_content, 'idc_designer')) {
                $this->redirect_based_on_user_status();
            }
        }
    }
    
    /**
     * Handle designer page redirect (additional layer)
     */
    public function handle_designer_page_redirect() {
        global $post;
        
        // Check if current page has designer shortcode
        if ($post && has_shortcode($post->post_content, 'idc_designer')) {
            $this->redirect_based_on_user_status();
        }
    }
    
    /**
     * Early designer redirect check (wp_loaded hook)
     */
    public function handle_early_designer_redirect() {
        // Only run on frontend
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Check if we're requesting a page that might have designer content
        $request_uri = $_SERVER['REQUEST_URI'];
        if ($request_uri === '/' || strpos($request_uri, 'designer') !== false) {
            // This might be the designer page - check on the next available hook
            add_action('wp', array($this, 'delayed_designer_check'), 5);
        }
    }
    
    /**
     * Delayed designer check after WP is fully loaded
     */
    public function delayed_designer_check() {
        global $post;
        
        if ($post && (has_shortcode($post->post_content, 'idc_designer') || is_front_page())) {
            $this->redirect_based_on_user_status();
        }
    }
    
    /**
     * Common redirect logic based on user authentication status
     */
    private function redirect_based_on_user_status() {
        if (!is_user_logged_in()) {
            // User not logged in - redirect to login
            wp_redirect(home_url('/idc-login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
            exit;
        } elseif (!current_user_can('idc_read')) {
            // User logged in but no permission - redirect to login
            wp_redirect(home_url('/idc-login/?error=no_permission'));
            exit;
        }
        // User is logged in and has permission - let them access the designer
    }
    
    /**
     * Handle login page template
     */
    public function handle_login_template() {
        // Debug: Log query vars
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $login_var = get_query_var('idc_login');
            $signup_var = get_query_var('idc_signup');
            error_log('IDC Debug: login_var=' . $login_var . ', signup_var=' . $signup_var);
        }
        
        if (get_query_var('idc_login')) {
            // If user is already logged in, redirect to appropriate page
            if (is_user_logged_in()) {
                // Check if there's a redirect_to parameter
                $redirect_to = isset($_GET['redirect_to']) ? urldecode($_GET['redirect_to']) : '';
                
                // Validate redirect URL (must be from same domain)
                if ($redirect_to && strpos($redirect_to, home_url()) === 0) {
                    wp_redirect($redirect_to);
                } elseif (current_user_can('idc_manage')) {
                    // Admin users go to admin dashboard
                    wp_redirect(home_url('/admin-dashboard/'));
                } elseif (current_user_can('idc_edit')) {
                    // Operator users go to operator dashboard
                    wp_redirect(home_url('/operator-dashboard/'));
                } elseif (current_user_can('idc_read') && !current_user_can('idc_edit')) {
                    // Viewer/Student users go to student dashboard (has idc_read but not idc_edit)
                    wp_redirect(home_url('/student-dashboard/'));
                } else {
                    // Fallback for other users
                    wp_redirect(home_url('/admin-dashboard/'));
                }
                exit;
            }
            
            // Handle signup form submission
            if ($_POST && isset($_POST['idc_signup_nonce']) && wp_verify_nonce($_POST['idc_signup_nonce'], 'idc_signup')) {
                $this->handle_signup_submission();
            }
            
            // Load the login template
            include_once IDC_CARD_DIR . 'public/templates/login.php';
            exit;
        }
        
        if (get_query_var('idc_signup') || $this->is_signup_url()) {
            // If user is already logged in, redirect to appropriate page
            if (is_user_logged_in()) {
                wp_redirect(home_url('/admin-dashboard/'));
                exit;
            }
            
            // Handle signup form submission
            if ($_POST && isset($_POST['idc_signup_nonce']) && wp_verify_nonce($_POST['idc_signup_nonce'], 'idc_signup')) {
                $this->handle_signup_submission();
            }
            
            // Load the signup template
            include_once IDC_CARD_DIR . 'public/templates/signup.php';
            exit;
        }
    }
    
    /**
     * Check if current URL is signup URL (fallback method)
     */
    private function is_signup_url() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($request_uri, '/idc-signup') !== false);
    }
    
    /**
     * Handle signup form submission
     */
    private function handle_signup_submission() {
        $errors = [];
        
        // Sanitize and validate input
        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        
        if (empty($email) || !is_email($email)) {
            $errors[] = 'Valid email address is required.';
        }
        
        if (email_exists($email)) {
            $errors[] = 'An account with this email already exists.';
        }
        
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        // If no errors, create the user
        if (empty($errors)) {
            // Generate unique username based on email or name
            $username = $this->generate_unique_username($email, $full_name);
            
            $user_data = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'display_name' => $full_name,
                'first_name' => explode(' ', $full_name)[0],
                'last_name' => trim(str_replace(explode(' ', $full_name)[0], '', $full_name)),
                'role' => 'idc_viewer' // Default role for new signups
            );
            
            $user_id = wp_insert_user($user_data);
            
            if (!is_wp_error($user_id)) {
                // Auto-login the user
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                
                // Check for redirect_to parameter (from GET or POST)
                $redirect_to = isset($_POST['redirect_to']) ? urldecode($_POST['redirect_to']) : 
                              (isset($_GET['redirect_to']) ? urldecode($_GET['redirect_to']) : '');
                
                // Validate redirect URL (must be from same domain)
                if ($redirect_to && strpos($redirect_to, home_url()) === 0) {
                    wp_redirect($redirect_to . (strpos($redirect_to, '?') ? '&' : '?') . 'signup=success');
                } else {
                    // Default redirect for new viewers
                    wp_redirect(home_url('/student-dashboard/?signup=success'));
                }
                exit;
            } else {
                $errors[] = 'Registration failed: ' . $user_id->get_error_message();
            }
        }
        
        // Store errors in session for display
        if (!session_id()) {
            session_start();
        }
        $_SESSION['idc_signup_errors'] = $errors;
        $_SESSION['idc_signup_data'] = $_POST; // Preserve form data
    }
    
    /**
     * Generate unique username
     */
    private function generate_unique_username($email, $full_name) {
        // Try email prefix first
        $base_username = sanitize_user(explode('@', $email)[0]);
        
        // If email prefix is too short, use name
        if (strlen($base_username) < 3) {
            $base_username = sanitize_user(str_replace(' ', '', strtolower($full_name)));
        }
        
        // Ensure minimum length
        if (strlen($base_username) < 3) {
            $base_username = 'user';
        }
        
        $username = $base_username;
        $counter = 1;
        
        // Keep adding numbers until we find a unique username
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Get the custom login URL
     */
    public static function get_login_url() {
        return home_url('/idc-login/');
    }
    
    /**
     * Get the custom signup URL
     */
    public static function get_signup_url() {
        return home_url('/idc-signup/');
    }
}

// Initialize the login handler
new IDC_Login_Handler();