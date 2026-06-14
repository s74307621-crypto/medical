<?php
/**
 * Plugin Name: Medical Records
 * Description: مدیریت پرونده‌های پزشکی کاربران - یکپارچه با بوکلی
 * Version: 2.0
 * Author: محمدامین سعدی کیا
 * Text Domain: medical-records
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

define('MR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MR_PLUGIN_VERSION', '2.0');

// URL of your plugin host JSON endpoint that returns update info
define('MR_UPDATE_API', 'https://your-plugin-host.example.com/updates/medical-records.json');

require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

// ========== بارگذاری CSS و JavaScript ==========
add_action('wp_enqueue_scripts', 'mr_enqueue_assets');

function mr_enqueue_assets() {
    // Core styles
    wp_enqueue_style('mr-booking-css', MR_PLUGIN_URL . 'assets/css/booking.css', [], MR_PLUGIN_VERSION);
    wp_enqueue_style('mr-frontend-css', MR_PLUGIN_URL . 'assets/css/frontend.css', [], MR_PLUGIN_VERSION);

    // External libraries (Persian/Jalali datepicker and animations) via CDN
    wp_enqueue_style('mr-persian-datepicker-css', 'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css', [], null);
    wp_enqueue_style('mr-animate-css', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', [], null);

    // Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('mr-persian-date', 'https://cdn.jsdelivr.net/npm/persian-date@1.0.6/dist/persian-date.min.js', ['jquery'], null, true);
    wp_enqueue_script('mr-persian-datepicker', 'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js', ['mr-persian-date', 'jquery'], null, true);
    wp_enqueue_script('mr-booking-js', MR_PLUGIN_URL . 'assets/js/booking.js', ['jquery', 'mr-persian-datepicker'], MR_PLUGIN_VERSION, true);

    // Pass ajax URL and nonces to frontend script
    wp_localize_script('mr-booking-js', 'mr_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'booking_nonce' => wp_create_nonce('mr_booking_nonce'),
        'mr_plugin_url' => MR_PLUGIN_URL,
    ]);\
}

// Activation hook
register_activation_hook(__FILE__, 'mr_plugin_activate');

function mr_plugin_activate() {
    // تنظیمات activation
    if (!wp_next_scheduled('mr_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'mr_daily_cleanup');
    }
}

// منوی ادمین
require_once MR_PLUGIN_DIR . 'includes/core/admin-menu.php';

// نقش‌ها
require_once MR_PLUGIN_DIR . 'includes/roles/class-admin.php';
require_once MR_PLUGIN_DIR . 'includes/roles/class-doctor.php';
require_once MR_PLUGIN_DIR . 'includes/roles/class-patient.php';

// کمکی - شامل توابع Bookly
require_once MR_PLUGIN_DIR . 'includes/helpers/functions-display.php';
require_once MR_PLUGIN_DIR . 'includes/helpers/functions-bookly.php';

// نوبت‌گیری (غیرفعال شده - سیستم رزرو حذف شد)
// require_once MR_PLUGIN_DIR . 'includes/booking/class-booking.php';
// require_once MR_PLUGIN_DIR . 'includes/booking/shortcode-booking.php';
// require_once MR_PLUGIN_DIR . 'includes/booking/doctor-settings.php';

// ========== هندلرهای AJAX (غیرفعال شده - سیستم امتیازدهی حذف شد) ==========
// Rating system has been removed as per requirements

// -------------------- Remote update checker --------------------
add_filter('pre_set_site_transient_update_plugins', 'mr_check_for_update');
add_filter('plugins_api', 'mr_plugins_api_handler', 10, 3);

function mr_get_remote_plugin_info() {
    $cache_key = 'mr_update_info';
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_get(MR_UPDATE_API, ['timeout' => 10]);
    if (is_wp_error($response)) {
        return false;
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return false;
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    if (!$data || !isset($data->version)) {
        return false;
    }

    // cache for 12 hours
    set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);
    return $data;
}

function mr_check_for_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_file = plugin_basename(__FILE__);
    $remote = mr_get_remote_plugin_info();
    if (!$remote || empty($remote->version)) {
        return $transient;
    }

    $local_version = MR_PLUGIN_VERSION;
    if (version_compare($remote->version, $local_version, '>')) {
        $update = new stdClass();
        $update->slug = dirname($plugin_file);
        $update->plugin = $plugin_file;
        $update->new_version = $remote->version;
        $update->package = $remote->package ?? '';
        $update->url = $remote->url ?? '';
        $transient->response[ $plugin_file ] = $update;
    }

    return $transient;
}

function mr_plugins_api_handler($res, $action, $args) {
    $plugin_file = plugin_basename(__FILE__);
    $slug = dirname($plugin_file);
    if (empty($args->slug) || $args->slug !== $slug) {
        return $res;
    }

    $remote = mr_get_remote_plugin_info();
    if (!$remote) {
        return $res;
    }

    $info = new stdClass();
    $info->name = $remote->name ?? 'Medical Records';
    $info->slug = $slug;
    $info->version = $remote->version;
    $info->author = $remote->author ?? '';
    $info->homepage = $remote->url ?? '';
    $info->requires = $remote->requires ?? '';
    $info->tested = $remote->tested ?? '';
    $info->download_link = $remote->package ?? '';
    $info->sections = [
        'description' => $remote->sections->description ?? ''
    ];

    return $info;
}
