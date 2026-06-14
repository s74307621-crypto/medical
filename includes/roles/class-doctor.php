<?php
/**
 * Medical Records Plugin - Doctor Dashboard
 * Uses Bookly staff as doctors
 * 
 * @package Medical_Records
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('medical_doctor_dashboard', 'mr_doctor_dashboard_shortcode');
function mr_doctor_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">لطفاً وارد شوید.</div>';
    }
    
    // Check if current user is a Bookly doctor
    $current_user = wp_get_current_user();
    $bookly_doctor = mr_get_doctor_by_wp_user_id($current_user->ID);
    
    if (!$bookly_doctor && !in_array('administrator', (array) $current_user->roles)) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">این بخش فقط برای پزشکان است.</div>';
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'view_patient') {
        return mr_doctor_view_patient_page(intval($_GET['user_id']));
    }
    if (isset($_POST['mr_save_visit_frontend'])) {
        return mr_doctor_handle_visit_submission();
    }
    if (isset($_GET['action']) && $_GET['action'] === 'add_visit') {
        return mr_doctor_add_visit_form(intval($_GET['user_id']));
    }
    if (isset($_GET['action']) && $_GET['action'] === 'create_record') {
        return mr_doctor_create_record(intval($_GET['user_id']));
    }
    return mr_doctor_patients_list();
}


	 // ========== لیست بیماران برای پزشک ==========
    function mr_doctor_patients_list() {
        $search_query = isset($_GET['mr_search']) ? sanitize_text_field($_GET['mr_search']) : '';
        $order_dir = (isset($_GET['mr_dir']) && $_GET['mr_dir'] === 'desc') ? 'DESC' : 'ASC';

        $args = [
            'role'        => 'subscriber',
            'orderby'     => 'ID',
            'order'       => $order_dir,
            'number'      => -1,
        ];

        if (!empty($search_query)) {
            $args['search'] = '*' . $search_query . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $patients = get_users($args);
        $base_url = remove_query_arg(['mr_dir']);
        $sort_id_url = add_query_arg(['mr_dir' => ($order_dir === 'ASC' ? 'desc' : 'asc')], $base_url);
        $sort_indicator = ($order_dir === 'ASC') ? ' ↑' : ' ↓';

        $output = '<div class="mr-container" style="max-width: 1200px !important; margin: 20px auto !important; padding: 0 15px !important;">';
        $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important; margin-bottom: 25px !important;">';
        $output .= '<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 20px !important;">';
        $output .= '<h2 style="color: #396cf0 !important; margin: 0 !important;">لیست بیماران</h2>';
        $output .= '<a href="' . esc_url(add_query_arg('action', 'booking_settings')) . '" style="background: #4caf50 !important; color: white !important; padding: 10px 20px !important; border-radius: 8px !important; text-decoration: none !important; font-weight: bold !important;">⚙️ تنظیمات نوبت‌گیری</a>';
        $output .= '</div>';
// ========== محاسبه و نمایش میانگین امتیاز کل پزشک ==========
$current_user_id = get_current_user_id();
$all_patients = get_users(['role' => 'subscriber']);
$all_ratings = [];

foreach ($all_patients as $patient) {
    $visits = get_user_meta($patient->ID, 'medical_visits', true);
    if (is_array($visits)) {
        foreach ($visits as $visit) {
            if (!empty($visit['doctor_id']) && $visit['doctor_id'] == $current_user_id && !empty($visit['rating'])) {
                $all_ratings[] = $visit['rating'];
            }
        }
    }
}

if (!empty($all_ratings)) {
    $avg_rating = round(array_sum($all_ratings) / count($all_ratings), 1);
    $stars = str_repeat('★', floor($avg_rating)) . str_repeat('☆', 5 - floor($avg_rating));
    $output .= '<div style="background: #f0f7ff; padding: 12px; border-radius: 8px; margin-bottom: 20px; display: inline-block;">';
    $output .= '<strong style="color: #396cf0 !important; font-size: 16px !important;">میانگین نظرات بیماران شما : ' . $avg_rating . '/5 ' . $stars . '</strong>';
    $output .= '</div>';
}
        $output .= '<form method="get" style="display: flex !important; align-items: center !important; margin-bottom: 20px !important; gap: 10px !important; flex-wrap: wrap !important;">';
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['mr_search', 'mr_dir', 'action', 'user_id'])) {
                $output .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }
        }
        $output .= '<input type="text" name="mr_search" value="' . esc_attr($search_query) . '" placeholder="جستجو بر اساس نام، ایمیل یا نام کاربری..." style="padding: 10px 15px !important; border: 1px solid #ddd !important; border-radius: 8px !important; font-size: 14px !important; width: 300px !important;" />';
        $output .= '<button type="submit" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 20px !important; border-radius: 8px !important; font-weight: bold !important;">جستجو</button>';
        if (!empty($search_query)) {
            $clear_url = remove_query_arg(['mr_search']);
            $output .= '<a href="' . esc_url($clear_url) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 20px !important; border-radius: 8px !important; text-decoration: none !important; margin-left: 8px !important;">پاک کردن</a>';
        }
        $output .= '</form>';

        $output .= '<p style="margin-bottom: 20px !important;"><strong>مرتب‌سازی بر اساس آیدی: </strong> ';
        $output .= '<a href="' . esc_url($sort_id_url) . '" style="background: #eef4ff !important; color: #396cf0 !important; border: 1px solid #c2d8ff !important; padding: 8px 16px !important; border-radius: 8px !important; text-decoration: none !important; font-weight: bold !important;"> ID کاربر' . $sort_indicator . '</a></p>';

        if (empty($patients)) {
            $output .= '<p style="padding: 20px !important; text-align: center !important; color: #888 !important; background: #fafafa !important; border-radius: 8px !important;">بیماری یافت نشد.</p>';
        } else {
            $output .= '<div style="overflow-x: auto !important;">';
            $output .= '<table style="width: 100% !important; border-collapse: collapse !important; background: white !important; border-radius: 10px !important; overflow: hidden !important;">';
            $output .= '<thead><tr style="background: #f8fafc !important;">';
            $output .= '<th style="padding: 14px !important; text-align: right !important; border-bottom: 1px solid #eee !important;">ID کاربر</th>';
            $output .= '<th style="padding: 14px !important; text-align: right !important; border-bottom: 1px solid #eee !important;">نام کامل</th>';
            $output .= '<th style="padding: 14px !important; text-align: right !important; border-bottom: 1px solid #eee !important;">ایمیل</th>';
            $output .= '<th style="padding: 14px !important; text-align: right !important; border-bottom: 1px solid #eee !important;">عملیات</th>';
            $output .= '</tr></thead><tbody>';

            foreach ($patients as $patient) {
                $has_record = !empty(get_user_meta($patient->ID, 'medical_record_data', true));
                $output .= '<tr style="border-bottom: 1px solid #f0f0f0 !important;">';
                $output .= '<td data-label="ID کاربر" style="padding: 14px !important; text-align: right !important;">' . esc_html($patient->ID) . '</td>';
                $output .= '<td data-label="نام کامل" style="padding: 14px !important; text-align: right !important;">' . esc_html($patient->display_name) . '</td>';
                $output .= '<td data-label="ایمیل" style="padding: 14px !important; text-align: right !important;">' . esc_html($patient->user_email) . '</td>';
                $output .= '<td data-label="عملیات" style="padding: 14px !important; text-align: right !important;">';

                if (!$has_record) {
                    $output .= '<a href="' . esc_url(add_query_arg(['action' => 'create_record', 'user_id' => $patient->ID])) . '" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 8px 16px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important; margin-left: 5px; margin-bottom: 5px;">ایجاد پرونده</a>';
                } else {
                    $output .= '<a href="' . esc_url(add_query_arg(['action' => 'view_patient', 'user_id' => $patient->ID])) . '" style="background: #eef4ff !important; color: #396cf0 !important; border: 1px solid #c2d8ff !important; padding: 8px 16px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important; margin-left: 5px; margin-bottom: 5px;">نمایش پرونده</a> ';
                    $output .= '<a href="' . esc_url(add_query_arg(['action' => 'add_visit', 'user_id' => $patient->ID])) . '" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 8px 16px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important; margin-left: 5px; margin-bottom: 5px;">ثبت ویزیت</a>';
                }

                $output .= '</td></tr>';
            }
            $output .= '</tbody></table>';
            $output .= '</div>';
        }
        $output .= '</div></div>';
        return $output;
    }

	 // ========== نمایش پرونده + فیلتر ویزیت‌ها ==========
    function mr_doctor_view_patient_page($user_id) {
        $patient = get_user_by('ID', $user_id);
        if (!$patient || !in_array('subscriber', (array) $patient->roles)) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">بیمار معتبر نیست.</div>';
        }

        $all_visits = get_user_meta($user_id, 'medical_visits', true);
        $all_visits = is_array($all_visits) ? $all_visits : [];

        // محاسبه آخرین ویزیت
        $last_visit_date = '—';
        if (!empty($all_visits)) {
            usort($all_visits, function($a, $b) {
                return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? '');
            });
            $last_visit_date = $all_visits[0]['visit_date'] ?? '—';
        }

        // استخراج لیست منحصر به فرد پزشکان
        $doctor_ids = [];
        foreach ($all_visits as $visit) {
            if (!empty($visit['doctor_id'])) {
                $doctor_ids[] = $visit['doctor_id'];
            }
        }
        $doctor_ids = array_unique($doctor_ids);
        $doctors = [];
        foreach ($doctor_ids as $id) {
            $doc = get_user_by('ID', $id);
            if ($doc) $doctors[$id] = $doc->display_name;
        }

        // دریافت فیلترها
        $filter_doctor = isset($_GET['filter_doctor']) ? intval($_GET['filter_doctor']) : '';
        $filter_from = isset($_GET['filter_from']) ? sanitize_text_field($_GET['filter_from']) : '';
        $filter_to = isset($_GET['filter_to']) ? sanitize_text_field($_GET['filter_to']) : '';

        // فیلتر کردن ویزیت‌ها
        $filtered_visits = $all_visits;
        if ($filter_doctor) {
            $filtered_visits = array_filter($filtered_visits, function($v) use ($filter_doctor) {
                return ($v['doctor_id'] ?? 0) == $filter_doctor;
            });
        }
        if ($filter_from || $filter_to) {
            $filtered_visits = array_filter($filtered_visits, function($v) use ($filter_from, $filter_to) {
                $date = $v['visit_date'] ?? '';
                if ($filter_from && strcmp($date, $filter_from) < 0) return false;
                if ($filter_to && strcmp($date, $filter_to) > 0) return false;
                return true;
            });
        }
        $filtered_visits = array_values($filtered_visits);

        $output = '<div class="mr-container" style="max-width: 1200px !important; margin: 20px auto !important; padding: 0 15px !important;">';
        $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important; margin-bottom: 25px !important;">';
        $output .= '<h2 style="color: #396cf0 !important; margin-top: 0 !important; margin-bottom: 20px !important;">پروندهٔ بیمار: ' . esc_html($patient->display_name) . '</h2>';
        $output .= '<p><a href="' . esc_url(remove_query_arg(['action', 'user_id'])) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 8px 16px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important;">بازگشت به لیست</a></p>';

        // خلاصه پرونده
        // جایگزینی بخش خلاصه پرونده
        echo mr_get_medical_info($user_id);

        // ========== فیلترهای ویزیت ==========
        $output .= '<h3 style="color: #396cf0 !important; margin: 25px 0 15px !important;">فیلتر ویزیت‌ها</h3>';
        $output .= '<form method="get" style="background: #fafcff !important; padding: 20px !important; border-radius: 10px !important; margin-bottom: 25px !important;">';
        foreach (['action', 'user_id'] as $param) {
            if (isset($_GET[$param])) {
                $output .= '<input type="hidden" name="' . esc_attr($param) . '" value="' . esc_attr($_GET[$param]) . '" />';
            }
        }
        $output .= '<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important; gap: 15px !important; margin-bottom: 15px !important;">';
        
        // فیلتر پزشک
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">فیلتر بر اساس پزشک</label>';
        $output .= '<select name="filter_doctor" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">';
        $output .= '<option value="">همه پزشک‌ها</option>';
        foreach ($doctors as $id => $name) {
            $selected = ($filter_doctor == $id) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($name) . '</option>';
        }
        $output .= '</select>';
        $output .= '</div>';

        // تاریخ شروع
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">از تاریخ (شمسی)</label>';
        $output .= '<input type="text" name="filter_from" value="' . esc_attr($filter_from) . '" placeholder="1404/01/01" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" />';
        $output .= '</div>';

        // تاریخ پایان
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">تا تاریخ (شمسی)</label>';
        $output .= '<input type="text" name="filter_to" value="' . esc_attr($filter_to) . '" placeholder="1404/12/29" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" />';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div>';
        $output .= '<button type="submit" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 20px !important; border-radius: 6px !important; font-weight: bold !important; margin-right: 10px !important;">اعمال فیلتر</button> ';
        $output .= '<a href="' . esc_url(add_query_arg(['action' => 'view_patient', 'user_id' => $user_id], remove_query_arg(['filter_doctor', 'filter_from', 'filter_to']))) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 20px !important; border-radius: 6px !important; text-decoration: none !important;">پاک کردن فیلتر</a>';
        $output .= '</div>';
        $output .= '</form>';

        // ========== نمایش ویزیت‌های فیلترشده ==========
        $output .= '<h3 style="color: #396cf0 !important; margin: 25px 0 15px !important;">ویزیت‌ها (' . count($filtered_visits) . ' مورد)</h3>';
        if (empty($filtered_visits)) {
            $output .= '<p style="background: #f9f9f9 !important; padding: 20px !important; border-radius: 8px !important; color: #777 !important; text-align: center !important;">ویزیتی یافت نشد.</p>';
        } else {
            foreach (array_reverse($filtered_visits) as $visit) {
                $doc = get_user_by('ID', $visit['doctor_id'] ?? 0);
                $output .= '<div style="background: white !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; padding: 24px !important; margin: 20px 0 !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important; word-break: break-word !important; overflow-wrap: break-word !important; position: relative !important;">';

                // هدر ویزیت
                $output .= '<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 20px !important; padding-bottom: 12px !important; border-bottom: 1px solid #edf2f7 !important;">';
                $output .= '<div style="display: flex !important; align-items: center !important; gap: 10px !important;">';
                $output .= '<span style="background: #396cf0 !important; color: white !important; width: 32px !important; height: 32px !important; border-radius: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 14px !important;">و</span>';
                $output .= '<h4 style="margin: 0 !important; color: #1a202c !important; font-size: 18px !important; font-weight: 700 !important;">' . esc_html($visit['visit_date'] ?? '—') . '</h4>';
                $output .= '</div>';
                $output .= '<div style="background: #ebf8ff !important; color: #2b6cb0 !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 14px !important; font-weight: 600 !important;">' . ($doc ? esc_html($doc->display_name) : '—') . '</div>';
                $output .= '</div>';

                // محتوای ویزیت — گرید با محدودیت عرض و کلمه‌شکنی
                $output .= '<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important; gap: 20px !important; width: 100% !important; box-sizing: border-box !important;">';

                // شرح حال
                $output .= '<div style="flex: 1 !important; min-width: 0 !important;">';
                $output .= '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📄</span> شرح حال بیمار</h5>';
                $output .= '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important; word-break: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important;">' . (empty($visit['complaint']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['complaint'])) . '</p>';
                $output .= '</div>';

                // تشخیص
                $output .= '<div style="flex: 1 !important; min-width: 0 !important;">';
                $output .= '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>⚕️</span> تشخیص پزشک</h5>';
                $output .= '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important; word-break: break-word !important; overflow-wrap: break-word !important; max-width: 100% !important;">' . (empty($visit['diagnosis']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['diagnosis'])) . '</p>';
                $output .= '</div>';

                // داروها
                if (!empty($visit['medications']) && is_array($visit['medications'])) {
                    $output .= '<div style="flex: 1 !important; min-width: 0 !important;">';
                    $output .= '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>💊</span> داروها</h5>';
                    $output .= '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
                    foreach ($visit['medications'] as $med) {
                        if (!empty($med)) $output .= '<li style="word-break: break-word !important; overflow-wrap: break-word !important;">' . esc_html($med) . '</li>';
                    }
                    $output .= '</ul>';
                    $output .= '</div>';
                }

                // فایل‌ها
                if (!empty($visit['files']) && is_array($visit['files'])) {
                    $output .= '<div style="flex: 1 !important; min-width: 0 !important;">';
                    $output .= '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📎</span> فایل‌ها</h5>';
                    $output .= '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
                    foreach ($visit['files'] as $file) {
                        if (!empty($file['title']) && !empty($file['url'])) {
                            $output .= '<li style="word-break: break-word !important; overflow-wrap: break-word !important;"><a href="' . esc_url($file['url']) . '" target="_blank" style="color: #396cf0 !important; text-decoration: underline !important; word-break: break-word !important; overflow-wrap: break-word !important;">' . esc_html($file['title']) . '</a></li>';
                        }
                    }
                    $output .= '</ul>';
                    $output .= '</div>';
                }
				// نمایش امتیاز در پنل پزشک
if (!empty($visit['rating'])) {
    $rating_stars = str_repeat('★', $visit['rating']) . str_repeat('☆', 5 - $visit['rating']);
    $output .= '<div style="flex: 1 !important; min-width: 0 !important;">';
    $output .= '<h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>⭐</span> امتیاز بیمار</h5>';
    $output .= '<p style="margin: 0 !important; color: #396cf0 !important; font-weight: bold;">' . $rating_stars . '</p>';
    $output .= '</div>';
}

                $output .= '</div>'; // پایان گرید
                $output .= '</div>'; // پایان کارت
            }
        }

        $output .= '<p style="margin-top: 25px !important;">';
        $output .= '<a href="' . esc_url(add_query_arg(['action' => 'add_visit', 'user_id' => $user_id])) . '" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 24px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important;">ثبت ویزیت جدید</a> ';
        $output .= '<a href="' . esc_url(remove_query_arg(['action', 'user_id'])) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 24px !important; border-radius: 6px !important; text-decoration: none !important; display: inline-block !important;">بازگشت به لیست</a>';
        $output .= '</p>';
        $output .= '</div></div>';
        return $output;
    }

	 // mr_doctor_add_visit_form()
    function mr_doctor_add_visit_form($user_id) {
        $patient = get_user_by('ID', $user_id);
        if (!$patient || !in_array('subscriber', (array) $patient->roles)) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">بیمار معتبر نیست.</div>';
        }

        $current_user = wp_get_current_user();

        $output = '<div class="mr-container" style="max-width: 1200px !important; margin: 20px auto !important; padding: 0 15px !important;">';
        $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important; margin-bottom: 25px !important;">';
        $output .= '<h2 style="color: #396cf0 !important; margin-top: 0 !important; margin-bottom: 20px !important;">ثبت ویزیت برای: ' . esc_html($patient->display_name) . '</h2>';
        // اضافه‌کردن enctype برای آپلود فایل
        $output .= '<form method="post" enctype="multipart/form-data" style="background: white !important; padding: 0 !important;">';
        $output .= wp_nonce_field('mr_save_visit_frontend_action', 'mr_visit_nonce', true, false);
        $output .= '<input type="hidden" name="patient_id" value="' . esc_attr($user_id) . '" />';
        $output .= '<input type="hidden" name="doctor_id" value="' . esc_attr($current_user->ID) . '" />';

        $output .= '<div style="margin-bottom: 20px !important;">';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">تاریخ ویزیت (شمسی)</label>';
        $output .= '<input type="text" name="visit_date" style="width: 100% !important; max-width: 300px !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important; direction: ltr !important; text-align: left !important;" placeholder="مثال: 1404/10/05" required />';
        $output .= '</div>';

        $output .= '<div style="margin-bottom: 20px !important;">';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">شرح حال بیمار</label>';
        $output .= '<textarea name="complaint" rows="3" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;"></textarea>';
        $output .= '</div>';

        $output .= '<div style="margin-bottom: 20px !important;">';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">تشخیص پزشک</label>';
        $output .= '<textarea name="diagnosis" rows="3" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;"></textarea>';
        $output .= '</div>';

        $output .= '<div style="margin-bottom: 20px !important;">';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">داروهای تجویزشده</label>';
        $output .= '<div id="meds-frontend" style="margin-bottom: 10px !important;">';
        $output .= '<input type="text" name="medications[]" style="width: 100% !important; max-width: 300px !important; padding: 10px !important; margin-bottom: 8px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" placeholder="نام دارو" />';
        $output .= '</div>';
        $output .= '<button type="button" style="background: #e0e0e0 !important; border: 1px solid #ccc !important; padding: 6px 12px !important; border-radius: 4px !important;" onclick="mrAddMedFrontend()">+ افزودن دارو</button>';
        $output .= '</div>';

        $output .= '<div style="margin-bottom: 20px !important;">';
        $output .= '<label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">فایل‌های پیوست</label>';
        $output .= '<div id="files-frontend" style="margin-bottom: 10px !important;">';
        $output .= '<div class="file-item" style="display: flex !important; align-items: center !important; gap: 10px !important; margin-bottom: 10px !important;">';
        $output .= '<input type="text" name="file_titles[]" placeholder="عنوان فایل" style="width: 40% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />';
        $output .= '<input type="file" name="file_uploads[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="width: 55% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<button type="button" style="background: #e0e0e0 !important; border: 1px solid #ccc !important; padding: 6px 12px !important; border-radius: 4px !important;" onclick="mrAddFileFrontend()">+ افزودن فایل</button>';
        $output .= '</div>';

        $output .= '<p style="margin-top: 25px !important;">';
        $output .= '<input type="submit" name="mr_save_visit_frontend" class="button button-primary" value="ثبت ویزیت" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 24px !important; border-radius: 6px !important; font-weight: bold !important;" />';
        $output .= ' <a href="' . esc_url(remove_query_arg(['action', 'user_id'])) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 24px !important; border-radius: 6px !important; text-decoration: none !important;">لغو</a>';
        $output .= '</p>';
        $output .= '</form>';

        $output .= '<script>
        function mrAddMedFrontend() {
            const container = document.getElementById("meds-frontend");
            const input = document.createElement("input");
            input.type = "text";
            input.name = "medications[]";
            input.placeholder = "نام دارو";
            input.style.cssText = "width: 100% !important; max-width: 300px !important; padding: 10px !important; margin-bottom: 8px !important; border: 1px solid #ddd !important; border-radius: 6px !important;";
            container.appendChild(document.createElement("br"));
            container.appendChild(input);
        }
        function mrAddFileFrontend() {
            const container = document.getElementById("files-frontend");
            const div = document.createElement("div");
            div.className = "file-item";
            div.style.cssText = "display: flex !important; align-items: center !important; gap: 10px !important; margin-bottom: 10px !important;";
            div.innerHTML = \'<input type="text" name="file_titles[]" placeholder="عنوان فایل" style="width: 40% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />\'+
                            \'<input type="file" name="file_uploads[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="width: 55% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;" />\';
            container.appendChild(div);
        }
        </script>';
        $output .= '</div></div>';
        return $output;
    }

	 // mr_doctor_create_record()
    function mr_doctor_create_record($user_id) {
        $patient = get_user_by('ID', $user_id);
        if (!$patient || !in_array('subscriber', (array) $patient->roles)) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">بیمار معتبر نیست.</div>';
        }

        $empty_record = [
            'illnesses'    => '',
            'medications'  => '',
            'last_visit'   => '',
            'notes'        => ''
        ];
        update_user_meta($user_id, 'medical_record_data', $empty_record);

        $redirect_url = add_query_arg(['action' => 'add_visit', 'user_id' => $user_id], $_SERVER['REQUEST_URI']);
        wp_redirect($redirect_url);
        exit;
    }

	// ========== پردازش ویزیت توسط پزشک ==========
    function mr_doctor_handle_visit_submission() {
        if (!wp_verify_nonce($_POST['mr_visit_nonce'] ?? '', 'mr_save_visit_frontend_action')) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">خطا در اعتبارسنجی.</div>';
        }

        $user = wp_get_current_user();
        if (!in_array('editor', (array) $user->roles) && !in_array('administrator', (array) $user->roles)) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">شما مجاز نیستید.</div>';
        }

        $patient_id = intval($_POST['patient_id'] ?? 0);
        $doctor_id = intval($_POST['doctor_id'] ?? $user->ID);
        $visit_date = sanitize_text_field($_POST['visit_date'] ?? '');
        $complaint = sanitize_textarea_field($_POST['complaint'] ?? '');
        $diagnosis = sanitize_textarea_field($_POST['diagnosis'] ?? '');
        $medications = array_filter(array_map('trim', (array) $_POST['medications'] ?? []), 'strlen');

        $files = [];
        $file_titles = $_POST['file_titles'] ?? [];
        $file_uploads = $_FILES['file_uploads'] ?? [];

        if (!empty($file_uploads['name'])) {
            $count = is_array($file_uploads['name']) ? count($file_uploads['name']) : 1;
            for ($i = 0; $i < $count; $i++) {
                if (!empty($file_uploads['name'][$i]) && !empty($file_titles[$i])) {
                    $file_array = [
                        'name'     => $file_uploads['name'][$i],
                        'type'     => $file_uploads['type'][$i],
                        'tmp_name' => $file_uploads['tmp_name'][$i],
                        'error'    => $file_uploads['error'][$i],
                        'size'     => $file_uploads['size'][$i]
                    ];

                    $upload = wp_handle_upload($file_array, [
                        'test_form' => false,
                        'mimes' => [
                            'jpg'  => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png'  => 'image/png',
                            'pdf'  => 'application/pdf',
                            'doc'  => 'application/msword',
                            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ]
                    ]);

                    if (!isset($upload['error'])) {
                        $files[] = [
                            'title' => sanitize_text_field($file_titles[$i]),
                            'url'   => $upload['url']
                        ];
                    }
                }
            }
        }

        if ($patient_id <= 0 || empty($visit_date)) {
            return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">بیمار یا تاریخ ویزیت معتبر نیست.</div>';
        }

        $new_visit = [
			'id' => uniqid('visit_', true),
            'doctor_id'   => $doctor_id,
            'visit_date'  => $visit_date,
            'complaint'   => $complaint,
            'diagnosis'   => $diagnosis,
            'medications' => $medications,
            'files'       => $files,
			'rating'      => 0
        ];

        $visits = get_user_meta($patient_id, 'medical_visits', true);
        if (!is_array($visits)) $visits = [];
        $visits[] = $new_visit;
        update_user_meta($patient_id, 'medical_visits', $visits);

        $redirect_url = add_query_arg([
            'action' => 'view_patient',
            'user_id' => $patient_id
        ], $_SERVER['REQUEST_URI']);
        wp_redirect($redirect_url);
        exit;
    }