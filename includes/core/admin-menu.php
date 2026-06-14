<?php
// ========== منوهای ادمین ==========
add_action('admin_menu', 'mr_add_admin_menu');
add_action('admin_menu', 'mr_add_record_detail_page');
add_action('admin_menu', 'mr_add_edit_record_page');
add_action('admin_menu', 'mr_add_visit_page');
// حذف منوی تنظیمات نوبت دکترها - سیستم رزرو حذف شد
// add_action('admin_menu', 'mr_add_doctor_schedules_page');

function mr_add_admin_menu() {
    add_menu_page(
        'پرونده‌های پزشکی',
        'پرونده‌های پزشکی',
        'manage_options',
        'medical-records',
        'mr_admin_page',
        'dashicons-clipboard',
        30
    );
}

function mr_add_record_detail_page() {
    add_submenu_page(
        'medical-records',
        'جزئیات پرونده',
        'جزئیات پرونده',
        'manage_options',
        'mr-view-record',
        'mr_view_record_page'
    );
}

function mr_add_edit_record_page() {
    add_submenu_page(
        null,
        'ویرایش پرونده',
        'ویرایش پرونده',
        'manage_options',
        'mr-edit-record',
        'mr_edit_record_page'
    );
}

function mr_add_visit_page() {
    add_submenu_page(
        null,
        'افزودن ویزیت',
        'افزودن ویزیت',
        'manage_options',
        'mr-add-visit',
        'mr_add_visit_page_callback'
    );
}

// تابع mr_add_doctor_schedules_page حذف شد - سیستم رزرو غیرفعال است