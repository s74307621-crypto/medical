<div class="wmr-create-record-form">
    <h3><?php echo esc_html__('Create Medical Record', 'wp-medical-records'); ?></h3>
    
    <div class="wmr-form-group">
        <label for="wmr-patient-select"><?php echo esc_html__('Select Patient', 'wp-medical-records'); ?></label>
        <select id="wmr-patient-select" class="wmr-select2" style="width: 100%;">
            <option value=""><?php echo esc_html__('Search and select patient...', 'wp-medical-records'); ?></option>
        </select>
    </div>
    
    <div class="wmr-form-actions">
        <button type="button" id="wmr-create-record-btn" class="button button-primary">
            <?php echo esc_html__('Create Record', 'wp-medical-records'); ?>
        </button>
        <button type="button" id="wmr-cancel-create-btn" class="button">
            <?php echo esc_html__('Cancel', 'wp-medical-records'); ?>
        </button>
    </div>
    
    <div id="wmr-patient-info-preview" style="display: none; margin-top: 20px;">
        <hr style="margin: 20px 0;">
        <h4><?php echo esc_html__('Patient Information', 'wp-medical-records'); ?></h4>
        <div class="wmr-info-row">
            <strong><?php echo esc_html__('Name:', 'wp-medical-records'); ?></strong>
            <span id="wmr-preview-name"></span>
        </div>
        <div class="wmr-info-row">
            <strong><?php echo esc_html__('Phone:', 'wp-medical-records'); ?></strong>
            <span id="wmr-preview-phone"></span>
        </div>
        
        <div class="wmr-form-actions" style="margin-top: 20px;">
            <button type="button" id="wmr-confirm-create-btn" class="button button-primary">
                <?php echo esc_html__('Confirm & Create', 'wp-medical-records'); ?>
            </button>
            <button type="button" id="wmr-back-select-btn" class="button">
                <?php echo esc_html__('Back', 'wp-medical-records'); ?>
            </button>
        </div>
    </div>
</div>
