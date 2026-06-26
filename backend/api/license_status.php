<?php
require_once __DIR__ . '/../config.php';

/**
 * نظام ترخيص بسيط: أول مرة يفتح فيها أي شخص التطبيق، يُسجَّل تاريخ البداية في
 * ملف على السيرفر (وليس في متصفح المستخدم) — فلا يمكن تصفير العداد بحذف
 * بيانات المتصفح. بعد 10 أيام يصبح التطبيق "منتهي" حتى يدخل كود التفعيل.
 */
function readLicense(): array
{
    if (!file_exists(LICENSE_FILE)) {
        $data = ['first_run' => date('c'), 'activated' => false];
        @mkdir(dirname(LICENSE_FILE), 0775, true);
        file_put_contents(LICENSE_FILE, json_encode($data));
        return $data;
    }
    $data = json_decode(file_get_contents(LICENSE_FILE), true);
    if (!is_array($data) || !isset($data['first_run'])) {
        $data = ['first_run' => date('c'), 'activated' => false];
        file_put_contents(LICENSE_FILE, json_encode($data));
    }
    return $data;
}

$data = readLicense();
$daysUsed = (int) floor((time() - strtotime($data['first_run'])) / 86400);
$daysLeft = max(0, TRIAL_DAYS - $daysUsed);
$activated = !empty($data['activated']);

jsonResponse([
    'activated'       => $activated,
    'trial_days_left' => $daysLeft,
    'expired'         => (!$activated && $daysLeft <= 0),
]);
