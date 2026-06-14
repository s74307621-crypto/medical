<?php
/**
 * کلاس مدیریت نوبت‌های پزشکی
 */

class MR_Booking {
    
    /**
     * دریافت تنظیمات دکتر
     */
    public static function get_doctor_settings($doctor_id) {
        $settings = get_user_meta($doctor_id, 'mr_doctor_settings', true);
        if (!is_array($settings)) {
            $settings = self::get_default_settings();
        }
        return $settings;
    }

    /**
     * تنظیمات پیش‌فرض برای دکتر
     */
    public static function get_default_settings() {
        return [
            'working_hours' => [
                'start' => '09:00',
                'end' => '17:00'
            ],
            'working_days' => ['1', '2', '3', '4', '5'], // شنبه تا چهارشنبه
            'holidays' => [],
            'appointment_types' => [
                'in_person' => [
                    'enabled' => false,
                    'price' => 0
                ],
                'online' => [
                    'enabled' => false,
                    'types' => [
                        'video' => ['enabled' => false, 'durations' => []],
                        'audio' => ['enabled' => false, 'durations' => []],
                        'text' => ['enabled' => false, 'durations' => []],
                        'phone' => ['enabled' => false, 'durations' => []]
                    ]
                ]
            ]
        ];
    }

    /**
     * ذخیره تنظیمات دکتر
     */
    public static function save_doctor_settings($doctor_id, $settings) {
        return update_user_meta($doctor_id, 'mr_doctor_settings', $settings);
    }

    /**
     * دریافت نوبت‌های رزرو شده دکتر برای یک روز
     */
    public static function get_doctor_appointments($doctor_id, $date) {
        $args = [
            'post_type' => 'mr_appointment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'appointment_doctor_id',
                    'value' => $doctor_id,
                    'compare' => '='
                ],
                [
                    'key' => 'appointment_date',
                    'value' => $date,
                    'compare' => '='
                ]
            ]
        ];
        return get_posts($args);
    }

    /**
     * ایجاد نوبت جدید
     */
    public static function create_appointment($data) {
        $appointment = [
            'patient_id' => $data['patient_id'] ?? 0,
            'doctor_id' => $data['doctor_id'] ?? 0,
            'appointment_date' => $data['appointment_date'] ?? '',
            'appointment_time' => $data['appointment_time'] ?? '',
            'appointment_type' => $data['appointment_type'] ?? '', // in_person, video, audio, text, phone
            'appointment_duration' => $data['appointment_duration'] ?? 0,
            'price' => $data['price'] ?? 0,
            'discount_code' => $data['discount_code'] ?? '',
            'patient_phone' => $data['patient_phone'] ?? '',
            'patient_dob' => $data['patient_dob'] ?? '',
            'status' => 'pending', // pending, confirmed, completed, cancelled
            'created_at' => current_time('mysql')
        ];
        
        return $appointment;
    }

    /**
     * ذخیره نوبت در user meta
     */
    public static function save_appointment($patient_id, $appointment) {
        $appointments = get_user_meta($patient_id, 'mr_appointments', true);
        if (!is_array($appointments)) {
            $appointments = [];
        }
        
        $appointment['id'] = uniqid('appt_', true);
        $appointments[] = $appointment;
        
        return update_user_meta($patient_id, 'mr_appointments', $appointments);
    }

    /**
     * دریافت ساعات موجود برای دکتر در یک روز
     */
    public static function get_available_times($doctor_id, $date, $duration = 30) {
        $settings = self::get_doctor_settings($doctor_id);
        $appointments = self::get_doctor_appointments($doctor_id, $date);
        
        $start_time = $settings['working_hours']['start'];
        $end_time = $settings['working_hours']['end'];
        
        $available_times = [];
        $current = strtotime($start_time);
        $end = strtotime($end_time);
        
        // ساعات درخواست شده
        $booked_times = [];
        foreach ($appointments as $appt) {
            $appt_time = get_post_meta($appt->ID, 'appointment_time', true);
            $appt_duration = get_post_meta($appt->ID, 'appointment_duration', true);
            $booked_times[] = [
                'start' => $appt_time,
                'duration' => $appt_duration
            ];
        }
        
        while ($current < $end) {
            $time_str = date('H:i', $current);
            $is_available = true;
            
            // چک کردن تداخل با نوبت‌های دیگر
            foreach ($booked_times as $booked) {
                if ($time_str === $booked['start']) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $available_times[] = $time_str;
            }
            
            $current += ($duration * 60);
        }
        
        return $available_times;
    }

    /**
     * تطبیق کد تخفیف
     */
    public static function validate_discount_code($code) {
        // فعلا نمی‌تونیم کد تخفیف رو تطبیق کنیم
        // بعدا این قابلیت اضافه شه
        return [
            'valid' => false,
            'discount_percent' => 0
        ];
    }

    /**
     * محاسبه قیمت نهایی
     */
    public static function calculate_final_price($base_price, $discount_code = '') {
        $final_price = $base_price;
        
        if (!empty($discount_code)) {
            $discount = self::validate_discount_code($discount_code);
            if ($discount['valid']) {
                $final_price = $base_price * (1 - ($discount['discount_percent'] / 100));
            }
        }
        
        return $final_price;
    }

    /**
     * دریافت لیست دکترانی که در یک روز موجودند
     */
    public static function get_available_doctors($date) {
        $args = [
            'role' => 'editor',
            'number' => -1
        ];
        
        $doctors = get_users($args);
        $available_doctors = [];
        
        foreach ($doctors as $doctor) {
            $settings = self::get_doctor_settings($doctor->ID);
            
            // چک کردن روز کاری
            $day_of_week = (int)date('N', strtotime($date));
            if (!in_array((string)$day_of_week, $settings['working_days'])) {
                continue;
            }
            
            // چک کردن تاریخ تعطیلی
            if (in_array($date, $settings['holidays'])) {
                continue;
            }
            
            // چک کردن بخش‌های فعال
            if (($settings['appointment_types']['in_person']['enabled'] ?? false) || 
                ($settings['appointment_types']['online']['enabled'] ?? false)) {
                $available_doctors[] = $doctor;
            }
        }
        
        return $available_doctors;
    }
}
