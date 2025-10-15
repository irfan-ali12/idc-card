<?php
// By default, we KEEP data to avoid accidental loss. If you want full cleanup, set the flag below.
if ( ! defined('WP_UNINSTALL_PLUGIN') ) { exit; }
$delete_all = false; // change to true to drop tables on uninstall
if ($delete_all) {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}idc_cards");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}idc_customers");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}idc_audit_log");
    delete_option('idc_settings');
}