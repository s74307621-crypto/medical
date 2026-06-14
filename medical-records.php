<?php
/**
 * Plugin Name: Medical Records (Bookly Integration)
 * Description: مدیریت پرونده‌های پزشکی کاربران - یکپارچه با بوکلی - بدون سیستم رزرو
 * Version: 3.0.0
 * Author: محمدامین سعدی کیا
 * Text Domain: medical-records
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants safely with checks
if (!defined('MR_PLUGIN_DIR')) {
    define('MR_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('MR_PLUGIN_URL')) {
    define('MR_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('MR_PLUGIN_VERSION')) {
    define('MR_PLUGIN_VERSION', '3.0.0');
}
if (!defined('MR_PLUGIN_BASENAME')) {
    define('MR_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// URL of your plugin host JSON endpoint that returns update info
if (!defined('MR_UPDATE_API')) {
    define('MR_UPDATE_API', 'https://your-plugin-host.example.com/updates/medical-records.json');
}

// ========== بارگذاری فایل‌های ضروری وردپرس ==========
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

/**
 * Main Plugin Class
 */
final class Medical_Records_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'), 1);
        add_action('init', array($this, 'load_dependencies'), 1);
        add_action('admin_init', array($this, 'check_database'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function activate() {
        $this->create_tables();
        $this->load_dependencies();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain('medical-records', false, dirname(MR_PLUGIN_BASENAME) . '/languages');
    }

    public function load_dependencies() {
        // Helpers - Load first
        if (file_exists(MR_PLUGIN_DIR . 'includes/helpers/functions-bookly.php')) {
            require_once MR_PLUGIN_DIR . 'includes/helpers/functions-bookly.php';
        }
        if (file_exists(MR_PLUGIN_DIR . 'includes/helpers/functions-display.php')) {
            require_once MR_PLUGIN_DIR . 'includes/helpers/functions-display.php';
        }
        
        // Core
        if (file_exists(MR_PLUGIN_DIR . 'includes/core/admin-menu.php')) {
            require_once MR_PLUGIN_DIR . 'includes/core/admin-menu.php';
        }
        
        // Roles - Check existence before requiring
        if (file_exists(MR_PLUGIN_DIR . 'includes/roles/class-admin.php')) {
            require_once MR_PLUGIN_DIR . 'includes/roles/class-admin.php';
        }
        if (file_exists(MR_PLUGIN_DIR . 'includes/roles/class-doctor.php')) {
            require_once MR_PLUGIN_DIR . 'includes/roles/class-doctor.php';
        }
        if (file_exists(MR_PLUGIN_DIR . 'includes/roles/class-patient.php')) {
            require_once MR_PLUGIN_DIR . 'includes/roles/class-patient.php';
        }
    }

    public function check_database() {
        // Check if tables exist, if not create them (safety net)
        global $wpdb;
        $table_visits = $wpdb->prefix . 'mr_visits';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_visits'") != $table_visits) {
            $this->create_tables();
        }
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_visits = $wpdb->prefix . 'mr_visits';
        $table_files = $wpdb->prefix . 'mr_files';

        $sql_visits = "CREATE TABLE $table_visits (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) NOT NULL,
            doctor_id bigint(20) NOT NULL,
            visit_date datetime NOT NULL,
            chief_complaint text NOT NULL,
            history_of_present_illness text,
            past_medical_history text,
            medications text,
            allergies text,
            physical_exam text,
            diagnosis text,
            treatment_plan text,
            follow_up_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";

        $sql_files = "CREATE TABLE $table_files (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            visit_id bigint(20) NOT NULL,
            file_url varchar(255) NOT NULL,
            file_type varchar(50) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY visit_id (visit_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_visits);
        dbDelta($sql_files);
    }

    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'medical-records') === false && strpos($hook, 'mr-') === false && strpos($hook, 'toplevel_page_medical-records') === false) {
            return;
        }
        
        wp_enqueue_style('mr-admin-css', MR_PLUGIN_URL . 'assets/css/admin.css', [], MR_PLUGIN_VERSION);
    }
}

// Initialize the plugin
function mr_init_plugin() {
    return Medical_Records_Plugin::get_instance();
}
mr_init_plugin();
