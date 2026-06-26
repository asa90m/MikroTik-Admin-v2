<?php
require_once __DIR__ . '/../config.php';

/**
 * يجلب القوائم الحقيقية التي تحتاجها نماذج "إنشاء كروت" من الراوتر:
 * - hotspot_profiles : بروفايلات الهوتسبوت (/ip/hotspot/user/profile/print)
 * - um_profiles       : بروفايلات اليوزرمانجر (/tool/user-manager/profile/print)
 * - um_customers      : عملاء اليوزرمانجر (/tool/user-manager/customer/print)
 * هذا ما كان مفعّلاً سابقاً، ولهذا كانت قوائم البروفايل في الواجهة وهمية (default فقط).
 */
try {
    $api = getRouterConnection();

    $hotspotProfiles = $api->comm('/ip/hotspot/user/profile/print');

    $umProfiles = $api->comm('/tool/user-manager/profile/print');
    if (empty($umProfiles)) {
        $umProfiles = $api->comm('/user-manager/profile/print');
    }

    $umCustomers = $api->comm('/tool/user-manager/customer/print');
    if (empty($umCustomers)) {
        $umCustomers = $api->comm('/user-manager/customer/print');
    }

    // تشخيص حقيقي لسبب عدم ظهور بيانات اليوزرمانجر (بدل التخمين)
    $packages = $api->comm('/system/package/print');
    $umPackage = null;
    foreach ($packages as $p) {
        if (stripos($p['name'] ?? '', 'user-manager') !== false) { $umPackage = $p; break; }
    }
    if (!$umPackage) {
        $umStatus = 'not_installed'; // الحزمة غير مثبّتة على الراوتر أصلاً
    } elseif (($umPackage['disabled'] ?? 'false') === 'true') {
        $umStatus = 'disabled'; // مثبّتة لكن معطّلة
    } elseif (empty($umProfiles) && empty($umCustomers)) {
        $umStatus = 'enabled_but_empty'; // تعمل لكن لا توجد بروفايلات/عملاء معرّفين بعد
    } else {
        $umStatus = 'ok';
    }

    $api->disconnect();

    jsonResponse([
        'hotspot_profiles' => array_values(array_filter(array_map(fn($p) => $p['name'] ?? null, $hotspotProfiles))),
        'um_profiles'       => array_values(array_filter(array_map(fn($p) => $p['name'] ?? null, $umProfiles))),
        'um_customers'      => array_values(array_filter(array_map(fn($c) => $c['login'] ?? ($c['name'] ?? null), $umCustomers))),
        'um_status'         => $umStatus,
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
