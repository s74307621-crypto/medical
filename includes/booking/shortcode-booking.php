<?php
/**
 * شورتکد نوبت‌گیری برای بیماران
 */

add_shortcode('medical_booking', 'mr_booking_shortcode');

function mr_booking_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">لطفاً ابتدا وارد شوید.</div>';
    }
    
    $user = wp_get_current_user();
    if (!in_array('subscriber', (array)$user->roles)) {
        return '<div class="mr-notice" style="padding: 15px !important; background: #fff8e6 !important; border: 1px solid #ffebcc !important; border-radius: 8px !important; margin: 20px 0 !important; color: #996800 !important;">این بخش فقط برای بیماران است.</div>';
    }
    
    // پردازش فرم‌ها
    if (isset($_POST['mr_booking_step'])) {
        return mr_booking_process_form();
    }
    
    // مرحلهٔ جاری
    $current_step = isset($_GET['booking_step']) ? intval($_GET['booking_step']) : 1;
    
    switch ($current_step) {
        case 2:
            return mr_booking_step_2();
        case 3:
            return mr_booking_step_3();
        case 4:
            return mr_booking_step_4();
        case 5:
            return mr_booking_step_5();
        default:
            return mr_booking_step_1();
    }
}

/**
 * مرحله ۱: انتخاب دکتر و نوع ملاقات
 */
function mr_booking_step_1() {
    $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 30px !important;">';
    $output .= '<h2 style="color: #396cf0 !important; margin-bottom: 10px !important;">رزرو نوبت</h2>';
    $output .= '<p style="color: #666 !important; margin-bottom: 30px !important;">مرحله ۱ از ۵: انتخاب دکتر و نوع ملاقات</p>';
    
    // دکترها
    $doctors = get_users(['role' => 'editor', 'number' => -1]);
    
    $output .= '<form method="POST" id="booking-step-1">';
    $output .= wp_nonce_field('mr_booking_nonce', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="mr_booking_step" value="1" />';
    
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">انتخاب دکتر *</label>';
    $output .= '<select name="doctor_id" id="doctor_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required onchange="mr_get_doctor_services(this.value)">';
    $output .= '<option value="">-- انتخاب کنید --</option>';
    
    foreach ($doctors as $doctor) {
        $settings = MR_Booking::get_doctor_settings($doctor->ID);
        $output .= '<option value="' . esc_attr($doctor->ID) . '">' . esc_html($doctor->display_name) . '</option>';
    }
    
    $output .= '</select>';
    $output .= '</div>';
    
    // نوع ملاقات
    $output .= '<div id="appointment-types-container" style="display: none; margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">نوع ملاقات *</label>';
    
    // حضوری
    $output .= '<div style="margin-bottom: 15px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; cursor: pointer;" onclick="mr_select_appointment_type(\'in_person\')">';
    $output .= '<input type="radio" name="appointment_type" value="in_person" id="type_in_person" style="cursor: pointer;" />';
    $output .= '<label for="type_in_person" style="cursor: pointer; margin-left: 10px;"><strong>حضوری</strong>';
    $output .= '<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">ملاقات در مطب‌دکتر</p>';
    $output .= '<p style="margin: 5px 0 0 0; color: #999; font-size: 13px;">قیمت: <strong id="price_in_person">0</strong> تومان</p>';
    $output .= '</label></div>';
    
    // انلاین
    $output .= '<div style="margin-bottom: 15px;">';
    $output .= '<div style="padding: 15px; border: 1px solid #e0e0e0; border-radius: 8px; cursor: pointer;" onclick="mr_toggle_online_types()">';
    $output .= '<input type="radio" name="appointment_type" value="online" id="type_online" style="cursor: pointer;" />';
    $output .= '<label for="type_online" style="cursor: pointer; margin-left: 10px;"><strong>انلاین</strong>';
    $output .= '<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">ملاقات از طریق اینترنت</p>';
    $output .= '</label></div>';
    
    // زیرگزینه‌های انلاین
    $output .= '<div id="online-types-container" style="display: none; margin-top: 15px; margin-left: 20px; padding-left: 20px; border-left: 3px solid #396cf0;">';
    
    // تصویری
    $output .= '<div style="margin-bottom: 10px; cursor: pointer;" onclick="mr_select_online_type(\'video\', this)">';
    $output .= '<input type="radio" name="online_type" value="video" class="online-type-radio" />';
    $output .= '<label style="margin-left: 10px; cursor: pointer;"><strong>تصویری</strong>';
    $output .= '<select name="video_duration" class="duration-select" style="margin-right: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; display: none;" onchange="mr_update_online_price()">';
    $output .= '<option value="10">10 دقیقه</option>';
    $output .= '<option value="20">20 دقیقه</option>';
    $output .= '<option value="30">30 دقیقه</option>';
    $output .= '<option value="40">40 دقیقه</option>';
    $output .= '</select>';
    $output .= '<span class="online-price" style="color: #999;"></span>';
    $output .= '</label></div>';
    
    // صوتی
    $output .= '<div style="margin-bottom: 10px; cursor: pointer;" onclick="mr_select_online_type(\'audio\', this)">';
    $output .= '<input type="radio" name="audio_type" value="audio" class="online-type-radio" />';
    $output .= '<label style="margin-left: 10px; cursor: pointer;"><strong>صوتی</strong>';
    $output .= '<select name="audio_duration" class="duration-select" style="margin-right: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; display: none;" onchange="mr_update_online_price()">';
    $output .= '<option value="10">10 دقیقه</option>';
    $output .= '<option value="20">20 دقیقه</option>';
    $output .= '<option value="30">30 دقیقه</option>';
    $output .= '<option value="40">40 دقیقه</option>';
    $output .= '</select>';
    $output .= '<span class="online-price" style="color: #999;"></span>';
    $output .= '</label></div>';
    
    // متنی
    $output .= '<div style="margin-bottom: 10px; cursor: pointer;" onclick="mr_select_online_type(\'text\', this)">';
    $output .= '<input type="radio" name="text_type" value="text" class="online-type-radio" />';
    $output .= '<label style="margin-left: 10px; cursor: pointer;"><strong>متنی</strong>';
    $output .= '<select name="text_duration" class="duration-select" style="margin-right: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; display: none;" onchange="mr_update_online_price()">';
    $output .= '<option value="10">10 دقیقه</option>';
    $output .= '<option value="20">20 دقیقه</option>';
    $output .= '<option value="30">30 دقیقه</option>';
    $output .= '<option value="40">40 دقیقه</option>';
    $output .= '</select>';
    $output .= '<span class="online-price" style="color: #999;"></span>';
    $output .= '</label></div>';
    
    // تلفنی
    $output .= '<div style="margin-bottom: 10px; cursor: pointer;" onclick="mr_select_online_type(\'phone\', this)">';
    $output .= '<input type="radio" name="phone_type" value="phone" class="online-type-radio" />';
    $output .= '<label style="margin-left: 10px; cursor: pointer;"><strong>تلفنی</strong>';
    $output .= '<select name="phone_duration" class="duration-select" style="margin-right: 10px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; display: none;" onchange="mr_update_online_price()">';
    $output .= '<option value="10">10 دقیقه</option>';
    $output .= '<option value="20">20 دقیقه</option>';
    $output .= '<option value="30">30 دقیقه</option>';
    $output .= '<option value="40">40 دقیقه</option>';
    $output .= '</select>';
    $output .= '<span class="online-price" style="color: #999;"></span>';
    $output .= '</label></div>';
    
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // خلاصهٔ قیمت
    $output .= '<div id="price-summary" style="background: #f0f7ff; padding: 15px; border-radius: 8px; margin-top: 20px; margin-bottom: 20px; display: none;">';
    $output .= '<p style="margin: 0; color: #333;"><strong>قیمت انتخاب شده:</strong> <span id="final-price">0</span> تومان</p>';
    $output .= '</div>';
    
    // دکمه‌ها
    $output .= '<div style="display: flex; gap: 10px; margin-top: 30px;">';
    $output .= '<button type="submit" style="background: #396cf0; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1;">ادامه</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * JavaScript مرحله ۱
 */
// Inline JS moved to assets/js/booking.js

/**
 * مرحله ۲: انتخاب تاریخ و ساعت
 */
function mr_booking_step_2() {
    $doctor_id = isset($_SESSION['booking_doctor_id']) ? intval($_SESSION['booking_doctor_id']) : 0;
    
    if ($doctor_id <= 0) {
        return '<div class="mr-notice">خطا: اطلاعات درخواست معتبر نیست</div>';
    }
    
    $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 30px !important;">';
    $output .= '<h2 style="color: #396cf0 !important; margin-bottom: 10px !important;">رزرو نوبت</h2>';
    $output .= '<p style="color: #666 !important; margin-bottom: 30px !important;">مرحله ۲ از ۵: انتخاب تاریخ و ساعت</p>';
    
    $output .= '<form method="POST">';
    $output .= wp_nonce_field('mr_booking_nonce', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="mr_booking_step" value="2" />';
    $output .= '<input type="hidden" name="doctor_id" value="' . esc_attr($doctor_id) . '" />';
    
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">تاریخ *</label>';
    $output .= '<input type="text" name="appointment_date" class="mr-date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" required />';
    $output .= '</div>';
    
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">ساعت *</label>';
    $output .= '<select name="appointment_time" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" required id="appointment_time">';
    $output .= '<option value="">-- لطفاً تاریخ را انتخاب کنید --</option>';
    $output .= '</select>';
    $output .= '</div>';
    
    $output .= '<div style="display: flex; gap: 10px; margin-top: 30px;">';
    $output .= '<a href="' . esc_url(add_query_arg('booking_step', '1')) . '" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">بازگشت</a>';
    $output .= '<button type="submit" style="background: #396cf0; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1;">ادامه</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * مرحله ۳: شماره موبایل و اطلاعات فردی
 */
function mr_booking_step_3() {
    $user = wp_get_current_user();
    
    $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 30px !important;">';
    $output .= '<h2 style="color: #396cf0 !important; margin-bottom: 10px !important;">رزرو نوبت</h2>';
    $output .= '<p style="color: #666 !important; margin-bottom: 30px !important;">مرحله ۳ از ۵: اطلاعات تماس و فردی</p>';
    
    $output .= '<form method="POST">';
    $output .= wp_nonce_field('mr_booking_nonce', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="mr_booking_step" value="3" />';
    
    // شماره موبایل
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">شماره موبایل *</label>';
    $output .= '<input type="tel" name="patient_phone" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; direction: ltr;" placeholder="0912xxxxxxx" required />';
    $output .= '</div>';
    
    // نام
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">نام *</label>';
    $output .= '<input type="text" name="patient_first_name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" value="' . esc_attr($user->first_name) . '" required />';
    $output .= '</div>';
    
    // نام خانوادگی
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">نام خانوادگی *</label>';
    $output .= '<input type="text" name="patient_last_name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" value="' . esc_attr($user->last_name) . '" required />';
    $output .= '</div>';
    
    // تاریخ تولد
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">تاریخ تولد *</label>';
    $output .= '<input type="text" name="patient_dob" class="mr-date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" required />';
    $output .= '</div>';
    
    $output .= '<div style="display: flex; gap: 10px; margin-top: 30px;">';
    $output .= '<a href="' . esc_url(add_query_arg('booking_step', '2')) . '" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">بازگشت</a>';
    $output .= '<button type="submit" style="background: #396cf0; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1;">ادامه</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * مرحله ۴: خلاصهٔ و کد تخفیف
 */
function mr_booking_step_4() {
    $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 30px !important;">';
    $output .= '<h2 style="color: #396cf0 !important; margin-bottom: 10px !important;">رزرو نوبت</h2>';
    $output .= '<p style="color: #666 !important; margin-bottom: 30px !important;">مرحله ۴ از ۵: بررسی و پرداخت</p>';
    
    $output .= '<form method="POST">';
    $output .= wp_nonce_field('mr_booking_nonce', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="mr_booking_step" value="4" />';
    
    // خلاصهٔ اطلاعات
    $output .= '<div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
    $output .= '<h3 style="color: #333; margin-top: 0;">خلاصهٔ نوبت</h3>';
    $output .= '<p><strong>دکتر:</strong> <span id="summary-doctor">—</span></p>';
    $output .= '<p><strong>نوع ملاقات:</strong> <span id="summary-type">—</span></p>';
    $output .= '<p><strong>تاریخ و ساعت:</strong> <span id="summary-datetime">—</span></p>';
    $output .= '<p><strong>نام بیمار:</strong> <span id="summary-patient">—</span></p>';
    $output .= '<p><strong>شماره موبایل:</strong> <span id="summary-phone">—</span></p>';
    $output .= '</div>';
    
    // کد تخفیف
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<label style="display: block; margin-bottom: 10px; font-weight: bold;">کد تخفیف (اختیاری)</label>';
    $output .= '<input type="text" name="discount_code" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;" placeholder="کد تخفیف را وارد کنید" />';
    $output .= '</div>';
    
    // قیمت نهایی
    $output .= '<div style="background: #e6f2ff; border: 2px solid #396cf0; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
    $output .= '<p style="margin: 0; font-size: 14px; color: #666;">قیمت پایه: <span id="summary-base-price">0</span> تومان</p>';
    $output .= '<p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">تخفیف: <span id="summary-discount">0</span> تومان</p>';
    $output .= '<p style="margin: 10px 0 0 0; font-size: 18px; color: #396cf0;"><strong>مبلغ نهایی: <span id="summary-final-price">0</span> تومان</strong></p>';
    $output .= '</div>';
    
    $output .= '<div style="display: flex; gap: 10px; margin-top: 30px;">';
    $output .= '<a href="' . esc_url(add_query_arg('booking_step', '3')) . '" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">بازگشت</a>';
    $output .= '<button type="submit" style="background: #4caf50; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1;">پرداخت</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * مرحله ۵: تایید پرداخت
 */
function mr_booking_step_5() {
    $output = '<div class="mr-container" style="max-width: 800px !important; margin: 20px auto !important; padding: 0 15px !important;">';
    $output .= '<div class="mr-card" style="background: white !important; border-radius: 12px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; padding: 30px !important; text-align: center !important;">';
    $output .= '<div style="color: #4caf50; font-size: 48px; margin-bottom: 20px;">✓</div>';
    $output .= '<h2 style="color: #4caf50 !important; margin-bottom: 10px !important;">نوبت با موفقیت رزرو شد</h2>';
    $output .= '<p style="color: #666 !important; margin-bottom: 20px !important;">نوبت شما ثبت شده است و در انتظار تایید است.</p>';
    $output .= '<p style="color: #999 !important; margin-bottom: 30px !important;">طلال کوتاهی پس از تایید، آپ بوسیله ایمیل اطلاع‌رسانی خواهید شد.</p>';
    
    $output .= '<a href="' . esc_url(home_url()) . '" style="background: #396cf0; color: white; border: none; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold;">بازگشت به داشبورد</a>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * پردازش فرم رزرو
 */
function mr_booking_process_form() {
    $step = isset($_POST['mr_booking_step']) ? intval($_POST['mr_booking_step']) : 0;
    
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        return '<div class="mr-notice" style="color: red;">خطا در تحقق درخواست</div>';
    }
    
    switch ($step) {
        case 1:
            return mr_process_step_1();
        case 2:
            return mr_process_step_2();
        case 3:
            return mr_process_step_3();
        case 4:
            return mr_process_step_4();
    }
}

function mr_process_step_1() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        return '<div class="mr-notice" style="color: red;">خطا در تحقق درخواست</div>';
    }
    
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_type = sanitize_key($_POST['appointment_type'] ?? '');
    
    if ($doctor_id <= 0 || empty($appointment_type)) {
        return '<div class="mr-notice" style="color: red;">لطفاً تمام فیلد‌ها را پر کنید</div>' . mr_booking_step_1();
    }
    
    $_SESSION['booking_doctor_id'] = $doctor_id;
    $_SESSION['booking_type'] = $appointment_type;

    wp_safe_redirect(add_query_arg('booking_step', '2'));
    exit;
}

function mr_process_step_2() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        return '<div class="mr-notice" style="color: red;">خطا در تحقق درخواست</div>';
    }
    
    $appointment_date = sanitize_text_field($_POST['appointment_date'] ?? '');
    $appointment_time = sanitize_text_field($_POST['appointment_time'] ?? '');
    
    if (strtotime($appointment_date) === false) {
        return '<div class="mr-notice" style="color: red;">تاریخ نامعتبر</div>' . mr_booking_step_2();
    }
    
    if (empty($appointment_date) || empty($appointment_time)) {
        return '<div class="mr-notice" style="color: red;">لطفاً تمام فیلد‌ها را پر کنید</div>' . mr_booking_step_2();
    }
    
    $_SESSION['booking_date'] = $appointment_date;
    $_SESSION['booking_time'] = $appointment_time;

    wp_safe_redirect(add_query_arg('booking_step', '3'));
    exit;
}

function mr_process_step_3() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        return '<div class="mr-notice" style="color: red;">خطا در تحقق درخواست</div>';
    }
    
    $phone = sanitize_text_field($_POST['patient_phone'] ?? '');
    $first_name = sanitize_text_field($_POST['patient_first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['patient_last_name'] ?? '');
    $dob = sanitize_text_field($_POST['patient_dob'] ?? '');
    
    if (strtotime($dob) === false) {
        return '<div class="mr-notice" style="color: red;">تاریخ تولد نامعتبر</div>' . mr_booking_step_3();
    }
    
    if (empty($phone) || empty($first_name) || empty($last_name) || empty($dob)) {
        return '<div class="mr-notice" style="color: red;">لطفاً تمام فیلد‌ها را پر کنید</div>' . mr_booking_step_3();
    }
    
    $_SESSION['booking_phone'] = $phone;
    $_SESSION['booking_first_name'] = $first_name;
    $_SESSION['booking_last_name'] = $last_name;
    $_SESSION['booking_dob'] = $dob;

    wp_safe_redirect(add_query_arg('booking_step', '4'));
    exit;
}

function mr_process_step_4() {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')) {
        return '<div class="mr-notice" style="color: red;">خطا در تحقق درخواست</div>';
    }
    
    $discount_code = sanitize_key($_POST['discount_code'] ?? '');
    $user_id = get_current_user_id();
    
    if ($user_id <= 0) {
        return '<div class="mr-notice" style="color: red;">لطفاً وارد شوید</div>';
    }
    
    // ذخیره اطلاعات نوبت
    $appointment = MR_Booking::create_appointment([
        'patient_id' => $user_id,
        'doctor_id' => $_SESSION['booking_doctor_id'] ?? 0,
        'appointment_date' => $_SESSION['booking_date'] ?? '',
        'appointment_time' => $_SESSION['booking_time'] ?? '',
        'appointment_type' => $_SESSION['booking_type'] ?? '',
        'patient_phone' => $_SESSION['booking_phone'] ?? '',
        'patient_dob' => $_SESSION['booking_dob'] ?? '',
        'discount_code' => $discount_code,
        'price' => $_SESSION['booking_price'] ?? 0
    ]);
    
    MR_Booking::save_appointment($user_id, $appointment);

    // پاک کردن session
    unset($_SESSION['booking_doctor_id']);
    unset($_SESSION['booking_type']);
    unset($_SESSION['booking_date']);
    unset($_SESSION['booking_time']);
    unset($_SESSION['booking_phone']);
    unset($_SESSION['booking_first_name']);
    unset($_SESSION['booking_last_name']);
    unset($_SESSION['booking_dob']);
    unset($_SESSION['booking_price']);

    wp_safe_redirect(add_query_arg('booking_step', '5'));
    exit;
}
