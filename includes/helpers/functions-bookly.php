<?php
/**
 * Helper functions for Medical Records plugin
 * Includes Bookly integration for doctors and patients
 * 
 * @package Medical_Records
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get doctors from Bookly staff table
 * 
 * @return array Array of doctor objects with full_name, email, phone, etc.
 */
function mr_get_bookly_doctors() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_staff';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return [];
    }
    
    $results = $wpdb->get_results(
        "SELECT * FROM {$table_name} WHERE visibility = 'public' ORDER BY position ASC",
        ARRAY_A
    );
    
    if (empty($results)) {
        return [];
    }
    
    return $results;
}

/**
 * Get a single doctor by ID from Bookly
 * 
 * @param int $doctor_id
 * @return array|null Doctor data or null if not found
 */
function mr_get_bookly_doctor_by_id($doctor_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_staff';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return null;
    }
    
    $result = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $doctor_id),
        ARRAY_A
    );
    
    return $result ?: null;
}

/**
 * Get patients from Bookly customers table
 * 
 * @return array Array of patient objects
 */
function mr_get_bookly_patients() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_customers';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return [];
    }
    
    $results = $wpdb->get_results(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC",
        ARRAY_A
    );
    
    if (empty($results)) {
        return [];
    }
    
    return $results;
}

/**
 * Get a single patient by ID from Bookly
 * 
 * @param int $patient_id
 * @return array|null Patient data or null if not found
 */
function mr_get_bookly_patient_by_id($patient_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_customers';
    
    // Check if table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return null;
    }
    
    $result = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $patient_id),
        ARRAY_A
    );
    
    return $result ?: null;
}

/**
 * Get WordPress user linked to a Bookly staff member
 * 
 * @param int $staff_id
 * @return WP_User|null WordPress user or null
 */
function mr_get_wp_user_from_staff($staff_id) {
    $staff = mr_get_bookly_doctor_by_id($staff_id);
    
    if (!$staff || empty($staff['wp_user_id'])) {
        return null;
    }
    
    return get_user_by('ID', $staff['wp_user_id']);
}

/**
 * Get WordPress user linked to a Bookly customer
 * 
 * @param int $customer_id
 * @return WP_User|null WordPress user or null
 */
function mr_get_wp_user_from_customer($customer_id) {
    $customer = mr_get_bookly_patient_by_id($customer_id);
    
    if (!$customer || empty($customer['wp_user_id'])) {
        return null;
    }
    
    return get_user_by('ID', $customer['wp_user_id']);
}

/**
 * Format doctor name for display
 * 
 * @param array $doctor
 * @return string Formatted name
 */
function mr_format_doctor_name($doctor) {
    if (empty($doctor)) {
        return __('Unknown Doctor', 'medical-records');
    }
    
    return !empty($doctor['full_name']) ? $doctor['full_name'] : 
           (!empty($doctor['name']) ? $doctor['name'] : __('Doctor', 'medical-records'));
}

/**
 * Format patient name for display
 * 
 * @param array $patient
 * @return string Formatted name
 */
function mr_format_patient_name($patient) {
    if (empty($patient)) {
        return __('Unknown Patient', 'medical-records');
    }
    
    if (!empty($patient['full_name'])) {
        return $patient['full_name'];
    }
    
    $first = $patient['first_name'] ?? '';
    $last = $patient['last_name'] ?? '';
    
    if ($first && $last) {
        return trim($first . ' ' . $last);
    }
    
    return $first ?: $last ?: __('Patient', 'medical-records');
}

/**
 * Get doctor's color for UI styling
 * 
 * @param array $doctor
 * @return string Color code
 */
function mr_get_doctor_color($doctor) {
    return !empty($doctor['color']) ? $doctor['color'] : '#396cf0';
}

/**
 * Check if Bookly tables exist
 * 
 * @return bool
 */
function mr_bookly_tables_exist() {
    global $wpdb;
    
    $staff_table = $wpdb->prefix . 'bookly_staff';
    $customers_table = $wpdb->prefix . 'bookly_customers';
    
    $staff_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $staff_table));
    $customers_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $customers_table));
    
    return !empty($staff_exists) && !empty($customers_exists);
}

/**
 * Get doctor select options HTML
 * 
 * @param mixed $selected Selected doctor ID
 * @param bool $include_empty Whether to include empty option
 * @return string HTML options
 */
function mr_get_doctor_select_options($selected = '', $include_empty = true) {
    $doctors = mr_get_bookly_doctors();
    $output = '';
    
    if ($include_empty) {
        $output .= '<option value="">' . __('Select Doctor...', 'medical-records') . '</option>';
    }
    
    if (empty($doctors)) {
        return $output;
    }
    
    foreach ($doctors as $doctor) {
        $selected_attr = selected($selected, $doctor['id'], false);
        $color = mr_get_doctor_color($doctor);
        $name = mr_format_doctor_name($doctor);
        $output .= '<option value="' . esc_attr($doctor['id']) . '" ' . $selected_attr . ' data-color="' . esc_attr($color) . '">' . esc_html($name) . '</option>';
    }
    
    return $output;
}

/**
 * Get patient by WordPress user ID
 * 
 * @param int $wp_user_id
 * @return array|null Patient data or null
 */
function mr_get_patient_by_wp_user_id($wp_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_customers';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return null;
    }
    
    $result = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE wp_user_id = %d", $wp_user_id),
        ARRAY_A
    );
    
    return $result ?: null;
}

/**
 * Get doctor by WordPress user ID
 * 
 * @param int $wp_user_id
 * @return array|null Doctor data or null
 */
function mr_get_doctor_by_wp_user_id($wp_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'bookly_staff';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        return null;
    }
    
    $result = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE wp_user_id = %d", $wp_user_id),
        ARRAY_A
    );
    
    return $result ?: null;
}
