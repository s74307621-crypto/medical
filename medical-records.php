<?php
/**
 * Plugin Name: Medical Records (Bookly Integration)
 * Plugin URI: https://example.com/medical-records
 * Description: مدیریت پرونده‌های پزشکی با یکپارچه‌سازی کامل Bookly - حذف سیستم رزرو و امتیازدهی
 * Version: 3.0.1
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: medilink
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MR_PLUGIN_VERSION', '3.0.1');
define('MR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load text domain on init hook (fix for _load_textdomain_just_in_time warning)
add_action('init', 'mr_load_textdomain');
function mr_load_textdomain() {
    load_plugin_textdomain('medilink', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Include required files
require_once MR_PLUGIN_DIR . 'includes/helpers/functions-bookly.php';
require_once MR_PLUGIN_DIR . 'includes/helpers/functions-display.php';
require_once MR_PLUGIN_DIR . 'includes/core/admin-menu.php';
require_once MR_PLUGIN_DIR . 'includes/roles/class-admin.php';
require_once MR_PLUGIN_DIR . 'includes/roles/class-doctor.php';
require_once MR_PLUGIN_DIR . 'includes/roles/class-patient.php';

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'mr_enqueue_admin_assets');
function mr_enqueue_admin_assets($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'medical-records') === false) {
        return;
    }
    
    wp_enqueue_style(
        'mr-admin-css',
        MR_PLUGIN_URL . 'assets/css/admin.css',
        [],
        MR_PLUGIN_VERSION
    );
}

// Activation hook
register_activation_hook(__FILE__, 'mr_activate');
function mr_activate() {
    // Nothing special needed for activation
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'mr_deactivate');
function mr_deactivate() {
    // Cleanup if needed
}
