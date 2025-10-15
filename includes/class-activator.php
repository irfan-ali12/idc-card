<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class Activator {
    public static function activate(): void {
        self::create_roles_caps();
        self::create_tables();
        self::seed_options();
        
        // Reset rewrite rules flag to ensure custom login/signup routes work
        delete_option('idc_login_rewrite_flushed');
        
        // Flush rewrite if REST/custom endpoints add rules later
        flush_rewrite_rules(false);
    }

    private static function create_roles_caps(): void {
        // Custom capabilities
        $caps = ['idc_manage', 'idc_edit', 'idc_read'];

        // Add roles (idempotent)
        add_role('idc_admin', 'IDC Admin', [
            'read'         => true,
            'upload_files' => true,
            'idc_manage'   => true,
            'idc_edit'     => true,
            'idc_read'     => true,
        ]);

        add_role('idc_operator', 'IDC Operator', [
            'read'         => true,
            'upload_files' => true,
            'idc_edit'     => true,
            'idc_read'     => true,
        ]);

        add_role('idc_viewer', 'IDC Viewer', [
            'read'     => true,
            'idc_read' => true,
        ]);

        // Map to WP Administrator as well
        if ($admin = get_role('administrator')) {
            foreach ($caps as $cap) { $admin->add_cap($cap); }
            $admin->add_cap('upload_files');
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $t_customers = $wpdb->prefix . 'idc_customers';
        $t_cards     = $wpdb->prefix . 'idc_cards';
        $t_audit     = $wpdb->prefix . 'idc_audit_log';

        $sql1 = "CREATE TABLE {$t_customers} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            full_name VARCHAR(200) NOT NULL,
            national_id VARCHAR(100) NOT NULL,
            dob VARCHAR(50) NOT NULL,
            country VARCHAR(120) NULL,
            issued_on VARCHAR(50) NULL,
            passport_no VARCHAR(100) NULL,
            photo_media_id BIGINT UNSIGNED NULL,
            job_title VARCHAR(100) NULL DEFAULT 'Student',
            status VARCHAR(50) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY k_national_id (national_id)
        ) $charset;";

        $sql2 = "CREATE TABLE {$t_cards} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 1,
            qr_payload LONGTEXT NULL,
            front_image_uri LONGTEXT NULL,
            back_image_uri LONGTEXT NULL,
            printed_at DATETIME NULL,
            printed_by BIGINT UNSIGNED NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY k_customer (customer_id)
        ) $charset;";

        $sql3 = "CREATE TABLE {$t_audit} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity VARCHAR(50) NOT NULL,       -- 'customer' | 'card'
            entity_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,       -- 'create' | 'update' | 'print' | 'delete'
            actor_user_id BIGINT UNSIGNED NULL,
            diff_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY k_entity (entity, entity_id)
        ) $charset;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        
        // Handle database migrations
        self::handle_db_migrations();
    }
    
    public static function check_migrations(): void {
        self::handle_db_migrations();
    }
    
    private static function handle_db_migrations(): void {
        global $wpdb;
        
        $current_version = get_option('idc_db_version', '1.0');
        
        // Migration for version 1.1: Add tracking_id column to customers table (legacy)
        if (version_compare($current_version, '1.1', '<')) {
            $table_name = $wpdb->prefix . 'idc_customers';
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'tracking_id'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD tracking_id VARCHAR(50) NULL AFTER photo_media_id");
                
                // Generate tracking IDs for existing customers
                $customers = $wpdb->get_results("SELECT id FROM {$table_name} WHERE tracking_id IS NULL");
                foreach ($customers as $customer) {
                    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $random = '';
                    for ($i = 0; $i < 8; $i++) {
                        $random .= $chars[rand(0, strlen($chars) - 1)];
                    }
                    $tracking_id = 'SSD-' . $random;
                    $wpdb->update($table_name, ['tracking_id' => $tracking_id], ['id' => $customer->id]);
                }
            }
            
            update_option('idc_db_version', '1.1');
        }
        
        // Migration for version 1.2: Replace tracking_id with job_title
        if (version_compare($current_version, '1.2', '<')) {
            $table_name = $wpdb->prefix . 'idc_customers';
            
            // Check if job_title column exists
            $job_title_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'job_title'");
            
            if (empty($job_title_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD job_title VARCHAR(100) NULL DEFAULT 'Student' AFTER photo_media_id");
            }
            
            // Set default job title for existing customers
            $wpdb->query("UPDATE {$table_name} SET job_title = 'Student' WHERE job_title IS NULL OR job_title = ''");
            
            // Remove tracking_id column if it exists (clean up)
            $tracking_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'tracking_id'");
            if (!empty($tracking_id_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} DROP COLUMN tracking_id");
            }
            
            update_option('idc_db_version', '1.2');
        }

        // Migration for version 1.3: Add wp_user_id column for student dashboard integration
        if (version_compare($current_version, '1.3', '<')) {
            $table_name = $wpdb->prefix . 'idc_customers';
            
            // Check if wp_user_id column exists
            $wp_user_id_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'wp_user_id'");
            
            if (empty($wp_user_id_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD wp_user_id BIGINT UNSIGNED NULL AFTER status");
                $wpdb->query("ALTER TABLE {$table_name} ADD KEY k_wp_user_id (wp_user_id)");
            }
            
            update_option('idc_db_version', '1.3');
        }
    }

    private static function seed_options(): void {
        // Default coordinates (mm). You can fine-tune later in Settings.
        $defaults = [
            'front_png_id' => 0,
            'back_png_id'  => 0,
            'garet_font_id'=> 0,
            'coords' => [
                'name'        => ['top'=>34.0, 'left'=>9.0],
                'national_id' => ['top'=>60.0, 'left'=>8.0],
                'dob'         => ['top'=>68.0, 'left'=>8.0],
                'country'     => ['top'=>76.0, 'left'=>8.0],
                'issued'      => ['top'=>84.0, 'left'=>8.0],
                'passport_no' => ['top'=>92.0, 'left'=>8.0],
                'photo'       => ['top'=>10.5, 'left'=>14.5, 'diameter'=>30.0, 'offset_x'=>0, 'offset_y'=>0],
                // QR default area (can move into settings later if needed)
                'qr'          => ['size'=>18.0, 'bottom'=>30.0, 'left'=>20.0],
            ],
        ];
        if (!get_option('idc_settings')) {
            add_option('idc_settings', $defaults, '', false);
        }
    }
}
