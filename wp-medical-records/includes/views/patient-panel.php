<div class="wmr-patient-panel">
    <h2><?php echo esc_html__('My Medical Records', 'wp-medical-records'); ?></h2>
    
    <?php
    global $wpdb;
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        echo '<div class="wmr-error-message">' . esc_html__('Please log in to view your medical records.', 'wp-medical-records') . '</div>';
        return;
    }
    
    // Find patient ID
    $patient = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bookly_customers WHERE wp_user_id = %d",
        $user_id
    ));
    
    if (!$patient) {
        echo '<div class="wmr-error-message">' . esc_html__('Patient record not found. Please contact support.', 'wp-medical-records') . '</div>';
        return;
    }
    
    // Find medical record
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wmr_medical_records WHERE patient_id = %d AND status = 'active'",
        $patient->id
    ));
    
    if (!$record) {
        echo '<div class="wmr-info-message">' . esc_html__('No medical record found. Please visit the clinic to create your medical record.', 'wp-medical-records') . '</div>';
        return;
    }
    ?>
    
    <!-- Patient Info Card -->
    <div class="wmr-patient-info-card">
        <h3><?php echo esc_html__('Your Information', 'wp-medical-records'); ?></h3>
        <div class="wmr-info-grid">
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Name:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($patient->full_name); ?></span>
            </div>
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Phone:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($patient->phone); ?></span>
            </div>
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Email:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($patient->email); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Medical History Section -->
    <div class="wmr-medical-history-section">
        <h3><?php echo esc_html__('Medical History', 'wp-medical-records'); ?></h3>
        <div id="wmr-patient-medical-history">
            <button type="button" id="wmr-patient-save-history-btn" class="button button-primary">
                <?php echo esc_html__('Register Medical History', 'wp-medical-records'); ?>
            </button>
        </div>
    </div>
    
    <!-- Visits List -->
    <div class="wmr-visits-section">
        <h3><?php echo esc_html__('Visit History', 'wp-medical-records'); ?></h3>
        
        <table class="wmr-table" id="wmr-patient-visits-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Doctor', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Clinic', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-medical-records'); ?></th>
                </tr>
            </thead>
            <tbody id="wmr-patient-visits-tbody">
                <tr>
                    <td colspan="4" class="text-center"><?php echo esc_html__('Loading...', 'wp-medical-records'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Hidden record ID -->
    <input type="hidden" id="wmr-patient-record-id" value="<?php echo esc_attr($record->id); ?>">
    
    <!-- View Visit Modal -->
    <div id="wmr-patient-view-visit-modal" class="wmr-modal" style="display: none;">
        <div class="wmr-modal-content wmr-modal-large">
            <span class="wmr-close">&times;</span>
            <div id="wmr-patient-view-visit-content"></div>
        </div>
    </div>
    
    <!-- Medical History Form Modal -->
    <div id="wmr-patient-history-modal" class="wmr-modal" style="display: none;">
        <div class="wmr-modal-content wmr-modal-large">
            <span class="wmr-close">&times;</span>
            <div id="wmr-patient-history-form"></div>
        </div>
    </div>
</div>
