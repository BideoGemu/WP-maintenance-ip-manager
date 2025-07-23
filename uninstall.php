<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete the option
delete_option('allowed_maintenance_ips');

// Delete the MU plugin file
$mu_file = WP_CONTENT_DIR . '/mu-plugins/mu-disable-maintenance-by-ip.php';
if (file_exists($mu_file)) {
    @unlink($mu_file);
}