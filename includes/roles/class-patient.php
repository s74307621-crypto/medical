<?php 
add_shortcode('medical_patient_dashboard', 'mr_patient_dashboard_shortcode');
function mr_patient_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">لطفاً وارد شوید.</div>';
    }
    $user = wp_get_current_user();
    if (!in_array('subscriber', (array) $user->roles)) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">این بخش فقط برای بیماران است.</div>';
    }

    // ✅ اول چک کن که آیا فرم سابمیت شده
    if (isset($_POST['mr_save_profile'])) {
        return mr_patient_save_profile();
    }

    // ✅ بعد ببین که آیا می‌خواهد ویرایش کند
    if (isset($_GET['action']) && $_GET['action'] === 'edit_profile') {
        return mr_patient_edit_profile_form($user->ID);
    }

    return mr_patient_view_profile($user->ID);
}


// ========== نمایش پروفایل بیمار با امتیازدهی ==========
function mr_patient_view_profile($user_id) {
    $user = get_user_by('ID', $user_id);
    $record = get_user_meta($user_id, 'medical_record_data_v2', true);
    if (!is_array($record)) $record = [];
    $diseases = ['diabetes' => 'دیابت','hypertension' => 'فشار خون بالا','asthma' => 'آسم','heart_disease' => 'بیماری قلبی','thyroid' => 'بیماری تیروئید','allergy' => 'حساسیت‌های شدید'];
    $all_visits = get_user_meta($user_id, 'medical_visits', true);
    $all_visits = is_array($all_visits) ? $all_visits : [];
    // --- اطمینان از داشتن id یونیک برای هر ویزیت و ذخیره در دیتابیس ---
    $visits_changed = false;
    foreach ($all_visits as $i => $visit) {
        if (empty($visit['id'])) {
            $all_visits[$i]['id'] = uniqid('visit_', true);
            $visits_changed = true;
        }
    }
    if ($visits_changed) {
        update_user_meta($user_id, 'medical_visits', $all_visits);
    }
    $doctor_ids = []; foreach ($all_visits as $visit) { if (!empty($visit['doctor_id'])) $doctor_ids[] = $visit['doctor_id']; }
    $doctor_ids = array_unique($doctor_ids); $doctors = []; foreach ($doctor_ids as $id) { $doc = get_user_by('ID', $id); if ($doc) $doctors[$id] = $doc->display_name; }
    $filter_doctor = isset($_GET['filter_doctor']) ? intval($_GET['filter_doctor']) : '';
    $filtered_visits = $all_visits; if ($filter_doctor) { $filtered_visits = array_filter($filtered_visits, function($v) use ($filter_doctor) { return ($v['doctor_id'] ?? 0) == $filter_doctor; }); }
    $filtered_visits = array_values($filtered_visits);
    usort($filtered_visits, function($a, $b) { return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? ''); });

    $output = '<div class="mr-container" style="max-width: 1200px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important; margin-bottom: 25px !important;">';
    $output .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
    $output .= '<h2 style="color: #396cf0 !important; margin: 0 !important;">پروندهٔ من</h2>';
    $output .= '<a href="' . esc_url(add_query_arg('action', 'edit_profile')) . '" style="background: #396cf0 !important; color: white !important; padding: 8px 16px !important; border-radius: 6px !important; text-decoration: none !important;">ویرایش اطلاعات</a>';
    $output .= '</div>';
    $output .= mr_get_medical_info($user_id);

    $output .= '<h3 style="color: #396cf0 !important; margin: 25px 0 15px !important;">فیلتر ویزیت‌ها</h3>';
    $output .= '<form method="get" style="background: #fafcff !important; padding: 20px !important; border-radius: 10px !important; margin-bottom: 25px !important;">';
    $output .= '<input type="hidden" name="page_id" value="patient_dashboard" />';
    $output .= '<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important; gap: 15px !important;">';
    $output .= '<div><label style="display: block !important; margin-bottom: 5px !important; font-weight: bold !important;">فیلتر بر اساس پزشک</label>';
    $output .= '<select name="filter_doctor" style="width: 100% !important; padding: 10px !important; border: 1px solid #ddd !important; border-radius: 6px !important;">';
    $output .= '<option value="">همه پزشک‌ها</option>'; foreach ($doctors as $id => $name) { $selected = ($filter_doctor == $id) ? 'selected' : ''; $output .= '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($name) . '</option>'; }
    $output .= '</select></div></div>';
    $output .= '<div style="margin-top: 15px;"><button type="submit" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 10px 20px !important; border-radius: 6px !important; font-weight: bold !important;">اعمال فیلتر</button> ';
    $output .= '<a href="' . esc_url(remove_query_arg('filter_doctor')) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 10px 20px !important; border-radius: 6px !important; text-decoration: none !important;">پاک کردن فیلتر</a></div>';
    $output .= '</form>';

   $output .= '<h3 style="color: #396cf0 !important; margin: 25px 0 15px !important;">ویزیت‌های پزشک (' . count($filtered_visits) . ' مورد)</h3>';
if (empty($filtered_visits)) {
    $output .= '<p style="background: #f9f9f9 !important; padding: 20px !important; border-radius: 8px !important; color: #777 !important; text-align: center !important;">ویزیتی یافت نشد.</p>';
} else {
    foreach ($filtered_visits as $visit) {
        // مطمئن شوید هر ویزیت id دارد
        if (!isset($visit['id'])) {
            $visit['id'] = uniqid('visit_', true);
        }
        
        $doc = get_user_by('ID', $visit['doctor_id'] ?? 0);
        $output .= '<div style="background: white !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; padding: 24px !important; margin: 20px 0 !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
        $output .= '<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 20px !important; padding-bottom: 12px !important; border-bottom: 1px solid #edf2f7 !important;">';
        $output .= '<div style="display: flex !important; align-items: center !important; gap: 10px !important;">';
        $output .= '<span style="background: #396cf0 !important; color: white !important; width: 32px !important; height: 32px !important; border-radius: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: bold !important; font-size: 14px !important;">و</span>';
        $output .= '<h4 style="margin: 0 !important; color: #1a202c !important; font-size: 18px !important; font-weight: 700 !important;">' . esc_html($visit['visit_date'] ?? '—') . '</h4>';
        $output .= '</div>';
        $output .= '<div style="background: #ebf8ff !important; color: #2b6cb0 !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 14px !important; font-weight: 600 !important;">' . ($doc ? esc_html($doc->display_name) : '—') . '</div>';
        $output .= '</div>';
        $output .= '<div style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important; gap: 20px !important;">';
        $output .= '<div style="flex: 1 !important; min-width: 0 !important;"><h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📄</span> شرح حال بیمار</h5>';
        $output .= '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important; word-break: break-word !important; overflow-wrap: break-word !important;">' . (empty($visit['complaint']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['complaint'])) . '</p></div>';
        $output .= '<div style="flex: 1 !important; min-width: 0 !important;"><h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>⚕️</span> تشخیص پزشک</h5>';
        $output .= '<p style="margin: 0 !important; color: #2d3748 !important; line-height: 1.6 !important; word-break: break-word !important; overflow-wrap: break-word !important;">' . (empty($visit['diagnosis']) ? '<span style="color: #a0aec0 !important;">—</span>' : esc_html($visit['diagnosis'])) . '</p></div>';
        if (!empty($visit['medications']) && is_array($visit['medications'])) {
            $output .= '<div style="flex: 1 !important; min-width: 0 !important;"><h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>💊</span> داروها</h5>';
            $output .= '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
            foreach ($visit['medications'] as $med) { if (!empty($med)) $output .= '<li>' . esc_html($med) . '</li>'; }
            $output .= '</ul></div>';
        }
        if (!empty($visit['files']) && is_array($visit['files'])) {
            $output .= '<div style="flex: 1 !important; min-width: 0 !important;"><h5 style="color: #4a5568 !important; font-size: 14px !important; font-weight: 600 !important; margin-bottom: 8px !important; display: flex !important; align-items: center !important; gap: 6px !important;"><span>📎</span> فایل‌های این ویزیت</h5>';
            $output .= '<ul style="margin: 0 !important; padding-left: 20px !important; color: #2d3748 !important; word-break: break-word !important; overflow-wrap: break-word !important;">';
            foreach ($visit['files'] as $file) { if (!empty($file['title']) && !empty($file['url'])) { $output .= '<li><a href="' . esc_url($file['url']) . '" target="_blank" style="color: #396cf0 !important; text-decoration: underline !important;">' . esc_html($file['title']) . '</a></li>'; } }
            $output .= '</ul></div>';
        }
        
        // ========== نمایش امتیاز یا فرم امتیازدهی ========== 
        $rating = isset($visit['rating']) ? intval($visit['rating']) : 0;
        if ($rating > 0) {
            $stars_display = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $output .= '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; color: #396cf0 !important; font-weight: bold;">';
            $output .= 'امتیاز شما: ' . $stars_display;
            $output .= '</div>';
        } else {
            $output .= '<div class="mr-rating-section" data-visit-id="' . esc_attr($visit['id']) . '" style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0;">';
            $output .= '<strong style="color: #396cf0 !important;">امتیاز به پزشک:</strong><br/>';
            $output .= '<div class="mr-stars" style="margin-top: 8px;">';
            for ($i = 1; $i <= 5; $i++) {
                $output .= '<span class="mr-star" data-value="' . $i . '" style="font-size: 24px; color: #ccc; margin-right: 5px; cursor: pointer !important;">☆</span>';
            }
            $output .= '</div>';
            $output .= '<button type="button" class="mr-submit-rating" style="margin-top: 10px; background: #396cf0 !important; color: white !important; border: none !important; padding: 6px 16px !important; border-radius: 4px !important; display: none; font-size: 14px !important;">ثبت امتیاز</button>';
            $output .= '</div>';
        }
        
        $output .= '</div></div>';
    }
}
    $output .= '</div></div>';
    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // تابع دریافت nonce جدید
        function getRatingNonce() {
            return fetch("' . admin_url('admin-ajax.php') . '?action=mr_get_rating_nonce", {
                method: "GET",
                headers: { "Content-Type": "application/json" }
            }).then(res => res.json());
        }

        // هندل کلیک روی ستاره‌ها
        document.querySelectorAll(".mr-star").forEach(star => {
            star.addEventListener("click", function() {
                const section = this.closest(".mr-rating-section");
                const value = parseInt(this.getAttribute("data-value"));
                const submitBtn = section.querySelector(".mr-submit-rating");
                section.setAttribute("data-rating", value);
                
                const allStars = section.querySelectorAll(".mr-star");
                allStars.forEach((s, i) => {
                    s.textContent = (i + 1 <= value) ? "★" : "☆";
                    s.style.color = (i + 1 <= value) ? "#396cf0" : "#ccc";
                });
                submitBtn.style.display = "inline-block";
            });
        });

        // هندل کلیک روی دکمهٔ ثبت
        document.querySelectorAll(".mr-submit-rating").forEach(button => {
            button.addEventListener("click", async function() {
                const section = this.closest(".mr-rating-section");
                // const visitIndex = section.getAttribute("data-visit-index");
                const rating = section.getAttribute("data-rating");
                const userId = ' . $user_id . ';
				const visitId = section.getAttribute("data-visit-id");
                
                if (!rating) {
                    alert("لطفاً امتیازی را انتخاب کنید.");
                    return;
                }

                // دریافت nonce جدید
                try {
                    const nonceResponse = await getRatingNonce();
                    if (!nonceResponse.success) {
                        throw new Error("خطا در دریافت امنیت");
                    }

                    // ارسال با nonce جدید
                    const response = await fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "action=mr_save_rating&user_id=" + userId + "&visit_id=" + visitId + "&rating=" + rating + "&_wpnonce=" + nonceResponse.data.nonce
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("❌ امتیاز ذخیره نشد.\\nلطفاً دوباره تلاش کنید.");
                    }
                } catch (err) {
                    alert("خطا در ارتباط با سرور.");
                }
            });
        });
    });
    </script>';
    return $output;
}
// ========== فرم ویرایش اطلاعات بیمار ==========
    function mr_patient_edit_profile_form($user_id) {
        $user = get_user_by('ID', $user_id);
        $record = get_user_meta($user_id, 'medical_record_data_v2', true);
        if (!is_array($record)) $record = [];

        $diseases = [
            'diabetes' => 'دیابت',
            'hypertension' => 'فشار خون بالا',
            'asthma' => 'آسم',
            'heart_disease' => 'بیماری قلبی',
            'thyroid' => 'بیماری تیروئید',
            'allergy' => 'حساسیت‌های شدید'
        ];

        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
        $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 25px !important;">';
        $output .= '<h2 style="color: #396cf0 !important; margin-top: 0 !important; margin-bottom: 20px !important;">ویرایش اطلاعات پزشکی</h2>';
        $output .= '<form method="post" enctype="multipart/form-data" style="display: grid !important; grid-template-columns: 1fr !important; gap: 20px !important;">';
        // wp_nonce_field('mr_save_profile_action', 'mr_nonce');
        $output .= '<input type="hidden" name="mr_nonce" value="' . wp_create_nonce('mr_save_profile_action') . '" />';

        // سن
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 8px !important; font-weight: bold !important;">سن (سال)</label>';
        $output .= '<input type="number" name="age" value="' . esc_attr($record['age'] ?? '') . '" min="1" max="120" style="width: 100% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important; font-size: 16px !important;" />';
        $output .= '</div>';

        // گروه خونی
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 8px !important; font-weight: bold !important;">گروه خونی</label>';
        $output .= '<select name="blood_group" style="width: 100% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important; font-size: 16px !important;">';
        $output .= '<option value="">انتخاب کنید...</option>';
        foreach ($blood_groups as $bg) {
            $selected = (isset($record['blood_group']) && $record['blood_group'] === $bg) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($bg) . '" ' . $selected . '>' . esc_html($bg) . '</option>';
        }
        $output .= '</select>';
        $output .= '</div>';

        // سابقهٔ بیماری‌ها
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 8px !important; font-weight: bold !important;">سابقهٔ بیماری‌ها</label>';
        $selected = isset($record['medical_history']) && is_array($record['medical_history']) ? $record['medical_history'] : [];
        foreach ($diseases as $key => $name) {
            $checked = in_array($key, $selected) ? 'checked' : '';
            $output .= '<label style="display: block !important; margin: 6px 0 !important; padding-right: 20px !important; position: relative !important;">';
            $output .= '<input type="checkbox" name="medical_history[]" value="' . esc_attr($key) . '" ' . $checked . ' style="margin-left: 8px !important;" />';
            $output .= '<span>' . esc_html($name) . '</span>';
            $output .= '</label>';
        }
        $output .= '</div>';

        // فایل‌ها
        $output .= '<div>';
        $output .= '<label style="display: block !important; margin-bottom: 8px !important; font-weight: bold !important;">فایل‌های پزشکی</label>';
        $output .= '<div id="patient-files" style="margin-bottom: 10px !important;">';
        $output .= '<div class="file-item" style="display: flex !important; gap: 10px !important; margin-bottom: 10px !important;">';
        $output .= '<input type="text" name="file_titles[]" placeholder="عنوان فایل" style="width: 40% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important;" />';
        $output .= '<input type="file" name="file_uploads[]" accept=".jpg,.jpeg,.png,.pdf" style="width: 55% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important;" />';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<button type="button" onclick="mrAddPatientFile()" style="background: #e0e0e0 !important; border: 1px solid #ccc !important; padding: 8px 16px !important; border-radius: 6px !important;">+ افزودن فایل</button>';
        $output .= '</div>';

        $output .= '<p style="margin-top: 25px !important;">';
        $output .= '<input type="submit" name="mr_save_profile" value="ذخیره اطلاعات" style="background: #396cf0 !important; color: white !important; border: none !important; padding: 12px 24px !important; border-radius: 8px !important; font-weight: bold !important; font-size: 16px !important;" />';
        $output .= ' <a href="' . esc_url(remove_query_arg('action')) . '" style="background: #f0f0f0 !important; color: #333 !important; border: 1px solid #ddd !important; padding: 12px 24px !important; border-radius: 8px !important; text-decoration: none !important;">لغو</a>';
        $output .= '</p>';
        $output .= '</form>';

        $output .= '<script>
        function mrAddPatientFile() {
            const container = document.getElementById("patient-files");
            const div = document.createElement("div");
            div.className = "file-item";
            div.style.cssText = "display: flex !important; gap: 10px !important; margin-bottom: 10px !important;";
            div.innerHTML = \'<input type="text" name="file_titles[]" placeholder="عنوان فایل" style="width: 40% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important;" />\'+
                            \'<input type="file" name="file_uploads[]" accept=".jpg,.jpeg,.png,.pdf" style="width: 55% !important; padding: 12px !important; border: 1px solid #cbd5e0 !important; border-radius: 8px !important;" />\';
            container.appendChild(div);
        }
        </script>';

        $output .= '</div></div>';
        return $output;
    }

	// ========== ذخیرهٔ اطلاعات بیمار ==========
function mr_patient_save_profile() {
    if (!is_user_logged_in() || !in_array('subscriber', (array) wp_get_current_user()->roles)) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">مجاز نیستید.</div>';
    }

    if (!wp_verify_nonce($_POST['mr_nonce'] ?? '', 'mr_save_profile_action')) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">خطا در اعتبارسنجی.</div>';
    }

    $user_id = get_current_user_id();
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $blood_group = sanitize_text_field($_POST['blood_group'] ?? '');
    $medical_history = array_map('sanitize_text_field', $_POST['medical_history'] ?? []);

    $files = [];
    $file_titles = $_POST['file_titles'] ?? [];
    $file_uploads = $_FILES['file_uploads'] ?? [];

    if (!empty($file_uploads['name']) && is_array($file_uploads['name'])) {
        for ($i = 0; $i < count($file_uploads['name']); $i++) {
            if (!empty($file_uploads['name'][$i]) && !empty($file_titles[$i])) {
                $file_array = [
                    'name' => $file_uploads['name'][$i],
                    'type' => $file_uploads['type'][$i],
                    'tmp_name' => $file_uploads['tmp_name'][$i],
                    'error' => $file_uploads['error'][$i],
                    'size' => $file_uploads['size'][$i]
                ];
                $upload = wp_handle_upload($file_array, ['test_form' => false]);
                if (!isset($upload['error'])) {
                    $files[] = [
                        'title' => sanitize_text_field($file_titles[$i]),
                        'url'   => $upload['url']
                    ];
                }
            }
        }
    }

    // حفظ فایل‌های قبلی
    $old_record = get_user_meta($user_id, 'medical_record_data_v2', true);
    if (is_array($old_record) && !empty($old_record['files'])) {
        $files = array_merge($old_record['files'], $files);
    }

    $new_record = [
        'age' => $age,
        'blood_group' => $blood_group ?: null,
        'medical_history' => $medical_history,
        'files' => $files
    ];

    update_user_meta($user_id, 'medical_record_data_v2', $new_record);

    // ✅ اینجا مشکل بود: قبلی از wp_redirect استفاده می‌کرد که در شورت‌کد کار نمی‌کند
    // حالا با پیام موفقیت + ریدایرکت جاوااسکریپتی اصلاح شد
    return '<div class="mr-notice" style="padding: 15px !important; background: #e6f4ea !important; border: 1px solid #34a853 !important; border-radius: 8px !important; margin: 20px 0 !important; color: #137333 !important;">
        <strong>✅ اطلاعات با موفقیت ذخیره شد.</strong>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = "' . esc_url(remove_query_arg('action')) . '";
        }, 2000);
    </script>';
}