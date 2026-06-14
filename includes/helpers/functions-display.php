<?php
/**
 * Display helper functions for Medical Records plugin
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get medical info summary card for a user
 * 
 * @param int $user_id User ID
 * @return string HTML output
 */
function mr_get_medical_info($user_id) {
    $record = get_user_meta($user_id, 'medical_record_data', true);
    $visits = get_user_meta($user_id, 'medical_visits', true);
    $visits = is_array($visits) ? $visits : [];
    
    // Calculate statistics
    $total_visits = count($visits);
    $last_visit_date = '—';
    $blood_group = isset($record['blood_group']) && !empty($record['blood_group']) ? $record['blood_group'] : '—';
    $allergies = isset($record['allergies']) && !empty($record['allergies']) ? $record['allergies'] : '—';
    
    if (!empty($visits)) {
        usort($visits, function($a, $b) {
            return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? '');
        });
        $last_visit_date = $visits[0]['visit_date'] ?? '—';
    }
    
    ob_start();
    ?>
    <div class="mr-medical-summary mr-card">
        <h3 class="mr-summary-title"><?php echo __('Medical Summary', 'medilink'); ?></h3>
        <div class="mr-summary-grid">
            <div class="mr-summary-item">
                <span class="mr-summary-icon">📋</span>
                <div class="mr-summary-content">
                    <span class="mr-summary-label"><?php echo __('Blood Group', 'medilink'); ?></span>
                    <span class="mr-summary-value"><?php echo esc_html($blood_group); ?></span>
                </div>
            </div>
            <div class="mr-summary-item">
                <span class="mr-summary-icon">⚠️</span>
                <div class="mr-summary-content">
                    <span class="mr-summary-label"><?php echo __('Allergies', 'medilink'); ?></span>
                    <span class="mr-summary-value"><?php echo esc_html($allergies); ?></span>
                </div>
            </div>
            <div class="mr-summary-item">
                <span class="mr-summary-icon">📅</span>
                <div class="mr-summary-content">
                    <span class="mr-summary-label"><?php echo __('Last Visit', 'medilink'); ?></span>
                    <span class="mr-summary-value"><?php echo esc_html($last_visit_date); ?></span>
                </div>
            </div>
            <div class="mr-summary-item">
                <span class="mr-summary-icon">🏥</span>
                <div class="mr-summary-content">
                    <span class="mr-summary-label"><?php echo __('Total Visits', 'medilink'); ?></span>
                    <span class="mr-summary-value"><?php echo esc_html($total_visits); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
