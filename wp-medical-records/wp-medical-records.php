<?php
/**
 * Plugin Name: WP Medical Records
 * Plugin URI: https://example.com/wp-medical-records
 * Description: A comprehensive medical records plugin integrated with Bookly for managing patient records, visits, and medical history.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-medical-records
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WMR_VERSION', '1.0.0');
define('WMR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WMR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WMR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class WP_Medical_Records {
    
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
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // AJAX actions for admin
        add_action('wp_ajax_wmr_get_patients', array($this, 'ajax_get_patients'));
        add_action('wp_ajax_wmr_create_record_form', array($this, 'ajax_create_record_form'));
        add_action('wp_ajax_wmr_create_record', array($this, 'ajax_create_record'));
        add_action('wp_ajax_wmr_view_record', array($this, 'ajax_view_record'));
        add_action('wp_ajax_wmr_delete_record', array($this, 'ajax_delete_record'));
        add_action('wp_ajax_wmr_get_visits', array($this, 'ajax_get_visits'));
        add_action('wp_ajax_wmr_view_visit', array($this, 'ajax_view_visit'));
        add_action('wp_ajax_wmr_create_visit', array($this, 'ajax_create_visit'));
        add_action('wp_ajax_wmr_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_wmr_save_medical_history', array($this, 'ajax_save_medical_history'));
        add_action('wp_ajax_wmr_get_medical_history', array($this, 'ajax_get_medical_history'));
        
        // AJAX actions for frontend (patients)
        add_action('wp_ajax_nopriv_wmr_get_patient_visits', array($this, 'ajax_get_patient_visits'));
        add_action('wp_ajax_nopriv_wmr_view_patient_visit', array($this, 'ajax_view_patient_visit'));
        add_action('wp_ajax_nopriv_wmr_save_patient_medical_history', array($this, 'ajax_save_patient_medical_history'));
        add_action('wp_ajax_nopriv_wmr_upload_patient_file', array($this, 'ajax_upload_patient_file'));
        
        // Shortcodes
        add_shortcode('wmr_doctor_panel', array($this, 'doctor_panel_shortcode'));
        add_shortcode('wmr_patient_panel', array($this, 'patient_panel_shortcode'));
    }
    
    public function init() {
        load_plugin_textdomain('wp-medical-records', false, dirname(WMR_PLUGIN_BASENAME) . '/languages');
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create medical records table
        $sql_records = "CREATE TABLE {$wpdb->prefix}wmr_medical_records (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            patient_id bigint(20) UNSIGNED NOT NULL,
            doctor_id bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";
        
        // Create visits table
        $sql_visits = "CREATE TABLE {$wpdb->prefix}wmr_visits (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            record_id bigint(20) UNSIGNED NOT NULL,
            doctor_id bigint(20) UNSIGNED NOT NULL,
            clinic_id bigint(20) UNSIGNED NOT NULL,
            visit_date datetime NOT NULL,
            complaint text,
            diagnosis text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY record_id (record_id),
            KEY doctor_id (doctor_id),
            KEY clinic_id (clinic_id)
        ) $charset_collate;";
        
        // Create medicines table
        $sql_medicines = "CREATE TABLE {$wpdb->prefix}wmr_medicines (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visit_id bigint(20) UNSIGNED NOT NULL,
            medicine_name varchar(255) NOT NULL,
            dosage varchar(255),
            PRIMARY KEY (id),
            KEY visit_id (visit_id)
        ) $charset_collate;";
        
        // Create files table
        $sql_files = "CREATE TABLE {$wpdb->prefix}wmr_files (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visit_id bigint(20) UNSIGNED,
            record_id bigint(20) UNSIGNED,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(100),
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visit_id (visit_id),
            KEY record_id (record_id)
        ) $charset_collate;";
        
        // Create medical history table
        $sql_history = "CREATE TABLE {$wpdb->prefix}wmr_medical_history (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            record_id bigint(20) UNSIGNED NOT NULL,
            blood_type varchar(10),
            diseases text,
            age int(3),
            current_medications text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY record_id (record_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_records);
        dbDelta($sql_visits);
        dbDelta($sql_medicines);
        dbDelta($sql_files);
        dbDelta($sql_history);
        
        update_option('wmr_db_version', WMR_VERSION);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Medical Records', 'wp-medical-records'),
            __('Medical Records', 'wp-medical-records'),
            'manage_options',
            'wmr-admin',
            array($this, 'admin_page_callback'),
            'dashicons-heart-pulse',
            30
        );
    }
    
    public function admin_page_callback() {
        include WMR_PLUGIN_DIR . 'includes/admin/views/admin-dashboard.php';
    }
    
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wmr-admin') {
            return;
        }
        
        wp_enqueue_style('wmr-admin-style', WMR_PLUGIN_URL . 'assets/css/admin-style.css', array(), WMR_VERSION);
        wp_enqueue_style('wmr-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
        wp_enqueue_style('wmr-jalali-datepicker', 'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/css/persian-date.min.css', array(), '1.1.0');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('wmr-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_script('wmr-jalali-datepicker', 'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/js/persian-date.min.js', array('jquery'), '1.1.0', true);
        wp_enqueue_script('wmr-jalali-datepicker-core', 'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js', array('jquery', 'wmr-jalali-datepicker'), '1.2.0', true);
        wp_enqueue_script('wmr-admin-script', WMR_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'wmr-select2', 'wmr-jalali-datepicker'), WMR_VERSION, true);
        
        wp_localize_script('wmr-admin-script', 'wmrAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wmr_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this record?', 'wp-medical-records'),
                'error' => __('An error occurred. Please try again.', 'wp-medical-records'),
                'success' => __('Operation completed successfully.', 'wp-medical-records'),
                'noPatients' => __('No patients found.', 'wp-medical-records'),
                'loading' => __('Loading...', 'wp-medical-records'),
            )
        ));
    }
    
    public function frontend_enqueue_scripts() {
        wp_enqueue_style('wmr-frontend-style', WMR_PLUGIN_URL . 'assets/css/frontend-style.css', array(), WMR_VERSION);
        wp_enqueue_style('wmr-jalali-datepicker', 'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/css/persian-date.min.css', array(), '1.1.0');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('wmr-jalali-datepicker', 'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/js/persian-date.min.js', array('jquery'), '1.1.0', true);
        wp_enqueue_script('wmr-jalali-datepicker-core', 'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js', array('jquery', 'wmr-jalali-datepicker'), '1.2.0', true);
        wp_enqueue_script('wmr-frontend-script', WMR_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery', 'wmr-jalali-datepicker'), WMR_VERSION, true);
        
        wp_localize_script('wmr-frontend-script', 'wmrFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wmr_frontend_nonce'),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'wp-medical-records'),
                'success' => __('Operation completed successfully.', 'wp-medical-records'),
                'loading' => __('Loading...', 'wp-medical-records'),
            )
        ));
    }
    
    public function doctor_panel_shortcode($atts) {
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/views/doctor-panel.php';
        return ob_get_clean();
    }
    
    public function patient_panel_shortcode($atts) {
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/views/patient-panel.php';
        return ob_get_clean();
    }
    
    // AJAX Handlers
    public function ajax_get_patients() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $patients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bookly_customers ORDER BY full_name ASC");
        
        wp_send_json_success($patients);
    }
    
    public function ajax_get_doctors() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        global $wpdb;
        $doctors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bookly_staff ORDER BY full_name ASC");
        
        wp_send_json_success($doctors);
    }
    
    public function ajax_get_clinics() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        global $wpdb;
        $clinics = $wpdb->get_results("SELECT DISTINCT bc.* FROM {$wpdb->prefix}bookly_categories bc 
            INNER JOIN {$wpdb->prefix}wmr_visits wv ON bc.id = wv.clinic_id 
            ORDER BY bc.name ASC");
        
        wp_send_json_success($clinics);
    }
    
    public function ajax_create_record_form() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/admin/views/create-record-form.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_create_record() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $patient_id = intval($_POST['patient_id']);
        $doctor_id = get_current_user_id();
        
        // Check if record already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_records WHERE patient_id = %d AND status = 'active'",
            $patient_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => __('Medical record already exists for this patient', 'wp-medical-records')));
        }
        
        $wpdb->insert(
            "{$wpdb->prefix}wmr_medical_records",
            array(
                'patient_id' => $patient_id,
                'doctor_id' => $doctor_id,
                'created_at' => current_time('mysql'),
                'status' => 'active'
            )
        );
        
        $record_id = $wpdb->insert_id;
        
        if ($record_id) {
            wp_send_json_success(array(
                'message' => __('Medical record created successfully', 'wp-medical-records'),
                'record_id' => $record_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create medical record', 'wp-medical-records')));
        }
    }
    
    public function ajax_view_record() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_medical_records WHERE id = %d",
            $record_id
        ));
        
        if (!$record) {
            wp_send_json_error(array('message' => __('Record not found', 'wp-medical-records')));
        }
        
        // Get patient info
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_customers WHERE id = %d",
            $record->patient_id
        ));
        
        // Get doctor info
        $doctor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_staff WHERE id = %d",
            $record->doctor_id
        ));
        
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/admin/views/view-record.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_delete_record() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        
        $wpdb->update(
            "{$wpdb->prefix}wmr_medical_records",
            array('status' => 'deleted'),
            array('id' => $record_id)
        );
        
        wp_send_json_success(array('message' => __('Record deleted successfully', 'wp-medical-records')));
    }
    
    public function ajax_get_visits() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : 0;
        $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $query = "SELECT wv.*, bc.name as clinic_name, bs.full_name as doctor_name 
            FROM {$wpdb->prefix}wmr_visits wv
            LEFT JOIN {$wpdb->prefix}bookly_categories bc ON wv.clinic_id = bc.id
            LEFT JOIN {$wpdb->prefix}bookly_staff bs ON wv.doctor_id = bs.id
            WHERE wv.record_id = %d";
        
        $params = array($record_id);
        
        if ($clinic_id > 0) {
            $query .= " AND wv.clinic_id = %d";
            $params[] = $clinic_id;
        }
        
        if ($doctor_id > 0) {
            $query .= " AND wv.doctor_id = %d";
            $params[] = $doctor_id;
        }
        
        if ($date_from) {
            $query .= " AND DATE(wv.visit_date) >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $query .= " AND DATE(wv.visit_date) <= %s";
            $params[] = $date_to;
        }
        
        $query .= " ORDER BY wv.visit_date DESC";
        
        $visits = $wpdb->get_results($wpdb->prepare($query, $params));
        
        wp_send_json_success($visits);
    }
    
    public function ajax_view_visit() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $visit_id = intval($_POST['visit_id']);
        
        $visit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_visits WHERE id = %d",
            $visit_id
        ));
        
        if (!$visit) {
            wp_send_json_error(array('message' => __('Visit not found', 'wp-medical-records')));
        }
        
        // Get clinic info
        $clinic = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_categories WHERE id = %d",
            $visit->clinic_id
        ));
        
        // Get doctor info
        $doctor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_staff WHERE id = %d",
            $visit->doctor_id
        ));
        
        // Get medicines
        $medicines = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_medicines WHERE visit_id = %d",
            $visit_id
        ));
        
        // Get files
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_files WHERE visit_id = %d",
            $visit_id
        ));
        
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/admin/views/view-visit.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_create_visit() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        $clinic_id = intval($_POST['clinic_id']);
        $complaint = sanitize_textarea_field($_POST['complaint']);
        $diagnosis = sanitize_textarea_field($_POST['diagnosis']);
        $medicines = isset($_POST['medicines']) ? $_POST['medicines'] : array();
        $files = isset($_POST['files']) ? $_POST['files'] : array();
        
        $doctor_id = get_current_user_id();
        $visit_date = current_time('mysql');
        
        $wpdb->insert(
            "{$wpdb->prefix}wmr_visits",
            array(
                'record_id' => $record_id,
                'doctor_id' => $doctor_id,
                'clinic_id' => $clinic_id,
                'visit_date' => $visit_date,
                'complaint' => $complaint,
                'diagnosis' => $diagnosis,
                'created_at' => $visit_date
            )
        );
        
        $visit_id = $wpdb->insert_id;
        
        if ($visit_id) {
            // Insert medicines
            if (!empty($medicines)) {
                foreach ($medicines as $medicine) {
                    $wpdb->insert(
                        "{$wpdb->prefix}wmr_medicines",
                        array(
                            'visit_id' => $visit_id,
                            'medicine_name' => sanitize_text_field($medicine['name']),
                            'dosage' => sanitize_text_field($medicine['dosage'])
                        )
                    );
                }
            }
            
            // Insert files
            if (!empty($files)) {
                foreach ($files as $file) {
                    $wpdb->insert(
                        "{$wpdb->prefix}wmr_files",
                        array(
                            'visit_id' => $visit_id,
                            'file_name' => sanitize_text_field($file['name']),
                            'file_path' => esc_url_raw($file['path']),
                            'file_type' => sanitize_text_field($file['type'])
                        )
                    );
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Visit created successfully', 'wp-medical-records'),
                'visit_id' => $visit_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create visit', 'wp-medical-records')));
        }
    }
    
    public function ajax_upload_file() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'wp-medical-records')));
        }
        
        $file = $_FILES['file'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type', 'wp-medical-records')));
        }
        
        $upload_dir = wp_upload_dir();
        $wmr_dir = $upload_dir['basedir'] . '/wmr-files/';
        
        if (!file_exists($wmr_dir)) {
            wp_mkdir_p($wmr_dir);
        }
        
        $filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $filepath = $wmr_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_url = $upload_dir['baseurl'] . '/wmr-files/' . $filename;
            wp_send_json_success(array(
                'name' => $file['name'],
                'path' => $file_url,
                'type' => $file['type']
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to upload file', 'wp-medical-records')));
        }
    }
    
    public function ajax_save_medical_history() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        $blood_type = sanitize_text_field($_POST['blood_type']);
        $diseases = isset($_POST['diseases']) ? implode(',', $_POST['diseases']) : '';
        $age = intval($_POST['age']);
        $current_medications = isset($_POST['current_medications']) ? $_POST['current_medications'] : array();
        $files = isset($_POST['files']) ? $_POST['files'] : array();
        
        // Check if history exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_history WHERE record_id = %d",
            $record_id
        ));
        
        $medications_json = json_encode($current_medications);
        
        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}wmr_medical_history",
                array(
                    'blood_type' => $blood_type,
                    'diseases' => $diseases,
                    'age' => $age,
                    'current_medications' => $medications_json,
                    'updated_at' => current_time('mysql')
                ),
                array('record_id' => $record_id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}wmr_medical_history",
                array(
                    'record_id' => $record_id,
                    'blood_type' => $blood_type,
                    'diseases' => $diseases,
                    'age' => $age,
                    'current_medications' => $medications_json,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        // Insert files
        if (!empty($files)) {
            foreach ($files as $file) {
                $wpdb->insert(
                    "{$wpdb->prefix}wmr_files",
                    array(
                        'record_id' => $record_id,
                        'file_name' => sanitize_text_field($file['name']),
                        'file_path' => esc_url_raw($file['path']),
                        'file_type' => sanitize_text_field($file['type'])
                    )
                );
            }
        }
        
        wp_send_json_success(array('message' => __('Medical history saved successfully', 'wp-medical-records')));
    }
    
    public function ajax_get_medical_history() {
        check_ajax_referer('wmr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !wmr_is_doctor()) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-medical-records')));
        }
        
        global $wpdb;
        $record_id = intval($_POST['record_id']);
        
        $history = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_medical_history WHERE record_id = %d",
            $record_id
        ));
        
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_files WHERE record_id = %d",
            $record_id
        ));
        
        wp_send_json_success(array(
            'history' => $history,
            'files' => $files
        ));
    }
    
    // Frontend AJAX handlers for patients
    public function ajax_get_patient_visits() {
        check_ajax_referer('wmr_frontend_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'wp-medical-records')));
        }
        
        global $wpdb;
        
        // Find patient ID from Bookly
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bookly_customers WHERE wp_user_id = %d",
            $user_id
        ));
        
        if (!$patient) {
            wp_send_json_error(array('message' => __('Patient record not found', 'wp-medical-records')));
        }
        
        // Find medical record
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_records WHERE patient_id = %d AND status = 'active'",
            $patient->id
        ));
        
        if (!$record) {
            wp_send_json_error(array('message' => __('No medical record found', 'wp-medical-records')));
        }
        
        $visits = $wpdb->get_results($wpdb->prepare(
            "SELECT wv.*, bc.name as clinic_name, bs.full_name as doctor_name 
                FROM {$wpdb->prefix}wmr_visits wv
                LEFT JOIN {$wpdb->prefix}bookly_categories bc ON wv.clinic_id = bc.id
                LEFT JOIN {$wpdb->prefix}bookly_staff bs ON wv.doctor_id = bs.id
                WHERE wv.record_id = %d
                ORDER BY wv.visit_date DESC",
            $record->id
        ));
        
        wp_send_json_success($visits);
    }
    
    public function ajax_view_patient_visit() {
        check_ajax_referer('wmr_frontend_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'wp-medical-records')));
        }
        
        global $wpdb;
        $visit_id = intval($_POST['visit_id']);
        
        $visit = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_visits WHERE id = %d",
            $visit_id
        ));
        
        if (!$visit) {
            wp_send_json_error(array('message' => __('Visit not found', 'wp-medical-records')));
        }
        
        // Verify patient has access to this visit
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bookly_customers WHERE wp_user_id = %d",
            $user_id
        ));
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_records WHERE patient_id = %d AND status = 'active'",
            $patient->id
        ));
        
        if (!$record || $record->id != $visit->record_id) {
            wp_send_json_error(array('message' => __('Access denied', 'wp-medical-records')));
        }
        
        // Get clinic info
        $clinic = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_categories WHERE id = %d",
            $visit->clinic_id
        ));
        
        // Get doctor info
        $doctor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bookly_staff WHERE id = %d",
            $visit->doctor_id
        ));
        
        // Get medicines
        $medicines = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_medicines WHERE visit_id = %d",
            $visit_id
        ));
        
        // Get files
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wmr_files WHERE visit_id = %d",
            $visit_id
        ));
        
        ob_start();
        include WMR_PLUGIN_DIR . 'includes/views/patient-view-visit.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_save_patient_medical_history() {
        check_ajax_referer('wmr_frontend_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'wp-medical-records')));
        }
        
        global $wpdb;
        
        // Find patient ID
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bookly_customers WHERE wp_user_id = %d",
            $user_id
        ));
        
        if (!$patient) {
            wp_send_json_error(array('message' => __('Patient record not found', 'wp-medical-records')));
        }
        
        // Find medical record
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_records WHERE patient_id = %d AND status = 'active'",
            $patient->id
        ));
        
        if (!$record) {
            wp_send_json_error(array('message' => __('No medical record found', 'wp-medical-records')));
        }
        
        $blood_type = sanitize_text_field($_POST['blood_type']);
        $diseases = isset($_POST['diseases']) ? implode(',', $_POST['diseases']) : '';
        $age = intval($_POST['age']);
        $current_medications = isset($_POST['current_medications']) ? $_POST['current_medications'] : array();
        $files = isset($_POST['files']) ? $_POST['files'] : array();
        
        // Check if history exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}wmr_medical_history WHERE record_id = %d",
            $record->id
        ));
        
        $medications_json = json_encode($current_medications);
        
        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}wmr_medical_history",
                array(
                    'blood_type' => $blood_type,
                    'diseases' => $diseases,
                    'age' => $age,
                    'current_medications' => $medications_json,
                    'updated_at' => current_time('mysql')
                ),
                array('record_id' => $record->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}wmr_medical_history",
                array(
                    'record_id' => $record->id,
                    'blood_type' => $blood_type,
                    'diseases' => $diseases,
                    'age' => $age,
                    'current_medications' => $medications_json,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        // Insert files
        if (!empty($files)) {
            foreach ($files as $file) {
                $wpdb->insert(
                    "{$wpdb->prefix}wmr_files",
                    array(
                        'record_id' => $record->id,
                        'file_name' => sanitize_text_field($file['name']),
                        'file_path' => esc_url_raw($file['path']),
                        'file_type' => sanitize_text_field($file['type'])
                    )
                );
            }
        }
        
        wp_send_json_success(array('message' => __('Medical history saved successfully', 'wp-medical-records')));
    }
    
    public function ajax_upload_patient_file() {
        check_ajax_referer('wmr_frontend_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Please log in', 'wp-medical-records')));
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'wp-medical-records')));
        }
        
        $file = $_FILES['file'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type', 'wp-medical-records')));
        }
        
        $upload_dir = wp_upload_dir();
        $wmr_dir = $upload_dir['basedir'] . '/wmr-files/';
        
        if (!file_exists($wmr_dir)) {
            wp_mkdir_p($wmr_dir);
        }
        
        $filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $filepath = $wmr_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_url = $upload_dir['baseurl'] . '/wmr-files/' . $filename;
            wp_send_json_success(array(
                'name' => $file['name'],
                'path' => $file_url,
                'type' => $file['type']
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to upload file', 'wp-medical-records')));
        }
    }
}

// Helper functions
function wmr_is_doctor() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $doctor = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bookly_staff WHERE wp_user_id = %d",
        $user_id
    ));
    
    return $doctor !== null;
}

function wmr_is_patient() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $patient = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bookly_customers WHERE wp_user_id = %d",
        $user_id
    ));
    
    return $patient !== null;
}

// Initialize plugin
WP_Medical_Records::get_instance();
