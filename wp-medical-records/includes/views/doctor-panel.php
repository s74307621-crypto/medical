<div class="wmr-doctor-panel">
    <h2><?php echo esc_html__('Doctor Panel - Medical Records', 'wp-medical-records'); ?></h2>
    
    <div class="wmr-patients-list-container">
        <h3><?php echo esc_html__('My Patients', 'wp-medical-records'); ?></h3>
        
        <table class="wmr-table" id="wmr-doctor-patients-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Full Name', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Phone', 'wp-medical-records'); ?></th>
                    <th><?php echo esc_html__('Actions', 'wp-medical-records'); ?></th>
                </tr>
            </thead>
            <tbody id="wmr-doctor-patients-tbody">
                <tr>
                    <td colspan="4" class="text-center"><?php echo esc_html__('Loading...', 'wp-medical-records'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- View Record Container -->
    <div id="wmr-doctor-view-record" style="display: none;"></div>
    
    <!-- Modals -->
    <div id="wmr-doctor-visit-modal" class="wmr-modal" style="display: none;">
        <div class="wmr-modal-content wmr-modal-large">
            <span class="wmr-close">&times;</span>
            <div id="wmr-doctor-visit-form"></div>
        </div>
    </div>
    
    <div id="wmr-doctor-view-visit-modal" class="wmr-modal" style="display: none;">
        <div class="wmr-modal-content wmr-modal-large">
            <span class="wmr-close">&times;</span>
            <div id="wmr-doctor-view-visit-content"></div>
        </div>
    </div>
</div>
