<?php
/**
 * Admin role functions for Medical Records plugin
 * Uses Bookly tables for doctors and patients
 * 
 * @package Medical_Records
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main admin page - List of patients from Bookly
 */
function mr_admin_page() {
    global $wpdb;
    
    echo '<div class="wrap mr-container">';
    echo '<h1 class="mr-card-title">' . __('Medical Records Management', 'medical-records') . '</h1>';
    
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
    
    // Display search form
    echo '<form method="get" action="" class="mr-search-form">';
    echo '<input type="hidden" name="page" value="medical-records">';
    echo '<div class="mr-search-row">';
    echo '<input type="text" name="mr_search" class="mr-search-input" placeholder="' . __('Search by name, email or phone...', 'medical-records') . '" value="' . esc_attr($search_query) . '">';
    echo '<button type="submit" class="mr-btn mr-btn-primary">' . __('Search', 'medical-records') . '</button>';
    echo '<a href="' . admin_url('admin.php?page=medical-records') . '" class="mr-btn mr-btn-secondary">' . __('Reset', 'medical-records') . '</a>';
    echo '</div>';
    echo '</form>';
    
    // Display patients table
    echo '<div class="mr-table-wrapper mr-card">';
    echo '<table class="mr-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th><a href="' . $sort_id_url . '">' . __('ID', 'medical-records') . ($order_dir === 'ASC' ? ' ↑' : ' ↓') . '</a></th>';
    echo '<th>' . __('Full Name', 'medical-records') . '</th>';
    echo '<th>' . __('Email', 'medical-records') . '</th>';
    echo '<th>' . __('Phone', 'medical-records') . '</th>';
    echo '<th>' . __('Actions', 'medical-records') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (empty($patients)) {
        echo '<tr><td colspan="5" class="mr-empty-state">' . __('No patients found.', 'medical-records') . '</td></tr>';
    } else {
        foreach ($patients as $patient) {
            $wp_user_id = $patient['wp_user_id'] ?? 0;
            $view_link = admin_url('admin.php?page=medical-records-view&user_id=' . $wp_user_id);
            $delete_link = wp_nonce_url(admin_url('admin-post.php?action=mr_delete_record&user_id=' . $wp_user_id), 'mr_delete_' . $wp_user_id);
            
            echo '<tr>';
            echo '<td>' . esc_html($patient['id']) . '</td>';
            echo '<td><strong>' . esc_html($patient['full_name']) . '</strong></td>';
            echo '<td>' . esc_html($patient['email']) . '</td>';
            echo '<td>' . esc_html($patient['phone']) . '</td>';
            echo '<td class="mr-actions">';
            echo '<a href="' . esc_url($view_link) . '" class="mr-btn mr-btn-sm mr-btn-info">' . __('View Record', 'medical-records') . '</a>';
            echo '<a href="' . esc_url($delete_link) . '" class="mr-btn mr-btn-sm mr-btn-danger" onclick="return confirm(\'' . esc_js(__('Are you sure?', 'medical-records')) . '\')">' . __('Delete', 'medical-records') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
}

/**
 * View record page for admin
 */
function mr_view_record_page() {
    if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
        echo '<div class="wrap"><h1 class="mr-error">' . __('Invalid user.', 'medical-records') . '</h1></div>';
        return;
    }

    $user_id = intval($_GET['user_id']);
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        echo '<div class="wrap"><h1 class="mr-error">' . __('User not found.', 'medical-records') . '</h1></div>';
        return;
    }

    $visits = get_user_meta($user_id, 'medical_visits', true);
    $visits = is_array($visits) ? $visits : [];

    // Calculate last visit date
    $last_visit_date = '—';
    if (!empty($visits)) {
        usort($visits, function($a, $b) {
            return strcmp($b['visit_date'] ?? '', $a['visit_date'] ?? '');
        });
        $last_visit_date = $visits[0]['visit_date'] ?? '—';
    }

    echo '<div class="wrap mr-detail-page">';
    echo '<h1 class="mr-page-title">' . __('Medical Record Details', 'medical-records') . '</h1>';
    
    // Medical info summary
    echo mr_get_medical_info($user_id);

    // Visits list
    echo '<h2 class="mr-section-title">' . __('Visits', 'medical-records') . '</h2>';
    if (empty($visits)) {
        echo '<div class="mr-empty-state mr-card">' . __('No visits registered.', 'medical-records') . '</div>';
    } else {
        foreach (array_reverse($visits) as $visit) {
            $doctor = get_user_by('ID', $visit['doctor_id'] ?? 0);
            echo '<div class="mr-visit-card mr-card">';
            
            // Visit header
            echo '<div class="mr-visit-header">';
            echo '<div class="mr-visit-title">';
            echo '<span class="mr-badge">' . esc_html($visit['visit_date'] ?? '—') . '</span>';
            echo '</div>';
            echo '<div class="mr-doctor-name">' . ($doctor ? esc_html($doctor->display_name) : '—') . '</div>';
            echo '</div>';

            // Visit content grid
            echo '<div class="mr-visit-grid">';

            // Complaint
            echo '<div class="mr-visit-item">';
            echo '<h5 class="mr-visit-label"><span>📄</span> ' . __('Chief Complaint', 'medical-records') . '</h5>';
            echo '<p class="mr-visit-value">' . (empty($visit['complaint']) ? '<span class="mr-empty">—</span>' : esc_html($visit['complaint'])) . '</p>';
            echo '</div>';

            // Diagnosis
            echo '<div class="mr-visit-item">';
            echo '<h5 class="mr-visit-label"><span>⚕️</span> ' . __('Diagnosis', 'medical-records') . '</h5>';
            echo '<p class="mr-visit-value">' . (empty($visit['diagnosis']) ? '<span class="mr-empty">—</span>' : esc_html($visit['diagnosis'])) . '</p>';
            echo '</div>';

            // Medications
            if (!empty($visit['medications']) && is_array($visit['medications'])) {
                echo '<div class="mr-visit-item">';
                echo '<h5 class="mr-visit-label"><span>💊</span> ' . __('Medications', 'medical-records') . '</h5>';
                echo '<ul class="mr-list">';
                foreach ($visit['medications'] as $med) {
                    if (!empty($med)) echo '<li>' . esc_html($med) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }

            // Files
            if (!empty($visit['files']) && is_array($visit['files'])) {
                echo '<div class="mr-visit-item">';
                echo '<h5 class="mr-visit-label"><span>📎</span> ' . __('Files', 'medical-records') . '</h5>';
                echo '<ul class="mr-list">';
                foreach ($visit['files'] as $file) {
                    if (!empty($file['title']) && !empty($file['url'])) {
                        echo '<li><a href="' . esc_url($file['url']) . '" target="_blank" class="mr-link">' . esc_html($file['title']) . '</a></li>';
                    }
                }
                echo '</ul>';
                echo '</div>';
            }

            echo '</div>'; // End grid
            echo '</div>'; // End visit card
        }
    }

    echo '<p class="mr-back-link">';
    echo '<a href="' . admin_url('admin.php?page=medical-records') . '" class="mr-btn mr-btn-secondary">← ' . __('Back to List', 'medical-records') . '</a>';
    echo '</p>';
    echo '</div>';
}

/**
 * Handle delete record action
 */
function mr_handle_delete_record() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You are not allowed.', 'medical-records'));
    }

    check_admin_referer('mr_delete_{user_id}');
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($user_id > 0) {
        delete_user_meta($user_id, 'medical_record_data');
        delete_user_meta($user_id, 'medical_visits');
    }

    wp_redirect(admin_url('admin.php?page=medical-records'));
    exit;
}

/**
 * Initialize admin actions
 */
function mr_admin_init_actions() {
    add_action('admin_post_mr_delete_record', 'mr_handle_delete_record');
}
add_action('admin_init', 'mr_admin_init_actions');
