<div class="wmr-view-record">
    <div class="wmr-back-button">
        <button type="button" id="wmr-back-to-list" class="button">
            ← <?php echo esc_html__('Back to List', 'wp-medical-records'); ?>
        </button>
    </div>
    
    <!-- Patient Info Card -->
    <div class="wmr-patient-info-card">
        <h3><?php echo esc_html__('Patient Information', 'wp-medical-records'); ?></h3>
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
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Record Created:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($record->created_at); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Medical History Section (if exists) -->
    <div id="wmr-medical-history-section" class="wmr-section">
        <h3><?php echo esc_html__('Medical History', 'wp-medical-records'); ?></h3>
        <div id="wmr-medical-history-content"></div>
    </div>
    
    <!-- Visits List -->
    <div class="wmr-visits-section">
        <div class="wmr-section-header">
            <h3><?php echo esc_html__('Visits History', 'wp-medical-records'); ?></h3>
            <?php if (wmr_is_doctor()): ?>
                <button type="button" id="wmr-add-visit-btn" class="button button-primary">
                    <?php echo esc_html__('Register Visit', 'wp-medical-records'); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Filters -->
        <div class="wmr-filters">
            <div class="wmr-filter-group">
                <label><?php echo esc_html__('Clinic:', 'wp-medical-records'); ?></label>
                <select id="wmr-filter-clinic" class="wmr-filter-select">
                    <option value="0"><?php echo esc_html__('All Clinics', 'wp-medical-records'); ?></option>
                </select>
            </div>
            <div class="wmr-filter-group">
                <label><?php echo esc_html__('Doctor:', 'wp-medical-records'); ?></label>
                <select id="wmr-filter-doctor" class="wmr-filter-select">
                    <option value="0"><?php echo esc_html__('All Doctors', 'wp-medical-records'); ?></option>
                </select>
            </div>
            <div class="wmr-filter-group">
                <label><?php echo esc_html__('From Date:', 'wp-medical-records'); ?></label>
                <input type="text" id="wmr-filter-date-from" class="wmr-date-picker" placeholder="<?php echo esc_attr__('Select date', 'wp-medical-records'); ?>">
            </div>
            <div class="wmr-filter-group">
                <label><?php echo esc_html__('To Date:', 'wp-medical-records'); ?></label>
                <input type="text" id="wmr-filter-date-to" class="wmr-date-picker" placeholder="<?php echo esc_attr__('Select date', 'wp-medical-records'); ?>">
            </div>
            <div class="wmr-filter-group">
                <button type="button" id="wmr-apply-filters" class="button">
                    <?php echo esc_html__('Filter', 'wp-medical-records'); ?>
                </button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped" id="wmr-visits-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Doctor', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Clinic', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-medical-records'); ?></th>
                </tr>
            </thead>
            <tbody id="wmr-visits-tbody">
                <tr>
                    <td colspan="4" class="text-center"><?php echo esc_html__('Loading...', 'wp-medical-records'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Hidden fields for record data -->
    <input type="hidden" id="wmr-record-id" value="<?php echo esc_attr($record->id); ?>">
    <input type="hidden" id="wmr-patient-id" value="<?php echo esc_attr($record->patient_id); ?>">
</div>

<!-- Visit Modal -->
<div id="wmr-visit-modal" class="wmr-modal" style="display: none;">
    <div class="wmr-modal-content wmr-modal-large">
        <span class="wmr-close">&times;</span>
        <div id="wmr-visit-form-container"></div>
    </div>
</div>

<!-- View Visit Modal -->
<div id="wmr-view-visit-modal" class="wmr-modal" style="display: none;">
    <div class="wmr-modal-content wmr-modal-large">
        <span class="wmr-close">&times;</span>
        <div id="wmr-view-visit-container"></div>
    </div>
</div>
