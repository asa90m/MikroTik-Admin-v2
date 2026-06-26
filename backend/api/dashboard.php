<?php
require_once __DIR__ . '/../config.php';

/**
 * يجلب كل أرقام لوحة التحكم من الراوتر فعلياً في طلب واحد.
 * كل القيم هنا حقيقية من الراوتر — لا توجد قيم وهمية/ثابتة.
 */
try {
    $api = getRouterConnection();

    $resource = $api->comm('/system/resource/print')[0] ?? [];
    $identity = $api->comm('/system/identity/print')[0] ?? [];
    $board    = $api->comm('/system/routerboard/print')[0] ?? [];

    // ---- هوتسبوت ----
    $activeSessions = $api->comm('/ip/hotspot/active/print');
    $hotspotUsers   = $api->comm('/ip/hotspot/user/print');

    $cookieCount = 0;
    foreach ($activeSessions as $s) {
        if (($s['login-by'] ?? '') === 'cookie') $cookieCount++;
    }

    $expiredByQuota = 0;
    $expiredByValidity = 0;
    foreach ($hotspotUsers as $u) {
        $limitBytes = (int) ($u['limit-bytes-total'] ?? 0);
        $usedBytes  = (int) ($u['bytes-in'] ?? 0) + (int) ($u['bytes-out'] ?? 0);
        if ($limitBytes > 0 && $usedBytes >= $limitBytes) $expiredByQuota++;
        if (($u['disabled'] ?? 'false') === 'true') $expiredByValidity++;
    }

    // ---- شبكة عامة ----
    $arpCount = count($api->comm('/ip/arp/print'));

    $dhcpLeases = [];
    try { $dhcpLeases = $api->comm('/ip/dhcp-server/lease/print'); } catch (Throwable $e) {}
    $dhcpCount = count($dhcpLeases);

    $interfaces = $api->comm('/interface/print');
    $ifaceTotal = count($interfaces);
    $ifaceRunning = 0;
    foreach ($interfaces as $i) {
        if (($i['running'] ?? 'false') === 'true') $ifaceRunning++;
    }

    // ---- يوزر مانجر (نفس جلسة تسجيل الدخول، بدون أي بيانات إضافية) ----
    // ملاحظة: في RouterOS v6 المسار /tool/user-manager/...، وفي يوزر مانجر 5 (RouterOS v7)
    // قد يكون المسار /user-manager/... بدون "tool". نجرّب القديم أولاً.
    $umUsers    = $api->comm('/tool/user-manager/user/print');
    $umSessions = $api->comm('/tool/user-manager/session/print');
    if (empty($umUsers) && empty($umSessions)) {
        // جرّب مسار يوزر مانجر 5 (RouterOS 7+) كبديل
        $umUsers    = $api->comm('/user-manager/user/print');
        $umSessions = $api->comm('/user-manager/session/print');
    }

    $api->disconnect();

    jsonResponse([
        'identity'        => $identity['name'] ?? '',
        'version'         => $resource['version'] ?? '',
        'board'           => $board['model'] ?? '',
        'uptime'          => $resource['uptime'] ?? '',
        'cpu_load'        => $resource['cpu-load'] ?? null,
        'free_memory'     => $resource['free-memory'] ?? null,
        'total_memory'    => $resource['total-memory'] ?? null,
        'free_hdd'        => $resource['free-hdd-space'] ?? null,
        'total_hdd'       => $resource['total-hdd-space'] ?? null,
        'active_cards'    => count($activeSessions),
        'total_cards'     => count($hotspotUsers),
        'cookie_count'    => $cookieCount,
        'expired_quota'   => $expiredByQuota,
        'expired_validity'=> $expiredByValidity,
        'arp_count'       => $arpCount,
        'dhcp_count'      => $dhcpCount,
        'iface_running'   => $ifaceRunning,
        'iface_total'     => $ifaceTotal,
        'um_users'        => count($umUsers),
        'um_sessions'     => count($umSessions),
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
