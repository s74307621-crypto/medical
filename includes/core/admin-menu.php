<?php
/**
 * Admin menu registration for Medical Records plugin
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'mr_register_admin_menu');
function mr_register_admin_menu() {
    // Main menu - Patient list (Admin only)
    add_menu_page(
        __('Medical Records', 'medilink'),
        __('Medical Records', 'medilink'),
        'manage_options',
        'medical-records',
        'mr_admin_page',
        'dashicons-admin-users',
        30
    );
    
    // Submenu - View Record (hidden from menu, accessed via URL)
    add_submenu_page(
        '', // Empty parent slug = hidden from menu
        __('View Record', 'medilink'),
        __('View Record', 'medilink'),
        'manage_options',
        'medical-records-view',
        'mr_view_record_page'
    );
}
