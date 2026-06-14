<?php
/**
 * بخش تنظیمات نوبت‌های دکتر
 */

// اضافه کردن تنظیمات نوبت‌گیری به داشبورد دکتر (AJAX handler در ادامه فایل ثبت شده)

function mr_doctor_booking_settings() {
    if (!is_user_logged_in()) {
        return '';
    }
    
    $user = wp_get_current_user();
    if (!in_array('editor', (array)$user->roles) && !in_array('administrator', (array)$user->roles)) {
        return '';
    }
    
    $doctor_id = $user->ID;
    $settings = MR_Booking::get_doctor_settings($doctor_id);
    
    $output = '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important; margin-bottom: 25px !important;">';
    $output .= '<h3 style="color: #396cf0 !important; margin: 0 0 25px 0 !important;">تنظیمات نوبت‌گیری</h3>';
    
    $output .= '<form id="doctor_booking_settings_form" method="POST" style="display: grid; gap: 25px;">';
    $output .= wp_nonce_field('mr_booking_settings_nonce', '_wpnonce', true, false);
    
    // ساعات کاری
    $output .= '<div>';
    $output .= '<h4 style="color: #333 !important; margin-bottom: 15px !important;">ساعات کاری</h4>';
    $output .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
    
    $output .= '<div>';
    $output .= '<label style="display: block; margin-bottom: 5px; font-weight: bold;">ساعت شروع</label>';
    $output .= '<input type="time" name="working_hours[start]" value="' . esc_attr($settings['working_hours']['start'] ?? '09:00') . '" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" />';
    $output .= '</div>';
    
    $output .= '<div>';
    $output .= '<label style="display: block; margin-bottom: 5px; font-weight: bold;">ساعت پایان</label>';
    $output .= '<input type="time" name="working_hours[end]" value="' . esc_attr($settings['working_hours']['end'] ?? '17:00') . '" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" />';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    // روزهای کاری
    $output .= '<div>';
    $output .= '<h4 style="color: #333 !important; margin-bottom: 15px !important;">روزهای کاری</h4>';
    $output .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">';
    
    $days = [
        '1' => 'شنبه',
        '2' => 'یکشنبه',
        '3' => 'دوشنبه',
        '4' => 'سه‌شنبه',
        '5' => 'چهارشنبه',
        '6' => 'پنج‌شنبه',
        '7' => 'جمعه'
    ];
    
    $working_days = $settings['working_days'] ?? [];
    
    foreach ($days as $day_num => $day_name) {
        $checked = in_array((string)$day_num, $working_days) ? 'checked' : '';
        $output .= '<label style="display: flex; align-items: center; padding: 8px; background: #f9f9f9; border-radius: 6px; cursor: pointer;">';
        $output .= '<input type="checkbox" name="working_days[]" value="' . esc_attr($day_num) . '" ' . $checked . ' style="cursor: pointer; margin-left: 8px;" />';
        $output .= $day_name;
        $output .= '</label>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    // تاریخ‌های تعطیلی
    $output .= '<div>';
    $output .= '<h4 style="color: #333 !important; margin-bottom: 15px !important;">روزهای تعطیل</h4>';
    $output .= '<div id="holidays_container">';
    
    $holidays = $settings['holidays'] ?? [];
    if (!empty($holidays)) {
        foreach ($holidays as $holiday) {
            $output .= '<div style="display: flex; gap: 10px; margin-bottom: 10px;">';
            $output .= '<input type="date" name="holidays[]" value="' . esc_attr($holiday) . '" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" />';
            $output .= '<button type="button" onclick="this.closest(\'div\').remove()" style="background: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">حذف</button>';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    $output .= '<button type="button" onclick="mr_add_holiday()" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 6px; cursor: pointer;">اضافه کردن تاریخ تعطیلی</button>';
    $output .= '</div>';
    
    // نوع ملاقات‌های حضوری
    $output .= '<div>';
    $output .= '<h4 style="color: #333 !important; margin-bottom: 15px !important;">ملاقات حضوری</h4>';
    
    $in_person = $settings['appointment_types']['in_person'] ?? [];
    $in_person_enabled = $in_person['enabled'] ?? false;
    $in_person_price = $in_person['price'] ?? 0;
    
    $output .= '<div style="padding: 15px; background: #f9f9f9; border-radius: 8px;">';
    $output .= '<label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer;">';
    $output .= '<input type="checkbox" name="appointment_types[in_person][enabled]" value="1" ' . ($in_person_enabled ? 'checked' : '') . ' style="cursor: pointer; margin-left: 10px;" onchange="mr_toggle_in_person_price()" />';
    $output .= '<strong>فعال کردن ملاقات حضوری</strong>';
    $output .= '</label>';
    
    $output .= '<div id="in_person_price_div" style="' . ($in_person_enabled ? '' : 'display: none;') . '">';
    $output .= '<label style="display: block; margin-bottom: 5px; font-weight: bold;">قیمت (تومان)</label>';
    $output .= '<input type="number" name="appointment_types[in_person][price]" value="' . esc_attr($in_person_price) . '" style="width: 200px; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" min="0" />';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // نوع ملاقات‌های انلاین
    $output .= '<div>';
    $output .= '<h4 style="color: #333 !important; margin-bottom: 15px !important;">ملاقات انلاین</h4>';
    
    $online = $settings['appointment_types']['online'] ?? [];
    $online_enabled = $online['enabled'] ?? false;
    
    $output .= '<label style="display: flex; align-items: center; margin-bottom: 20px; cursor: pointer;">';
    $output .= '<input type="checkbox" name="appointment_types[online][enabled]" value="1" ' . ($online_enabled ? 'checked' : '') . ' style="cursor: pointer; margin-left: 10px;" onchange="mr_toggle_online_services()" />';
    $output .= '<strong>فعال کردن ملاقات انلاین</strong>';
    $output .= '</label>';
    
    $output .= '<div id="online_services_div" style="' . ($online_enabled ? '' : 'display: none;') . '; border-left: 3px solid #396cf0; padding-left: 20px;">';
    
    // تصویری
    $output .= mr_booking_online_service_form('video', 'تصویری', $online['types']['video'] ?? []);
    
    // صوتی
    $output .= mr_booking_online_service_form('audio', 'صوتی', $online['types']['audio'] ?? []);
    
    // متنی
    $output .= mr_booking_online_service_form('text', 'متنی', $online['types']['text'] ?? []);
    
    // تلفنی
    $output .= mr_booking_online_service_form('phone', 'تلفنی', $online['types']['phone'] ?? []);
    
    $output .= '</div>';
    $output .= '</div>';
    
    // دکمه ذخیره
    $output .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">';
    $output .= '<button type="submit" id="save_booking_settings" style="background: #396cf0; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; cursor: pointer;">ذخیره تنظیمات</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    
    // JavaScript
    $output .= mr_booking_doctor_settings_js();
    
    return $output;
}

/**
 * فرم خدمات انلاین
 */
function mr_booking_online_service_form($type, $label, $service_data = []) {
    $enabled = $service_data['enabled'] ?? false;
    $prices = $service_data['prices'] ?? [];
    $type = sanitize_key($type);
    
    $output = '<div style="margin-bottom: 25px; padding: 15px; background: #f5f5f5; border-radius: 8px;">';
    
    $output .= '<label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer;">';
    $output .= '<input type="checkbox" name="appointment_types[online][types][' . esc_attr($type) . '][enabled]" value="1" ' . ($enabled ? 'checked' : '') . ' style="cursor: pointer; margin-left: 10px;" onchange="mr_toggle_service_prices(this)" />';
    $output .= '<strong>' . esc_html($label) . '</strong>';
    $output .= '</label>';
    
    $output .= '<div class="service-prices-container" style="' . ($enabled ? '' : 'display: none;') . '; padding-left: 20px;">';
    
    $durations = [10, 20, 30, 40];
    foreach ($durations as $duration) {
        $price = intval($prices[$duration] ?? 0);
        $output .= '<div style="display: grid; grid-template-columns: 100px 1fr; gap: 15px; margin-bottom: 10px; align-items: center;">';
        $output .= '<label style="font-weight: bold;">' . intval($duration) . ' دقیقه</label>';
        $output .= '<input type="number" name="appointment_types[online][types][' . esc_attr($type) . '][prices][' . intval($duration) . ']" value="' . esc_attr($price) . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="قیمت" min="0" />';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * JavaScript تنظیمات دکتر
 */
function mr_booking_doctor_settings_js() {
    $js = '<script>
    function mr_toggle_in_person_price() {
        var checked = jQuery("input[name=\"appointment_types[in_person][enabled]\"]").is(":checked");
        jQuery("#in_person_price_div").toggle(checked);
    }
    
    function mr_toggle_online_services() {
        var checked = jQuery("input[name=\"appointment_types[online][enabled]\"]").is(":checked");
        jQuery("#online_services_div").toggle(checked);
    }
    
    function mr_toggle_service_prices(element) {
        var checked = jQuery(element).is(":checked");
        jQuery(element).closest(".service-prices-container").siblings(".service-prices-container").toggle(checked);
    }
    
    function mr_add_holiday() {
        var html = "<div style=\"display: flex; gap: 10px; margin-bottom: 10px;\">";
        html += "<input type=\"date\" name=\"holidays[]\" style=\"flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px;\" />";
        html += "<button type=\"button\" onclick=\"this.closest(\'div\').remove()\" style=\"background: #e74c3c; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;\">حذف</button>";
        html += "</div>";
        jQuery("#holidays_container").append(html);
    }
    
    // ذخیره تنظیمات
    jQuery("#doctor_booking_settings_form").on("submit", function(e) {
        e.preventDefault();
        
        var formData = jQuery(this).serialize();
        
        jQuery.ajax({
            url: "' . admin_url('admin-ajax.php') . '",
            type: "POST",
            data: formData + "&action=mr_save_doctor_booking_settings&_wpnonce=' . wp_create_nonce('mr_booking_settings_nonce') . '",
            success: function(response) {
                if (response.success) {
                    alert("تنظیمات با موفقیت ذخیره شد");
                } else {
                    alert("خطا در ذخیره تنظیمات");
                }
            }
        });
    });
    </script>';
    
    return $js;
}

// AJAX handler برای ذخیره تنظیمات
add_action('wp_ajax_mr_save_doctor_booking_settings', 'mr_handle_save_doctor_booking_settings');

function mr_handle_save_doctor_booking_settings() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_settings_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $user = wp_get_current_user();
    if (!in_array('editor', (array)$user->roles) && !in_array('administrator', (array)$user->roles)) {
        wp_send_json_error('Access denied');
    }
    
    $doctor_id = $user->ID;
    
    // دریافت تنظیمات پیش‌فرض
    $settings = MR_Booking::get_default_settings();
    
    // بروزرسانی
    if (isset($_POST['working_hours'])) {
        $settings['working_hours'] = [
            'start' => sanitize_text_field($_POST['working_hours']['start'] ?? '09:00'),
            'end' => sanitize_text_field($_POST['working_hours']['end'] ?? '17:00')
        ];
    }
    
    if (isset($_POST['working_days'])) {
        $settings['working_days'] = array_map('sanitize_text_field', $_POST['working_days']);
    }
    
    if (isset($_POST['holidays'])) {
        $settings['holidays'] = array_map('sanitize_text_field', array_filter($_POST['holidays']));
    }
    
    // نوع ملاقات‌های مختلف
    if (isset($_POST['appointment_types'])) {
        $types = $_POST['appointment_types'];
        
        // حضوری
        if (isset($types['in_person'])) {
            $settings['appointment_types']['in_person'] = [
                'enabled' => isset($types['in_person']['enabled']),
                'price' => intval($types['in_person']['price'] ?? 0)
            ];
        }
        
        // انلاین
        if (isset($types['online'])) {
            $settings['appointment_types']['online'] = [
                'enabled' => isset($types['online']['enabled']),
                'types' => []
            ];
            
            if (isset($types['online']['types'])) {
                foreach (['video', 'audio', 'text', 'phone'] as $service_type) {
                    $settings['appointment_types']['online']['types'][$service_type] = [
                        'enabled' => isset($types['online']['types'][$service_type]['enabled']),
                        'prices' => []
                    ];
                    
                    if (isset($types['online']['types'][$service_type]['prices'])) {
                        foreach ([10, 20, 30, 40] as $duration) {
                            $settings['appointment_types']['online']['types'][$service_type]['prices'][$duration] = intval($types['online']['types'][$service_type]['prices'][$duration] ?? 0);
                        }
                    }
                }
            }
        }
    }
    
    MR_Booking::save_doctor_settings($doctor_id, $settings);
    
    wp_send_json_success('Settings saved successfully');
}

// AJAX handler برای دریافت خدمات دکتر
add_action('wp_ajax_mr_get_doctor_services', 'mr_handle_get_doctor_services');

function mr_handle_get_doctor_services() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    if ($doctor_id <= 0) {
        wp_send_json_error('Invalid doctor');
    }
    
    $settings = MR_Booking::get_doctor_settings($doctor_id);
    
    wp_send_json_success($settings['appointment_types']);
}

    /**
     * صفحهٔ مدیریت: فهرست دکترها و فرم ویرایش تنظیمات
     */
    function mr_doctor_schedules_page() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }

        $doctors = get_users(['role' => 'editor', 'number' => -1]);
        echo '<div class="wrap"><h1>تنظیمات نوبت‌گیری دکترها</h1>';

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['doctor_id'])) {
            $doctor_id = intval($_GET['doctor_id']);
            $settings = MR_Booking::get_doctor_settings($doctor_id);

            echo '<h2>ویرایش تنظیمات دکتر: ' . esc_html(get_userdata($doctor_id)->display_name) . '</h2>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('mr_admin_booking_settings_nonce', '_wpnonce');
            echo '<input type="hidden" name="action" value="mr_admin_save_doctor_settings" />';
            echo '<input type="hidden" name="doctor_id" value="' . esc_attr($doctor_id) . '" />';

            // simple fields: working hours and days
            echo '<p><label>ساعت شروع: <input type="time" name="working_hours[start]" value="' . esc_attr($settings['working_hours']['start']) . '" /></label></p>';
            echo '<p><label>ساعت پایان: <input type="time" name="working_hours[end]" value="' . esc_attr($settings['working_hours']['end']) . '" /></label></p>';

            $days = ['1'=>'شنبه','2'=>'یکشنبه','3'=>'دوشنبه','4'=>'سه‌شنبه','5'=>'چهارشنبه','6'=>'پنج‌شنبه','7'=>'جمعه'];
            echo '<p>روزهای کاری: ';
            foreach ($days as $k => $label) {
                $checked = in_array((string)$k, $settings['working_days']) ? 'checked' : '';
                echo '<label style="margin-left:10px;"><input type="checkbox" name="working_days[]" value="' . esc_attr($k) . '" ' . $checked . ' /> ' . esc_html($label) . '</label>';
            }
            echo '</p>';

            // in_person
            $in_person = $settings['appointment_types']['in_person'] ?? ['enabled'=>false,'price'=>0];
            echo '<p><label><input type="checkbox" name="appointment_types[in_person][enabled]" value="1" ' . ($in_person['enabled'] ? 'checked' : '') . ' /> فعال کردن ملاقات حضوری</label></p>';
            echo '<p><label>قیمت حضوری: <input type="number" name="appointment_types[in_person][price]" value="' . esc_attr($in_person['price']) . '" min="0" /></label></p>';

            // online types
            $online = $settings['appointment_types']['online'] ?? ['enabled'=>false,'types'=>[]];
            echo '<p><label><input type="checkbox" name="appointment_types[online][enabled]" value="1" ' . ($online['enabled'] ? 'checked' : '') . ' /> فعال کردن ملاقات انلاین</label></p>';
            foreach (['video'=>'تصویری','audio'=>'صوتی','text'=>'متنی','phone'=>'تلفنی'] as $type => $label) {
                $svc = $online['types'][$type] ?? ['enabled'=>false,'prices'=>[]];
                echo '<fieldset style="border:1px solid #ddd;padding:10px;margin-bottom:8px;">';
                echo '<legend>' . esc_html($label) . '</legend>';
                echo '<p><label><input type="checkbox" name="appointment_types[online][types][' . esc_attr($type) . '][enabled]" value="1" ' . ($svc['enabled'] ? 'checked' : '') . ' /> فعال</label></p>';
                foreach ([10,20,30,40] as $d) {
                    $price = intval($svc['prices'][$d] ?? 0);
                    echo '<p>' . intval($d) . ' دقیقه: <input type="number" name="appointment_types[online][types][' . esc_attr($type) . '][prices][' . intval($d) . ']" value="' . esc_attr($price) . '" min="0" /></p>';
                }
                echo '</fieldset>';
            }

            echo '<p><button class="button button-primary" type="submit">ذخیره تنظیمات</button> <a href="' . esc_url(admin_url('admin.php?page=mr-doctor-schedules')) . '" class="button">بازگشت</a></p>';
            echo '</form>';

        } else {
            echo '<h2>فهرست دکتر‌ها</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>نام</th><th>عملیات</th></tr></thead><tbody>';
            foreach ($doctors as $doc) {
                echo '<tr><td>' . esc_html($doc->ID) . '</td><td>' . esc_html($doc->display_name) . '</td><td><a class="button" href="' . esc_url(add_query_arg(['action'=>'edit','doctor_id'=>$doc->ID], admin_url('admin.php?page=mr-doctor-schedules'))) . '">ویرایش</a></td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    // پردازش فرم ادمین برای ذخیره تنظیمات دکترها
    add_action('admin_post_mr_admin_save_doctor_settings', 'mr_admin_save_doctor_settings');
    function mr_admin_save_doctor_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_admin_booking_settings_nonce')) {
            wp_die('Nonce نامعتبر');
        }

        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        if ($doctor_id <= 0) {
            wp_safe_redirect(add_query_arg('updated', '0', admin_url('admin.php?page=mr-doctor-schedules')));
            exit;
        }

        $settings = MR_Booking::get_default_settings();
        if (isset($_POST['working_hours'])) {
            $settings['working_hours'] = [
                'start' => sanitize_text_field($_POST['working_hours']['start'] ?? '09:00'),
                'end' => sanitize_text_field($_POST['working_hours']['end'] ?? '17:00')
            ];
        }
        if (isset($_POST['working_days'])) {
            $settings['working_days'] = array_map('sanitize_text_field', (array) $_POST['working_days']);
        }
        if (isset($_POST['holidays'])) {
            $settings['holidays'] = array_map('sanitize_text_field', array_filter((array) $_POST['holidays']));
        }

        if (isset($_POST['appointment_types'])) {
            $types = $_POST['appointment_types'];
            if (isset($types['in_person'])) {
                $settings['appointment_types']['in_person'] = [
                    'enabled' => isset($types['in_person']['enabled']),
                    'price' => intval($types['in_person']['price'] ?? 0)
                ];
            }
            if (isset($types['online'])) {
                $settings['appointment_types']['online'] = [
                    'enabled' => isset($types['online']['enabled']),
                    'types' => []
                ];
                if (isset($types['online']['types'])) {
                    foreach (['video','audio','text','phone'] as $service_type) {
                        $settings['appointment_types']['online']['types'][$service_type] = [
                            'enabled' => isset($types['online']['types'][$service_type]['enabled']),
                            'prices' => []
                        ];
                        if (isset($types['online']['types'][$service_type]['prices'])) {
                            foreach ([10,20,30,40] as $duration) {
                                $settings['appointment_types']['online']['types'][$service_type]['prices'][$duration] = intval($types['online']['types'][$service_type]['prices'][$duration] ?? 0);
                            }
                        }
                    }
                }
            }
        }

        MR_Booking::save_doctor_settings($doctor_id, $settings);

        wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=mr-doctor-schedules')));
        exit;
    }
