<?php
namespace IDC;

if (!defined('ABSPATH')) { exit; }

class Deactivator {
    public static function deactivate(): void {
        // Keep roles, caps, and data. Nothing destructive on deactivate.
        flush_rewrite_rules(false);
    }
}
