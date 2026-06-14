<?php
/**
 * Bookly integration functions for Medical Records plugin
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all doctors from Bookly staff table
 * 
 * @return array List of doctors
 */
function mr_get_bookly_doctors() {
    global $wpdb;
    
    $staff_table = $wpdb->prefix . 'bookly_staff';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $staff_table)) !== $staff_table) {
        return [];
    }
    
    $doctors = $wpdb->get_results("SELECT * FROM {$staff_table} WHERE visibility = 'public' ORDER BY position ASC", ARRAY_A);
    
    return $doctors ? $doctors : [];
}

/**
 * Get doctor by ID from Bookly staff table
 * 
 * @param int $doctor_id Doctor ID
 * @return array|null Doctor data or null if not found
 */
function mr_get_bookly_doctor_by_id($doctor_id) {
    global $wpdb;
    
    $staff_table = $wpdb->prefix . 'bookly_staff';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $staff_table)) !== $staff_table) {
        return null;
    }
    
    $doctor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$staff_table} WHERE id = %d", $doctor_id), ARRAY_A);
    
    return $doctor ?: null;
}

/**
 * Get doctor by WordPress user ID
 * 
 * @param int $wp_user_id WordPress user ID
 * @return array|null Doctor data or null if not found
 */
function mr_get_bookly_doctor_by_wp_user_id($wp_user_id) {
    global $wpdb;
    
    $staff_table = $wpdb->prefix . 'bookly_staff';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $staff_table)) !== $staff_table) {
        return null;
    }
    
    $doctor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$staff_table} WHERE wp_user_id = %d", $wp_user_id), ARRAY_A);
    
    return $doctor ?: null;
}

/**
 * Get all patients from Bookly customers table
 * 
 * @return array List of patients
 */
function mr_get_bookly_patients() {
    global $wpdb;
    
    $customers_table = $wpdb->prefix . 'bookly_customers';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $customers_table)) !== $customers_table) {
        return [];
    }
    
    $patients = $wpdb->get_results("SELECT * FROM {$customers_table} ORDER BY id DESC", ARRAY_A);
    
    return $patients ? $patients : [];
}

/**
 * Get patient by WordPress user ID
 * 
 * @param int $wp_user_id WordPress user ID
 * @return array|null Patient data or null if not found
 */
function mr_get_patient_by_wp_user_id($wp_user_id) {
    global $wpdb;
    
    $customers_table = $wpdb->prefix . 'bookly_customers';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $customers_table)) !== $customers_table) {
        return null;
    }
    
    $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$customers_table} WHERE wp_user_id = %d", $wp_user_id), ARRAY_A);
    
    return $patient ?: null;
}

/**
 * Format doctor name for display
 * 
 * @param array $doctor Doctor data
 * @return string Formatted name
 */
function mr_format_doctor_name($doctor) {
    if (empty($doctor)) {
        return __('Unknown', 'medilink');
    }
    
    return isset($doctor['full_name']) ? $doctor['full_name'] : __('Unknown', 'medilink');
}

/**
 * Format patient name for display
 * 
 * @param array $patient Patient data
 * @return string Formatted name
 */
function mr_format_patient_name($patient) {
    if (empty($patient)) {
        return __('Unknown', 'medilink');
    }
    
    if (!empty($patient['full_name'])) {
        return $patient['full_name'];
    }
    
    $first = $patient['first_name'] ?? '';
    $last = $patient['last_name'] ?? '';
    
    return trim($first . ' ' . $last);
}

/**
 * Get doctor's color for UI styling
 * 
 * @param array $doctor Doctor data
 * @return string Color hex code
 */
function mr_get_doctor_color($doctor) {
    if (empty($doctor) || empty($doctor['color'])) {
        return '#396cf0'; // Default blue
    }
    
    return $doctor['color'];
}

/**
 * Generate select options for doctors dropdown
 * 
 * @param int $selected Selected doctor ID
 * @return string HTML options
 */
function mr_get_doctor_select_options($selected = 0) {
    $doctors = mr_get_bookly_doctors();
    $options = '';
    
    foreach ($doctors as $doctor) {
        $selected_attr = selected($selected, $doctor['id'], false);
        $name = esc_html(mr_format_doctor_name($doctor));
        $options .= '<option value="' . esc_attr($doctor['id']) . '"' . $selected_attr . '>' . $name . '</option>';
    }
    
    return $options;
}
