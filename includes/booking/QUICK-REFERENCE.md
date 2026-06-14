# 📚 Quick Reference Guide

## ⚡ شورتکد‌ها

### برای بیماران
```
[medical_booking]
```

### مثال Page Template
```html
<div class="page-content">
    <h1>رزرو نوبت پزشک</h1>
    <p>لطفاً از فرم زیر برای رزرو نوبت استفاده کنید.</p>
    
    [medical_booking]
    
</div>
```

---

## 🔧 توابع PHP

### دریافت تنظیمات دکتر
```php
$settings = MR_Booking::get_doctor_settings($doctor_id);

// خروجی:
[
    'working_hours' => ['start' => '09:00', 'end' => '17:00'],
    'working_days' => ['1', '2', '3', '4', '5'],
    'holidays' => [],
    'appointment_types' => [...]
]
```

### ذخیره تنظیمات
```php
$success = MR_Booking::save_doctor_settings($doctor_id, $settings);
// برگشت: true/false
```

### دریافت ساعات موجود
```php
$times = MR_Booking::get_available_times(
    $doctor_id,      // ID دکتر
    '1403-02-15',    // تاریخ
    30               // مدت (دقیقه)
);

// خروجی: ['09:00', '09:30', '10:00', ...]
```

### دریافت نوبت‌های دکتر
```php
$appointments = MR_Booking::get_doctor_appointments(
    $doctor_id,
    '1403-02-15'
);

// خروجی: Array of WP_Post objects
```

### محاسبهٔ قیمت نهایی
```php
$final_price = MR_Booking::calculate_final_price(
    250000,          // قیمت پایه
    'WELCOME25'      // کد تخفیف (اختیاری)
);

// خروجی: 187500 (با 25% تخفیف)
```

### ایجاد نوبت
```php
$appointment = MR_Booking::create_appointment([
    'patient_id' => 1,
    'doctor_id' => 3,
    'appointment_date' => '1403-02-15',
    'appointment_time' => '10:30',
    'appointment_type' => 'video',
    'appointment_duration' => 30,
    'price' => 250000,
    'discount_code' => '',
    'patient_phone' => '0912xxx',
    'patient_dob' => '1369-01-01'
]);

MR_Booking::save_appointment($patient_id, $appointment);
```

---

## 🎯 نقاط ورود (Hooks)

### Actions

```php
// CSS شامل‌سازی
add_action('wp_enqueue_scripts', 'mr_enqueue_assets');

// AJAX handlers
add_action('wp_ajax_mr_get_doctor_services', 'mr_handle_get_doctor_services');
add_action('wp_ajax_mr_save_doctor_booking_settings', 'mr_handle_save_doctor_booking_settings');
```

### Shortcodes

```php
// نوبت‌گیری برای بیماران
add_shortcode('medical_booking', 'mr_booking_shortcode');

// تنظیمات دکتر (درون داشبورد)
mr_doctor_booking_settings();
```

---

## 📁 فایل‌های کلیدی

| فایل | توضیح |
|------|-------|
| `class-booking.php` | کلاس اصلی و توابع |
| `shortcode-booking.php` | شورتکد نوبت‌گیری |
| `doctor-settings.php` | تنظیمات دکتر |
| `booking.css` | سبک‌ها |

---

## 🗄️ User Meta Keys

```php
// برای دکتر
$settings = get_user_meta($doctor_id, 'mr_doctor_settings', true);

// برای بیمار
$appointments = get_user_meta($patient_id, 'mr_appointments', true);
```

---

## 🔐 Security Checklist

- ✅ `wp_verify_nonce()` - در تمام AJAX
- ✅ `sanitize_text_field()` - برای تمام input
- ✅ `intval()` - برای IDs و اعداد
- ✅ `esc_attr()` / `esc_html()` - در output
- ✅ `esc_url()` - برای URLs
- ✅ نقش‌های کاربر بررسی می‌شود
- ✅ `current_user_can()` - برای دسترسی

---

## 🛠️ Debugging Tips

### Log booking data
```php
error_log(print_r($appointment, true));
error_log(print_r($_SESSION, true));
```

### Check doctor settings
```php
$settings = MR_Booking::get_doctor_settings($doctor_id);
echo '<pre>'; print_r($settings); echo '</pre>';
```

### Check available times
```php
$times = MR_Booking::get_available_times($doctor_id, '1403-02-15', 30);
var_dump($times);
```

### Verify nonce in AJAX
```php
// مطمئن شوید nonce صحیح است
wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mr_booking_nonce')
```

---

## 📦 Session Variables

در مراحل فرم استفاده می‌شود:

```php
$_SESSION['booking_doctor_id']      // ID دکتر
$_SESSION['booking_type']           // نوع ملاقات
$_SESSION['booking_date']           // تاریخ
$_SESSION['booking_time']           // ساعت
$_SESSION['booking_phone']          // تلفن
$_SESSION['booking_first_name']     // نام
$_SESSION['booking_last_name']      // نام خانوادگی
$_SESSION['booking_dob']            // تاریخ تولد
$_SESSION['booking_price']          // قیمت
```

---

## 🎨 CSS Classes

```css
.mr-booking-container      /* کانتینر اصلی */
.mr-booking-card          /* کارت فرم */
.mr-appointment-type-box  /* جعبهٔ نوع ملاقات */
.mr-doctor-option         /* گزینهٔ دکتر */
.mr-price-summary         /* خلاصهٔ قیمت */
.mr-booking-buttons       /* دکمه‌های فرم */
.mr-booking-btn-next      /* دکمهٔ بعدی */
.mr-booking-btn-pay       /* دکمهٔ پرداخت */
```

---

## 🔌 AJAX Endpoints

### دریافت خدمات دکتر
```
POST /wp-admin/admin-ajax.php
action: mr_get_doctor_services
doctor_id: 3
_wpnonce: [nonce]

Response: {
    success: true,
    data: {
        in_person: {...},
        online: {...}
    }
}
```

### ذخیره تنظیمات
```
POST /wp-admin/admin-ajax.php
action: mr_save_doctor_booking_settings
[form fields...]
_wpnonce: [nonce]

Response: {
    success: true,
    data: "Settings saved successfully"
}
```

---

## 🧪 نمونه‌های کد

### دریافت نوبت‌های بیمار
```php
$patient_id = get_current_user_id();
$appointments = get_user_meta($patient_id, 'mr_appointments', true);

if (is_array($appointments)) {
    foreach ($appointments as $appt) {
        echo $appt['appointment_date'] . ' - ' . $appt['appointment_time'];
    }
}
```

### دریافت نوبت‌های دکتر برای روز
```php
$doctor_id = 3;
$date = '1403-02-15';

$appointments = MR_Booking::get_doctor_appointments($doctor_id, $date);

foreach ($appointments as $appt) {
    $patient = get_user_by('ID', $appt['patient_id']);
    echo $patient->display_name;
}
```

### تغییر قیمت خدمت
```php
$doctor_id = 3;
$settings = MR_Booking::get_doctor_settings($doctor_id);

// تغییر قیمت تصویری 30 دقیقه
$settings['appointment_types']['online']['types']['video']['prices']['30'] = 300000;

MR_Booking::save_doctor_settings($doctor_id, $settings);
```

---

## 🚨 رفع مشکلات

### شورتکد کار نمی‌کند
```php
// بررسی:
1. آیا فایل booking شامل‌سازی شده است؟
2. آیا کاربر وارد شده است؟
3. آیا وردپرس شورتکد را پردازش می‌کند؟
```

### تنظیمات دکتر ذخیره نمی‌شود
```php
// بررسی:
1. بررسی browser console برای AJAX errors
2. بررسی server logs
3. نقش کاربر را بررسی کنید (editor/administrator)
4. nonce را بررسی کنید
```

### ساعات موجود نیستند
```php
// بررسی:
1. آیا روز کاری است؟
2. آیا در ساعات کاری است؟
3. آیا نوبت تضاد دارد؟
4. تاریخ را مجدداً انتخاب کنید
```

---

## 📞 Support

برای پشتیبانی یا سؤالات:
- بررسی فایل‌های README و SETUP-GUIDE
- بررسی FLOWCHART برای درک جریان
- بررسی browser console برای errors
- بررسی WordPress debug.log

---

## 📋 Checklist پیاده‌سازی

- [ ] شورتکد `[medical_booking]` در صفحه اضافه شد
- [ ] دکتر/دکترها تنظیمات را کامل کردند
- [ ] ساعات کاری تعیین شد
- [ ] قیمت‌ها تعیین شدند
- [ ] خدمات انتخاب شدند
- [ ] CSS صحیح بارگذاری می‌شود
- [ ] AJAX کار می‌کند
- [ ] نوبت‌ها ذخیره می‌شوند

---

## 🎓 Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Admin AJAX](https://developer.wordpress.org/plugins/javascript/ajax/)
- [WordPress Security](https://developer.wordpress.org/plugins/security/)
- [WordPress User Meta](https://developer.wordpress.org/plugins/users/working-with-user-metadata/)

---

**نسخه:** 1.0  
**آخرین به‌روزرسانی:** ۱۳ فروردین ۱۴۰۵

