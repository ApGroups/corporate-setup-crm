<?php
/**
 * Formatting Helper Functions
 * Corporate Setup Limited - Internal CRM
 */

function formatCurrency($amount, $currency = 'NGN') {
    return $currency . ' ' . number_format($amount, 2);
}

function formatNumber($number) {
    return number_format($number);
}

function formatPercentage($value, $decimals = 1) {
    return number_format($value, $decimals) . '%';
}

function truncateText($text, $limit = 100, $end = '...') {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . $end;
}

function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'NA';
}

function getAvatarColor($name) {
    $colors = [
        '#2563eb', '#059669', '#d97706', '#dc2626',
        '#7c3aed', '#0891b2', '#c2410c', '#be185d'
    ];
    $hash = 0;
    for ($i = 0; $i < strlen($name); $i++) {
        $hash = ord($name[$i]) + (($hash << 5) - $hash);
    }
    return $colors[abs($hash) % count($colors)];
}

function generateAvatar($name, $size = 40, $avatarPath = null) {
    if (!empty($avatarPath)) {
        $safePath = sanitize($avatarPath);
        $src = preg_match('#^https?://#i', $safePath)
            ? $safePath
            : ((strpos($safePath, 'uploads/') === 0 ? '' : 'uploads/') . ltrim($safePath, '/'));
        return '<img src="' . $src . '" alt="' . sanitize($name) . '" class="avatar" style="width:' . $size . 'px;height:' . $size . 'px;object-fit:cover;border-radius:50%;">';
    }

    $initials = getInitials($name);
    $color = getAvatarColor($name);

    return '<div class="avatar" style="width:' . $size . 'px;height:' . $size . 'px;background-color:' . $color . ';font-size:' . ($size * 0.4) . 'px;">' . $initials . '</div>';
}

function getStatusClass($status) {
    $status = strtolower($status);
    $classes = [
        'active' => 'status-active',
        'inactive' => 'status-inactive',
        'pending' => 'status-pending',
        'completed' => 'status-completed',
        'expired' => 'status-expired',
        'submitted' => 'status-submitted',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        'awaiting_client' => 'status-awaiting',
        'awaiting_regulator' => 'status-awaiting',
        'in_progress' => 'status-progress',
        'overdue' => 'status-overdue',
    ];

    return $classes[$status] ?? 'status-default';
}

function getDaysRemaining($date) {
    if (empty($date)) return null;

    $target = strtotime($date);
    $today = strtotime(date('Y-m-d'));
    $diff = ($target - $today) / 86400;

    return round($diff);
}

function isExpiringSoon($date, $days = 30) {
    $remaining = getDaysRemaining($date);
    return $remaining !== null && $remaining >= 0 && $remaining <= $days;
}

function isOverdue($date) {
    $remaining = getDaysRemaining($date);
    return $remaining !== null && $remaining < 0;
}

function formatDateRange($start, $end) {
    if (empty($start)) return '';

    $start = date('d M Y', strtotime($start));
    if (empty($end)) return $start;

    $end = date('d M Y', strtotime($end));
    return "{$start} - {$end}";
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    return empty($text) ? 'n-a' : $text;
}

function highlightSearch($text, $query) {
    if (empty($query)) return $text;

    return preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark>$1</mark>', $text);
}

function getFileIcon($extension) {
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
    ];

    return $icons[strtolower($extension)] ?? 'fa-file';
}
