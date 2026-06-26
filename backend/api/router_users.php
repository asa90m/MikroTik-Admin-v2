<?php
require_once __DIR__ . '/../config.php';

/**
 * يجلب قائمة المستخدمين الحقيقية من الراوتر مباشرة (وليس من أرشيف قاعدة البيانات).
 * type=hotspot      → /ip/hotspot/user/print
 * type=usermanager  → /tool/user-manager/user/print (أو /user-manager/user/print في UMv5)
 * يستخدم نفس جلسة تسجيل الدخول الحالية، بدون أي بيانات اعتماد إضافية.
 */
$type = $_GET['type'] ?? 'hotspot';

try {
    $api = getRouterConnection();

    $rows = [];
    $valid = 0;
    $expired = 0;

    if ($type === 'usermanager') {
        $rows = $api->comm('/tool/user-manager/user/print');
        if (empty($rows)) {
            $rows = $api->comm('/user-manager/user/print');
        }
        foreach ($rows as $r) {
            // لا يوجد حقل "منتهي" مباشر في UM القديم؛ نعتبر المستخدم المعطّل (إن وُجد) منتهياً
            if (($r['disabled'] ?? 'false') === 'true') $expired++; else $valid++;
        }
        $out = array_map(function ($r) {
            return [
                'username' => $r['username'] ?? ($r['name'] ?? ''),
                'password' => $r['password'] ?? '',
                'profile'  => $r['actual-profile'] ?? '',
                'customer' => $r['customer'] ?? '',
                'comment'  => $r['comment'] ?? '',
            ];
        }, $rows);
    } else {
        $rows = $api->comm('/ip/hotspot/user/print');
        foreach ($rows as $r) {
            $limitBytes = (int) ($r['limit-bytes-total'] ?? 0);
            $usedBytes  = (int) ($r['bytes-in'] ?? 0) + (int) ($r['bytes-out'] ?? 0);
            $isExpired  = (($r['disabled'] ?? 'false') === 'true') || ($limitBytes > 0 && $usedBytes >= $limitBytes);
            if ($isExpired) $expired++; else $valid++;
        }
        $out = array_map(function ($r) {
            return [
                'username' => $r['name'] ?? '',
                'password' => $r['password'] ?? '',
                'profile'  => $r['profile'] ?? '',
                'limit_uptime' => $r['limit-uptime'] ?? '',
                'limit_bytes'  => $r['limit-bytes-total'] ?? '',
                'comment'      => $r['comment'] ?? '',
            ];
        }, $rows);
    }

    $api->disconnect();

    if (!empty($_GET['search'])) {
        $q = mb_strtolower($_GET['search']);
        $out = array_values(array_filter($out, function ($r) use ($q) {
            return str_contains(mb_strtolower($r['username']), $q);
        }));
    }

    jsonResponse(['users' => $out, 'valid' => $valid, 'expired' => $expired]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
