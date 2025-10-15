<?php
namespace IDC;

use wpdb;

if (!defined('ABSPATH')) { exit; }

/**
 * Low-level DB helper (custom tables).
 * All methods return arrays / bools / ints (no WP_Error here).
 */
class DB {
    private wpdb $db;
    private string $t_customers;
    private string $t_cards;
    private string $t_audit;

    public function __construct() {
        global $wpdb; $this->db = $wpdb;
        $this->t_customers = $wpdb->prefix . 'idc_customers';
        $this->t_cards     = $wpdb->prefix . 'idc_cards';
        $this->t_audit     = $wpdb->prefix . 'idc_audit_log';
    }

    /* ---------------- Customers ---------------- */

    public function create_customer(array $row): int {
        $defaults = [
            'first_name'     => null,
            'last_name'      => null,
            'full_name'      => '',
            'national_id'    => '',
            'dob'            => '',
            'country'        => null,
            'issued_on'      => null,
            'passport_no'    => null,
            'photo_media_id' => null,
            'job_title'      => 'Student',
            'status'         => 'active',
        ];
        $row = array_merge($defaults, $row);
        $this->db->insert($this->t_customers, $row);
        $id = (int) $this->db->insert_id;
        $this->audit('customer', $id, 'create', $row);
        return $id;
    }

    public function update_customer(int $id, array $patch): bool {
        $before = $this->get_customer($id);
        if (!$before) return false;
        $patch['updated_at'] = current_time('mysql');
        $ok = (bool) $this->db->update($this->t_customers, $patch, ['id' => $id]);
        if ($ok) $this->audit('customer', $id, 'update', self::diff($before, $patch));
        return $ok;
    }

    public function get_customer(int $id): ?array {
        $sql = $this->db->prepare("SELECT * FROM {$this->t_customers} WHERE id=%d", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function find_customers(string $q = '', int $limit = 50): array {
        $like = '%' . $this->db->esc_like($q) . '%';
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->t_customers}
             WHERE full_name LIKE %s OR national_id LIKE %s OR passport_no LIKE %s
             ORDER BY created_at DESC LIMIT %d",
            $like, $like, $like, $limit
        );
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    public function find_customer_by_nid(string $nid): ?array {
        $sql = $this->db->prepare("SELECT * FROM {$this->t_customers} WHERE national_id=%s LIMIT 1", $nid);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function get_customer_by_wp_user(int $wp_user_id): ?object {
        $sql = $this->db->prepare("SELECT * FROM {$this->t_customers} WHERE wp_user_id=%d LIMIT 1", $wp_user_id);
        $row = $this->db->get_row($sql);
        return $row ?: null;
    }

    public function delete_customer(int $id): bool {
        $before = $this->get_customer($id);
        if (!$before) return false;
        
        // Also delete associated cards
        $this->db->delete($this->t_cards, ['customer_id' => $id]);
        
        // Delete the customer
        $ok = (bool) $this->db->delete($this->t_customers, ['id' => $id]);
        if ($ok) {
            $this->audit('customer', $id, 'delete', $before);
        }
        return $ok;
    }

    public function bulk_delete_customers(array $ids): int {
        $deleted_count = 0;
        foreach ($ids as $id) {
            if ($this->delete_customer((int)$id)) {
                $deleted_count++;
            }
        }
        return $deleted_count;
    }

    /* ---------------- Cards & Versions ---------------- */

    public function create_card(int $customer_id, array $row): int {
        $row = array_merge([
            'customer_id'    => $customer_id,
            'version'        => 1,
            'qr_payload'     => null,
            'front_image_uri'=> null,
            'back_image_uri' => null,
            'printed_at'     => current_time('mysql'),
            'printed_by'     => get_current_user_id(),
            'note'           => null,
        ], $row);

        $this->db->insert($this->t_cards, $row);
        $id = (int) $this->db->insert_id;
        $this->audit('card', $id, 'create', $row);
        return $id;
    }

    public function bump_card_version(int $id, array $patch): bool {
        $before = $this->get_card($id);
        if (!$before) return false;
        $patch['version'] = (int) $before['version'] + 1;
        $ok = (bool) $this->db->update($this->t_cards, $patch, ['id' => $id]);
        if ($ok) $this->audit('card', $id, 'update', self::diff($before, $patch));
        return $ok;
    }

    public function get_card(int $id): ?array {
        $sql = $this->db->prepare("SELECT * FROM {$this->t_cards} WHERE id=%d", $id);
        $row = $this->db->get_row($sql, ARRAY_A);
        return $row ?: null;
    }

    public function list_cards_by_customer(int $customer_id): array {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->t_cards} WHERE customer_id=%d ORDER BY version DESC",
            $customer_id
        );
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    /* ---------------- Audit ---------------- */

    public function audit(string $entity, int $entity_id, string $action, $diff): void {
        $this->db->insert($this->t_audit, [
            'entity'       => $entity,
            'entity_id'    => $entity_id,
            'action'       => $action,
            'actor_user_id'=> get_current_user_id(),
            'diff_json'    => is_string($diff) ? $diff : wp_json_encode($diff),
        ]);
    }

    public function add_audit_log(array $data): void {
        // Extract data with defaults
        $user_id = $data['user_id'] ?? get_current_user_id();
        $action = $data['action'] ?? 'unknown';
        $details = $data['details'] ?? '';
        $customer_id = $data['customer_id'] ?? null;
        
        // Insert into audit log table
        $this->db->insert($this->t_audit, [
            'entity'        => 'system',
            'entity_id'     => $customer_id ?: 0,
            'action'        => $action,
            'actor_user_id' => $user_id,
            'diff_json'     => $details,
        ]);
    }

    public function list_audit(string $entity = '', int $entity_id = 0, int $limit = 200): array {
        if ($entity && $entity_id) {
            $sql = $this->db->prepare(
                "SELECT * FROM {$this->t_audit} WHERE entity=%s AND entity_id=%d ORDER BY id DESC LIMIT %d",
                $entity, $entity_id, $limit
            );
        } else {
            $sql = $this->db->prepare("SELECT * FROM {$this->t_audit} ORDER BY id DESC LIMIT %d", $limit);
        }
        return $this->db->get_results($sql, ARRAY_A) ?: [];
    }

    /* ---------------- Utils ---------------- */

    public static function diff(array $old, array $patch): array {
        $d = [];
        foreach ($patch as $k => $v) {
            $ov = $old[$k] ?? null;
            if ($ov !== $v) $d[$k] = ['from' => $ov, 'to' => $v];
        }
        return $d;
    }
}
