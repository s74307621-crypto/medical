<?php
/**
 * Doctor role functions for Medical Records plugin
 * Uses Bookly staff table for doctor identification
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if current user is a doctor (Bookly staff)
 * 
 * @return int|false Doctor ID if user is doctor, false otherwise
 */
function mr_is_doctor() {
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return false;
    }
    
    $doctor = mr_get_bookly_doctor_by_wp_user_id($current_user_id);
    return $doctor ? $doctor['id'] : false;
}

/**
 * Doctor dashboard page
 */
function mr_doctor_dashboard() {
    $doctor_id = mr_is_doctor();
    
    if (!$doctor_id) {
        echo '<div class="wrap"><h1 class="mr-error">' . __('Access denied. You are not registered as a doctor.', 'medilink') . '</h1></div>';
        return;
    }
    
    $doctor = mr_get_bookly_doctor_by_id($doctor_id);
    $doctor_name = mr_format_doctor_name($doctor);
    
    echo '<div class="wrap mr-container">';
    echo '<h1 class="mr-card-title">' . sprintf(__('Welcome, Dr. %s', 'medilink'), esc_html($doctor_name)) . '</h1>';
    
    // Get all patients with visits by this doctor
    global $wpdb;
    $patients_table = $wpdb->prefix . 'bookly_customers';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $patients_table)) !== $patients_table) {
        echo '<div class="mr-error-message mr-card">' . __('Bookly customers table not found.', 'medilink') . '</div>';
        echo '</div>';
        return;
    }
    
    // Get all patients
    $all_patients = $wpdb->get_results("SELECT * FROM {$patients_table} ORDER BY id DESC", ARRAY_A);
    $my_patients = [];
    
    // Filter patients who have visits with this doctor
    foreach ($all_patients as $patient) {
        $wp_user_id = $patient['wp_user_id'] ?? 0;
        if ($wp_user_id) {
            $visits = get_user_meta($wp_user_id, 'medical_visits', true);
            if (is_array($visits)) {
                foreach ($visits as $visit) {
                    // Check if visit's doctor_id matches our Bookly doctor's wp_user_id
                    if (isset($visit['doctor_id']) && $visit['doctor_id'] == $doctor['wp_user_id']) {
                        $my_patients[] = $patient;
                        break;
                    }
                }
            }
        }
    }
    
    // Display patient list
    echo '<div class="mr-table-wrapper mr-card">';
    echo '<h2 class="mr-section-title">' . __('My Patients', 'medilink') . '</h2>';
    
    if (empty($my_patients)) {
        echo '<p class="mr-empty-state">' . __('No patients found.', 'medilink') . '</p>';
    } else {
        echo '<table class="mr-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Name', 'medilink') . '</th>';
        echo '<th>' . __('Phone', 'medilink') . '</th>';
        echo '<th>' . __('Email', 'medilink') . '</th>';
        echo '<th>' . __('Actions', 'medilink') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($my_patients as $patient) {
            $wp_user_id = $patient['wp_user_id'] ?? 0;
            $view_link = admin_url('admin.php?page=medical-records-view&user_id=' . $wp_user_id);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($patient['full_name']) . '</strong></td>';
            echo '<td>' . esc_html($patient['phone']) . '</td>';
            echo '<td>' . esc_html($patient['email']) . '</td>';
            echo '<td class="mr-actions">';
            echo '<a href="' . esc_url($view_link) . '" class="mr-btn mr-btn-sm mr-btn-info">' . __('View Record', 'medilink') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    echo '</div>';
    echo '</div>';
}
