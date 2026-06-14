<?php
/**
 * Display helper functions for Medical Records plugin
 * 
 * @package Medical_Records
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get medical info summary HTML with modern UI
 * 
 * @param int $user_id
 * @return string HTML output
 */
function mr_get_medical_info($user_id) {
    $record = get_user_meta($user_id, 'medical_record_data', true);
    $visits = get_user_meta($user_id, 'medical_visits', true);
    $visits = is_array($visits) ? $visits : [];
    
    // Calculate last visit date
    $last_visit_date = '—';
    if (!empty($visits)) {
        usort($visits, function($a, $b) {
            return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? '');
        });
        $last_visit_date = $visits[0]['visit_date'] ?? '—';
    }
    
    $illnesses = !empty($record['illnesses']) ? $record['illnesses'] : '—';
    $medications = !empty($record['medications']) ? $record['medications'] : '—';
    $notes = !empty($record['notes']) ? $record['notes'] : '—';
    
    $output = '<div class="mr-medical-summary mr-animate-in">';
    $output .= '<h3 style="margin-top: 0 !important; margin-bottom: 20px !important;" class="mr-card-title">' . __('Medical Summary', 'medical-records') . '</h3>';
    $output .= '<div class="mr-medical-summary-grid">';
    
    $output .= '<div class="mr-medical-item">';
    $output .= '<div class="mr-medical-label"><span>📅</span> ' . __('Last Visit', 'medical-records') . '</div>';
    $output .= '<div class="mr-medical-value">' . esc_html($last_visit_date) . '</div>';
    $output .= '</div>';
    
    $output .= '<div class="mr-medical-item">';
    $output .= '<div class="mr-medical-label"><span>🏥</span> ' . __('Chronic Illnesses', 'medical-records') . '</div>';
    $output .= '<div class="mr-medical-value">' . esc_html($illnesses) . '</div>';
    $output .= '</div>';
    
    $output .= '<div class="mr-medical-item">';
    $output .= '<div class="mr-medical-label"><span>💊</span> ' . __('Current Medications', 'medical-records') . '</div>';
    $output .= '<div class="mr-medical-value">' . esc_html($medications) . '</div>';
    $output .= '</div>';
    
    $output .= '<div class="mr-medical-item">';
    $output .= '<div class="mr-medical-label"><span>📝</span> ' . __('Doctor Notes', 'medical-records') . '</div>';
    $output .= '<div class="mr-medical-value">' . esc_html($notes) . '</div>';
    $output .= '</div>';
    
    $output .= '</div></div>';
    
    return $output;
}