<?php
/**
 * Patient role functions for Medical Records plugin
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if current user is a patient (Bookly customer)
 * 
 * @return int|false Patient ID if user is patient, false otherwise
 */
function mr_is_patient() {
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return false;
    }
    
    $patient = mr_get_patient_by_wp_user_id($current_user_id);
    return $patient ? $patient['id'] : false;
}

/**
 * Patient dashboard page - View own medical records
 */
function mr_patient_dashboard() {
    $patient_id = mr_is_patient();
    
    if (!$patient_id) {
        echo '<div class="wrap"><h1 class="mr-error">' . __('No medical record found for your account.', 'medilink') . '</h1></div>';
        return;
    }
    
    $patient = mr_get_bookly_patients();
    $current_user_id = get_current_user_id();
    $user_patient = null;
    
    foreach ($patient as $p) {
        if ($p['wp_user_id'] == $current_user_id) {
            $user_patient = $p;
            break;
        }
    }
    
    if (!$user_patient) {
        echo '<div class="wrap"><h1 class="mr-error">' . __('No medical record found for your account.', 'medilink') . '</h1></div>';
        return;
    }
    
    echo '<div class="wrap mr-container">';
    echo '<h1 class="mr-card-title">' . sprintf(__('Medical Records - %s', 'medilink'), esc_html($user_patient['full_name'])) . '</h1>';
    
    // Display medical summary
    echo mr_get_medical_info($current_user_id);
    
    // Display visits
    $visits = get_user_meta($current_user_id, 'medical_visits', true);
    $visits = is_array($visits) ? $visits : [];
    
    echo '<h2 class="mr-section-title">' . __('My Visits', 'medilink') . '</h2>';
    
    if (empty($visits)) {
        echo '<div class="mr-empty-state mr-card">' . __('No visits registered.', 'medilink') . '</div>';
    } else {
        foreach (array_reverse($visits) as $visit) {
            $doctor = get_user_by('ID', $visit['doctor_id'] ?? 0);
            echo '<div class="mr-visit-card mr-card">';
            
            echo '<div class="mr-visit-header">';
            echo '<div class="mr-visit-title">';
            echo '<span class="mr-badge">' . esc_html($visit['visit_date'] ?? '—') . '</span>';
            echo '</div>';
            echo '<div class="mr-doctor-name">' . ($doctor ? esc_html($doctor->display_name) : '—') . '</div>';
            echo '</div>';
            
            echo '<div class="mr-visit-grid">';
            
            // Complaint
            echo '<div class="mr-visit-item">';
            echo '<h5 class="mr-visit-label"><span>📄</span> ' . __('Chief Complaint', 'medilink') . '</h5>';
            echo '<p class="mr-visit-value">' . (empty($visit['complaint']) ? '<span class="mr-empty">—</span>' : esc_html($visit['complaint'])) . '</p>';
            echo '</div>';
            
            // Diagnosis
            echo '<div class="mr-visit-item">';
            echo '<h5 class="mr-visit-label"><span>⚕️</span> ' . __('Diagnosis', 'medilink') . '</h5>';
            echo '<p class="mr-visit-value">' . (empty($visit['diagnosis']) ? '<span class="mr-empty">—</span>' : esc_html($visit['diagnosis'])) . '</p>';
            echo '</div>';
            
            // Medications
            if (!empty($visit['medications']) && is_array($visit['medications'])) {
                echo '<div class="mr-visit-item">';
                echo '<h5 class="mr-visit-label"><span>💊</span> ' . __('Medications', 'medilink') . '</h5>';
                echo '<ul class="mr-list">';
                foreach ($visit['medications'] as $med) {
                    if (!empty($med)) echo '<li>' . esc_html($med) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            // Files
            if (!empty($visit['files']) && is_array($visit['files'])) {
                echo '<div class="mr-visit-item">';
                echo '<h5 class="mr-visit-label"><span>📎</span> ' . __('Files', 'medilink') . '</h5>';
                echo '<ul class="mr-list">';
                foreach ($visit['files'] as $file) {
                    if (!empty($file['title']) && !empty($file['url'])) {
                        echo '<li><a href="' . esc_url($file['url']) . '" target="_blank" class="mr-link">' . esc_html($file['title']) . '</a></li>';
                    }
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
}
