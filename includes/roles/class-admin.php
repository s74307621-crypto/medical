<?php
/**
 * Admin role functions for Medical Records plugin
 * Uses Bookly tables for doctors and patients
 * 
 * @package Medical_Records
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main admin page - List of patients from Bookly
 */
function mr_admin_page() {
    global $wpdb;
    
    echo '<div class="wrap mr-container">';
    echo '<h1 class="mr-card-title" style="margin-bottom: 30px !important;">' . __('Medical Records Management', 'medical-records') . '</h1>';
    
    // Search and filters
    $search_query = isset($_GET['mr_search']) ? sanitize_text_field($_GET['mr_search']) : '';
    $order_dir = (isset($_GET['mr_dir']) && $_GET['mr_dir'] === 'desc') ? 'DESC' : 'ASC';
    
    $base_url = remove_query_arg(['mr_dir']);
    $sort_id_url = add_query_arg(['mr_dir' => ($order_dir === 'ASC' ? 'desc' : 'asc')], $base_url);
    
    // Get patients from Bookly customers table
    $patients_table = $wpdb->prefix . 'bookly_customers';
    $patients = [];
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $patients_table)) === $patients_table) {
        $sql = "SELECT * FROM {$patients_table}";
        $params = [];
        
        if (!empty($search_query)) {
            $sql .= " WHERE full_name LIKE %s OR email LIKE %s OR phone LIKE %s";
            $search_param = '%' . $wpdb->esc_like($search_query) . '%';
            $params = [$search_param, $search_param, $search_param];
        }
        
        $sql .= " ORDER BY id " . ($order_dir === 'ASC' ? 'ASC' : 'DESC');
        
        if (!empty($params)) {
            $patients = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        } else {
            $patients = $wpdb->get_results($sql, ARRAY_A);
        }
    }
    
    // Search form with modern UI
    echo '<div class="mr-card">';
    echo '<form method="get" class="mr-search-form">';
    echo '<input type="hidden" name="page" value="medical-records" />';
    echo '<input type="text" name="mr_search" value="' . esc_attr($search_query) . '" 
        placeholder="' . __('Search by name, email or phone...', 'medical-records') . '" class="mr-search-input" />';
    echo '<button type="submit" class="mr-btn mr-btn-primary">' . __('Search', 'medical-records') . '</button>';
    echo '<a href="' . admin_url('admin.php?page=medical-records') . '" class="mr-btn mr-btn-secondary">' . __('Clear', 'medical-records') . '</a>';
    echo '</form>';
    
    // Sort indicator
    $sort_indicator = ($order_dir === 'ASC') ? ' ↑' : ' ↓';
    echo '<div style="margin-top: 15px;">';
    echo '<span style="color: var(--mr-muted); font-weight: 600;">' . __('Sort by ID:', 'medical-records') . ' </span>';
    echo '<a href="' . esc_url($sort_id_url) . '" class="mr-filter-badge">' . __('Patient ID', 'medical-records') . $sort_indicator . '</a>';
    echo '</div>';
    echo '</div>';
    
    // Patients table with modern UI
    echo '<div class="mr-card">';
    echo '<div class="mr-table-wrapper">';
    echo '<table class="mr-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('ID', 'medical-records') . '</th>';
    echo '<th>' . __('Full Name', 'medical-records') . '</th>';
    echo '<th>' . __('Email', 'medical-records') . '</th>';
    echo '<th>' . __('Phone', 'medical-records') . '</th>';
    echo '<th>' . __('Actions', 'medical-records') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (empty($patients)) {
        echo '<tr>';
        echo '<td colspan="5" style="text-align: center; padding: 40px;">';
        echo '<div class="mr-empty-state">';
        echo '<div class="mr-empty-state-icon">📋</div>';
        echo '<div class="mr-empty-state-text">' . __('No patients found.', 'medical-records') . '</div>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    } else {
        foreach ($patients as $patient) {
            $wp_user_id = $patient['wp_user_id'] ?? 0;
            $has_record = false;
            
            if ($wp_user_id) {
                $record_data = get_user_meta($wp_user_id, 'medical_record_data', true);
                $has_record = !empty($record_data) && is_array($record_data);
            }
            
            echo '<tr class="mr-animate-in">';
            echo '<td><strong>#' . esc_html($patient['id']) . '</strong></td>';
            echo '<td>' . esc_html(mr_format_patient_name($patient)) . '</td>';
            echo '<td>' . (empty($patient['email']) ? '<span style="color: var(--mr-muted);">—</span>' : esc_html($patient['email'])) . '</td>';
            echo '<td>' . (empty($patient['phone']) ? '<span style="color: var(--mr-muted);">—</span>' : esc_html($patient['phone'])) . '</td>';
            echo '<td>';
            
            if ($has_record && $wp_user_id) {
                echo '<a href="' . admin_url('admin.php?page=mr-view-record&user_id=' . $wp_user_id) . '" class="mr-btn mr-btn-primary mr-btn-sm">' . __('View Record', 'medical-records') . '</a> ';
                echo '<a href="' . admin_url('admin.php?page=mr-edit-record&user_id=' . $wp_user_id) . '" class="mr-btn mr-btn-success mr-btn-sm">' . __('Edit', 'medical-records') . '</a> ';
                echo '<a href="' . admin_url('admin-post.php?action=mr_delete_record&user_id=' . $wp_user_id) . '" class="mr-btn mr-btn-danger mr-btn-sm" 
                    onclick="return confirm(\'' . __('Are you sure you want to delete this patient\'s record?', 'medical-records') . '\');">' . __('Delete', 'medical-records') . '</a>';
            } else {
                if ($wp_user_id) {
                    echo '<a href="' . admin_url('admin-post.php?action=mr_create_record&user_id=' . $wp_user_id) . '" class="mr-btn mr-btn-primary mr-btn-sm">' . __('Create Record', 'medical-records') . '</a>';
                } else {
                    echo '<span style="color: var(--mr-warning); font-weight: 600;">' . __('No WordPress User Linked', 'medical-records') . '</span>';
                }
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// ========== نمایش جزئیات (ادمین) ==========
    function mr_view_record_page() {
        if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
            echo '<div class="wrap"><h1 style="color: #396cf0 !important;">کاربر معتبر نیست.</h1></div>';
            return;
        }

        $user_id = intval($_GET['user_id']);
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            echo '<div class="wrap"><h1 style="color: #396cf0 !important;">کاربر یافت نشد.</h1></div>';
            return;
        }

        $visits = get_user_meta($user_id, 'medical_visits', true);
        $visits = is_array($visits) ? $visits : [];

        // محاسبه آخرین ویزیت
        $last_visit_date = '—';
        if (!empty($visits)) {
            usort($visits, function($a, $b) {
                return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? '');
            });
            $last_visit_date = $visits[0]['visit_date'] ?? '—';
        }

        echo '<div class="wrap" style="margin: 20px !important;">';
        echo '<h1 style="color: #396cf0 !important; margin-bottom: 25px !important;">جزئیات پرونده پزشکی</h1>';
        // جایگزینی بخش خلاصه پرونده
    echo mr_get_medical_info($user_id);

        echo '<h2 style="color: #396cf0 !important; margin: 25px 0 15px !important;">ویزیت‌ها</h2>';
        if (empty($visits)) {
            echo '<p style="background: #f9f9f9 !important; padding: 15px !important; border-radius: 8px !important; color: #777 !important;">هیچ ویزیتی ثبت نشده است.</p>';
        } else {
            foreach (array_reverse($visits) as $visit) {
                $doctor = get_user_by('ID', $visit['doctor_id'] ?? 0);
                echo '<div style="background: white !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; padding: 24px !important; margin: 20px 0 !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
                
                // هدر ویزیت
                echo '<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 20px !important; padding-bottom: 12px !important; border-bottom: 1px solid #edf2f7 !important;">';
                echo '<div style="display: flex !important; align-items: center !important; gap: 10px !important;">';
                echo '<span style="background: #396cf0 !important; color: white !important; width: 32px !important; height: 32px !important; border-radius: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 14px !important;">و</span>';
                echo '<h4 style="margin: 0 !important; color: #1a202c !important; font-size: 18px !important; font-weight: 700 !important;">' . esc_html($visit['visit_date'] ?? '—') . '</h4>';
                echo '</div>';
                echo '<div style="background: #ebf8ff !important; color: #2b6cb0 !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 14px !important; font-weight: 600 !important;">' . ($doctor ? esc_html($doctor->display_name) : '—') . '</div>';
                echo '</div>';

                // محتوای ویزیت (به صورت گرید)
                echo '<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important; gap: 20px !important;">';

                // شرح حال
                echo '<div style="flex: 1 !important;">';
                echo '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📄</span> شرح حال بیمار</h5>';
                echo '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important;">' . (empty($visit['complaint']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['complaint'])) . '</p>';
                echo '</div>';

                // تشخیص
                echo '<div style="flex: 1 !important;">';
                echo '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>⚕️</span> تشخیص پزشک</h5>';
                echo '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important;">' . (empty($visit['diagnosis']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['diagnosis'])) . '</p>';
                echo '</div>';

                // داروها
                if (!empty($visit['medications']) && is_array($visit['medications'])) {
                    echo '<div style="flex: 1 !important;">';
                    echo '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>💊</span> داروها</h5>';
                    echo '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important;">';
                    foreach ($visit['medications'] as $med) {
                        if (!empty($med)) echo '<li>' . esc_html($med) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }

                // فایل‌ها
                if (!empty($visit['files']) && is_array($visit['files'])) {
                    echo '<div style="flex: 1 !important;">';
                    echo '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📎</span> فایل‌ها</h5>';
                    echo '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important;">';
                    foreach ($visit['files'] as $file) {
                        if (!empty($file['title']) && !empty($file['url'])) {
                            echo '<li><a href="' . esc_url($file['url']) . '" target="_blank" style="color: #396cf0 !important; text-decoration: underline !important;">' . esc_html($file['title']) . '</a></li>';
                        }
                    }
                    echo '</ul>';
                    echo '</div>';
                }
				// نمایش امتیاز در پنل پزشک
				// نمایش امتیاز در پنل ادمین
if (!empty($visit['rating'])) {
    $rating_stars = str_repeat('★', $visit['rating']) . str_repeat('☆', 5 - $visit['rating']);
    echo '<div style="flex: 1 !important; min-width: 0 !important;">';
    echo '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>⭐</span> امتیاز بیمار</h5>';
    echo '<p style="margin: 0 !important; color: #396cf0 !important; font-weight: bold;">' . $rating_stars . '</p>';
    echo '</div>';
}

                echo '</div>'; // پایان گرید
                echo '</div>'; // پایان کارت
            }
        }

        echo '<p style="margin-top: 25px !important;">';
        echo '<a href="' . admin_url('admin.php?page=mr-add-visit&user_id=' . $user_id) . '" class="button" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 8px 20px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important;">افزودن ویزیت</a> ';
        echo '<a href="' . admin_url('admin.php?page=medical-records') . '" class="button" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 8px 20px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important;">بازگشت به لیست</a>';
        echo '</p>';
        echo '</div>';
    }


    // ========== ویرایش پرونده (ادمین) ==========
    function mr_edit_record_page() {
        if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
            wp_die('کاربر نامعتبر است.');
        }

        $user_id = intval($_GET['user_id']);
        $user = get_user_by('ID', $user_id);
        if (!$user) wp_die('کاربر یافت نشد.');

        $record = get_user_meta($user_id, 'medical_record_data', true);
        if (!is_array($record)) {
            $record = [
                'illnesses'    => '',
                'medications'  => '',
                'last_visit'   => '',
                'notes'        => ''
            ];
        }

        if (isset($_POST['mr_save_record']) && wp_verify_nonce($_POST['mr_nonce'], 'mr_save_record_action')) {
            $record = [
                'illnesses'    => sanitize_textarea_field($_POST['illnesses'] ?? ''),
                'medications'  => sanitize_textarea_field($_POST['medications'] ?? ''),
                'last_visit'   => sanitize_text_field($_POST['last_visit'] ?? ''),
                'notes'        => sanitize_textarea_field($_POST['notes'] ?? '')
            ];
            update_user_meta($user_id, 'medical_record_data', $record);
            wp_redirect(admin_url('admin.php?page=mr-view-record&user_id=' . $user_id));
            exit;
        }

        echo '<div class="wrap" style="margin: 20px !important;">';
        echo '<h1 style="color: #396cf0 !important; margin-bottom: 25px !important;">ویرایش پرونده پزشکی — ' . esc_html($user->display_name) . '</h1>';
        echo '<form method="post" style="background: white !important; padding: 25px !important; border-radius: 12px !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;">';
        wp_nonce_field('mr_save_record_action', 'mr_nonce');
        echo '<table class="form-table" style="width: 100% !important;">';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">سابقه بیماری‌ها</th><td style="padding: 10px 0 !important;"><textarea name="illnesses" rows="3" class="large-text" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">' . esc_textarea($record['illnesses']) . '</textarea></td></tr>';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">داروهای مصرفی</th><td style="padding: 10px 0 !important;"><textarea name="medications" rows="3" class="large-text" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">' . esc_textarea($record['medications']) . '</textarea></td></tr>';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">تاریخ آخرین ویزیت</th><td style="padding: 10px 0 !important;"><input type="text" name="last_visit" value="' . esc_attr($record['last_visit']) . '" class="regular-text" placeholder="مثال: 1404/10/05" style="width: 100% !important; max-width: 300px !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" /></td></tr>';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">یادداشت پزشک</th><td style="padding: 10px 0 !important;"><textarea name="notes" rows="4" class="large-text" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">' . esc_textarea($record['notes']) . '</textarea></td></tr>';
        echo '</table>';
        echo '<p style="margin-top: 25px !important;">';
        echo '<input type="submit" name="mr_save_record" class="button button-primary" value="ذخیره پرونده" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 24px !important; border-radius: 6px !important; font-weight: bold !important;" />';
        echo ' <a href="' . admin_url('admin.php?page=mr-view-record&user_id=' . $user_id) . '" class="button" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 24px !important; border-radius: 6px !important;">لغو</a>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }


    // ========== افزودن ویزیت (ادمین) ==========
    function mr_add_visit_page_callback() {
        if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
            wp_die('بیمار معتبر نیست.');
        }

        // بارگذاری مدیا
        wp_enqueue_media();

        $patient_id = intval($_GET['user_id']);
        $patient = get_user_by('ID', $patient_id);
        if (!$patient) wp_die('بیمار یافت نشد.');

        // انتخاب پزشک از جدول Bookly
        $doctors = mr_get_bookly_doctors();

        if (isset($_POST['mr_save_visit']) && wp_verify_nonce($_POST['mr_visit_nonce'], 'mr_save_visit_action')) {
            $doctor_id = intval($_POST['doctor_id'] ?? 0);
            $visit_date = sanitize_text_field($_POST['visit_date'] ?? '');
            $complaint = sanitize_textarea_field($_POST['complaint'] ?? '');
            $diagnosis = sanitize_textarea_field($_POST['diagnosis'] ?? '');
            $medications = array_filter(array_map('trim', (array) $_POST['medications'] ?? []), 'strlen');
            
            $files = [];
            $file_titles = $_POST['file_titles'] ?? [];
            $file_urls = $_POST['file_urls'] ?? [];
            foreach ($file_titles as $index => $title) {
                if (!empty($title) && !empty($file_urls[$index])) {
                    $files[] = [
                        'title' => sanitize_text_field($title),
                        'url'   => esc_url_raw($file_urls[$index])
                    ];
                }
            }

            if ($doctor_id > 0 && !empty($visit_date)) {
                $new_visit = [
					'id' => uniqid('visit_', true),
                    'doctor_id'   => $doctor_id,
                    'visit_date'  => $visit_date,
                    'complaint'   => $complaint,
                    'diagnosis'   => $diagnosis,
                    'medications' => $medications,
                    'files'       => $files
                ];

                $visits = get_user_meta($patient_id, 'medical_visits', true);
                if (!is_array($visits)) $visits = [];
                $visits[] = $new_visit;
                update_user_meta($patient_id, 'medical_visits', $visits);

                wp_redirect(admin_url('admin.php?page=mr-view-record&user_id=' . $patient_id));
                exit;
            } else {
                echo '<div class="notice notice-error" style="background: #fee !important; border-left: 4px solid #e74c3c !important; padding: 12px !important; margin: 20px 0 !important; border-radius: 4px !important;"><p>لطفاً پزشک و تاریخ ویزیت را وارد کنید.</p></div>';
            }
        }

        echo '<div class="wrap" style="margin: 20px !important;">';
        echo '<h1 style="color: #396cf0 !important; margin-bottom: 25px !important;">افزودن ویزیت برای: ' . esc_html($patient->display_name) . '</h1>';
        echo '<form method="post" id="visit-form" style="background: white !important; padding: 25px !important; border-radius: 12px !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;">';
        wp_nonce_field('mr_save_visit_action', 'mr_visit_nonce');
        echo '<table class="form-table" style="width: 100% !important;">';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">پزشک <span style="color:red !important;">*</span></th><td style="padding: 10px 0 !important;"><select name="doctor_id" class="regular-text" required style="width: 100% !important; max-width: 300px !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">';
        echo '<option value="">انتخاب کنید...</option>';
        foreach ($doctors as $doc) {
            $color = mr_get_doctor_color($doc);
            $name = mr_format_doctor_name($doc);
            echo '<option value="' . esc_attr($doc['id']) . '" data-color="' . esc_attr($color) . '">' . esc_html($name) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">تاریخ ویزیت <span style="color:red !important;">*</span></th><td style="padding: 10px 0 !important;"><input type="text" name="visit_date" class="regular-text" placeholder="مثال: 1404/10/05" required style="width: 100% !important; max-width: 300px !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" /></td></tr>';

        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">شرح حال بیمار</th><td style="padding: 10px 0 !important;"><textarea name="complaint" rows="3" class="large-text" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;"></textarea></td></tr>';
        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">تشخیص پزشک</th><td style="padding: 10px 0 !important;"><textarea name="diagnosis" rows="3" class="large-text" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;"></textarea></td></tr>';

        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important;">داروهای تجویزشده</th><td style="padding: 10px 0 !important;">';
        echo '<div id="medications-list">';
        echo '<input type="text" name="medications[]" class="regular-text" placeholder="نام دارو" style="width: 100% !important; max-width: 300px !important; padding: 10px !important; margin-bottom: 8px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />';
        echo '</div>';
        echo '<button type="button" class="button" onclick="mrAddMedication()" style="background: #e0e0e0 !important; border: 1px solid #ccc !important; padding: 6px 12px !important; border-radius: 4px !important; margin-top: 5px !important;">+ افزودن دارو</button>';
        echo '</td></tr>';

        echo '<tr><th style="padding: 10px 0 !important; width: 25% !important; text-align: left !important; vertical-align: top !important;">فایل‌های پیوست</th><td style="padding: 10px 0 !important;">';
        echo '<div id="files-list" style="margin-bottom: 10px !important;">';
        echo '<div class="file-item" style="display: flex !important; align-items: center !important; gap: 10px !important; margin-bottom: 10px !important;">';
        echo '<input type="text" name="file_titles[]" class="regular-text" placeholder="عنوان فایل" style="width: 40% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />';
        echo '<input type="text" name="file_urls[]" class="regular-text file-url-input" placeholder="لینک فایل" style="width: 45% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" readonly />';
        echo '<button type="button" class="button upload-file-btn" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 8px 12px !important; border-radius: 4px !important;">انتخاب فایل</button>';
        echo '</div>';
        echo '</div>';
        echo '<button type="button" class="button" onclick="mrAddFile()" style="background: #e0e0e0 !important; border: 1px solid #ccc !important; padding: 6px 12px !important; border-radius: 4px !important;">+ افزودن فایل</button>';
        echo '</td></tr>';
        echo '</table>';

        echo '<p style="margin-top: 25px !important;">';
        echo '<input type="submit" name="mr_save_visit" class="button button-primary" value="ذخیره ویزیت" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 24px !important; border-radius: 6px !important; font-weight: bold !important;" />';
        echo ' <a href="' . admin_url('admin.php?page=mr-view-record&user_id=' . $patient_id) . '" class="button" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 24px !important; border-radius: 6px !important;">لغو</a>';
        echo '</p>';
        echo '</form>';

        // اسکریپت اصلاح‌شده برای چندفایلی
        echo '<script>
        jQuery(document).ready(function($) {
            let uploaders = {};

            function createUploader(button, index) {
                if (uploaders[index]) return uploaders[index];
                
                const frame = wp.media({
                    title: "انتخاب فایل",
                    button: { text: "انتخاب این فایل" },
                    multiple: false
                });

                frame.on("select", function() {
                    const attachment = frame.state().get("selection").first().toJSON();
                    button.siblings(".file-url-input").val(attachment.url);
                });

                uploaders[index] = frame;
                return frame;
            }

            $(document).on("click", ".upload-file-btn", function(e) {
                e.preventDefault();
                const button = $(this);
                const container = button.closest(".file-item");
                const index = container.index();
                const frame = createUploader(button, index);
                frame.open();
            });
        });

        function mrAddMedication() {
            const container = document.getElementById("medications-list");
            const input = document.createElement("input");
            input.type = "text";
            input.name = "medications[]";
            input.placeholder = "نام دارو";
            input.style.cssText = "width: 100% !important; max-width: 300px !important; padding: 10px !important; margin-bottom: 8px !important; border: 1px solid #ddd !important; border-radius: 6px !important;";
            container.appendChild(document.createElement("br"));
            container.appendChild(input);
        }
        function mrAddFile() {
            const container = document.getElementById("files-list");
            const div = document.createElement("div");
            div.className = "file-item";
            div.style.cssText = "display: flex !important; align-items: center !important; gap: 10px !important; margin-bottom: 10px !important;";
            div.innerHTML = \'<input type="text" name="file_titles[]" class="regular-text" placeholder="عنوان فایل" style="width: 40% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />\'+
                            \'<input type="text" name="file_urls[]" class="regular-text file-url-input" placeholder="لینک فایل" style="width: 45% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" readonly />\'+
                            \'<button type="button" class="button upload-file-btn" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 8px 12px !important; border-radius: 4px !important;">انتخاب فایل</button>\';
            container.appendChild(div);
        }
        </script>';
        echo '</div>';
    }



    // ========== ایجاد پرونده ==========
    add_action('admin_post_mr_create_record', 'mr_handle_create_record');
    function mr_handle_create_record() {
        if (!current_user_can('manage_options')) {
            wp_die('مجاز نیستید.');
        }

        $user_id = intval($_GET['user_id']);
        if ($user_id > 0) {
            $empty_record = [
                'illnesses'    => '',
                'medications'  => '',
                'last_visit'   => '',
                'notes'        => ''
            ];
            update_user_meta($user_id, 'medical_record_data', $empty_record);
        }

        wp_redirect(admin_url('admin.php?page=medical-records'));
        exit;
    }
 // ========== حذف پرونده ==========
    add_action('admin_post_mr_delete_record', 'mr_handle_delete_record');
    function mr_handle_delete_record() {
        if (!current_user_can('manage_options')) {
            wp_die('مجاز نیستید.');
        }

        $user_id = intval($_GET['user_id']);
        if ($user_id > 0) {
            delete_user_meta($user_id, 'medical_record_data');
            delete_user_meta($user_id, 'medical_visits');
        }

        wp_redirect(admin_url('admin.php?page=medical-records'));
        exit;
    }
// ========== Delete Record Handler ==========
add_action('admin_post_mr_delete_record', 'mr_handle_delete_record');
function mr_handle_delete_record() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You are not allowed.', 'medical-records'));
    }

    $user_id = intval($_GET['user_id']);
    if ($user_id > 0) {
        delete_user_meta($user_id, 'medical_record_data');
        delete_user_meta($user_id, 'medical_visits');
    }

    wp_redirect(admin_url('admin.php?page=medical-records'));
    exit;
}
