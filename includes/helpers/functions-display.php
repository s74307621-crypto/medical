<?php
function mr_get_medical_info($user_id) {
    $record = get_user_meta($user_id, 'medical_record_data_v2', true);
    $diseases = [
        'diabetes' => 'دیابت',
        'hypertension' => 'فشار خون بالا',
        'asthma' => 'آسم',
        'heart_disease' => 'بیماری قلبی',
        'thyroid' => 'بیماری تیروئید',
        'allergy' => 'حساسیت‌های شدید'
    ];

    $html = '<div style="background: #f8fafc !important; border-left: 4px solid #396cf0 !important; padding: 20px !important; border-radius: 0 8px 8px 0 !important; margin: 20px 0 !important;">';
    $html .= '<h3 style="margin-top: 0 !important; color: #333 !important;">اطلاعات پزشکی</h3>';
    
    $user = get_user_by('ID', $user_id);
    $html .= '<p><strong>نام بیمار:</strong> ' . esc_html($user->display_name) . '</p>';
    $html .= '<p><strong>سن:</strong> ' . (isset($record['age']) ? esc_html($record['age']) : '—') . '</p>';
    $html .= '<p><strong>گروه خونی:</strong> ' . (isset($record['blood_group']) ? esc_html($record['blood_group']) : '—') . '</p>';

    $selected_diseases = isset($record['medical_history']) ? $record['medical_history'] : [];
    if (!empty($selected_diseases)) {
        $names = [];
        foreach ($selected_diseases as $d) {
            if (isset($diseases[$d])) $names[] = $diseases[$d];
        }
        $html .= '<p><strong>سابقهٔ بیماری‌ها:</strong> ' . implode('، ', $names) . '</p>';
    } else {
        $html .= '<p><strong>سابقهٔ بیماری‌ها:</strong> —</p>';
    }

    // فایل‌ها
    if (!empty($record['files'])) {
        $html .= '<p><strong>فایل‌های پزشکی:</strong><br/>';
        foreach ($record['files'] as $file) {
            $html .= '• <a href="' . esc_url($file['url']) . '" target="_blank" style="color: #396cf0 !important; text-decoration: underline !important;">' . esc_html($file['title']) . '</a><br/>';
        }
        $html .= '</p>';
    }

    $html .= '</div>';
    return $html;
}