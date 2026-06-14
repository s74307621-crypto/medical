<div class="wmr-view-visit">
    <h3><?php echo esc_html__('Visit Details', 'wp-medical-records'); ?></h3>
    
    <div class="wmr-visit-info-card">
        <div class="wmr-info-grid">
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Date:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($visit->visit_date); ?></span>
            </div>
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Doctor:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($doctor->full_name); ?></span>
            </div>
            <div class="wmr-info-item">
                <strong><?php echo esc_html__('Clinic:', 'wp-medical-records'); ?></strong>
                <span><?php echo esc_html($clinic->name); ?></span>
            </div>
        </div>
    </div>
    
    <div class="wmr-visit-details">
        <h4><?php echo esc_html__('Complaint', 'wp-medical-records'); ?></h4>
        <p><?php echo nl2br(esc_html($visit->complaint)); ?></p>
        
        <h4><?php echo esc_html__('Diagnosis', 'wp-medical-records'); ?></h4>
        <p><?php echo nl2br(esc_html($visit->diagnosis)); ?></p>
    </div>
    
    <?php if (!empty($medicines)): ?>
        <div class="wmr-medicines-section">
            <h4><?php echo esc_html__('Prescribed Medications', 'wp-medical-records'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Medicine Name', 'wp-medical-records'); ?></th>
                        <th><?php echo esc_html__('Dosage', 'wp-medical-records'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td><?php echo esc_html($medicine->medicine_name); ?></td>
                            <td><?php echo esc_html($medicine->dosage); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($files)): ?>
        <div class="wmr-files-section">
            <h4><?php echo esc_html__('Attached Files', 'wp-medical-records'); ?></h4>
            <div class="wmr-files-list">
                <?php foreach ($files as $file): ?>
                    <div class="wmr-file-item">
                        <a href="#" class="wmr-file-link" data-file-url="<?php echo esc_url($file->file_path); ?>" data-file-name="<?php echo esc_attr($file->file_name); ?>">
                            <span class="dashicons dashicons-media-document"></span>
                            <?php echo esc_html($file->file_name); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Lightbox for files -->
<div id="wmr-file-lightbox" class="wmr-lightbox" style="display: none;">
    <div class="wmr-lightbox-content">
        <span class="wmr-lightbox-close">&times;</span>
        <div id="wmr-lightbox-body"></div>
    </div>
</div>
