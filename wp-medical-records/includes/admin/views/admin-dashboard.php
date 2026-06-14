<div class="wmr-admin-dashboard">
    <h1><?php echo esc_html__('Medical Records Management', 'wp-medical-records'); ?></h1>
    
    <div class="wmr-patients-list">
        <h2><?php echo esc_html__('Patients List', 'wp-medical-records'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped" id="wmr-patients-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Full Name', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Phone', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-medical-records'); ?></th>
                </tr>
            </thead>
            <tbody id="wmr-patients-tbody">
                <tr>
                    <td colspan="4" class="text-center"><?php echo esc_html__('Loading...', 'wp-medical-records'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Create Record Modal -->
    <div id="wmr-create-record-modal" class="wmr-modal" style="display: none;">
        <div class="wmr-modal-content">
            <span class="wmr-close">&times;</span>
            <div id="wmr-create-record-form-container"></div>
        </div>
    </div>
    
    <!-- View Record Container -->
    <div id="wmr-view-record-container" style="display: none;"></div>
</div>
