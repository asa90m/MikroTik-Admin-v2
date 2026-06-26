<?php
require_once __DIR__ . '/../config.php';

function randomString(int $len, string $charType = 'mixed'): string
{
    $charset = $charType === 'numbers'
        ? '0123456789'
        : 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $charset[random_int(0, strlen($charset) - 1)];
    }
    return $out;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$qty      = max(1, min(500, (int) ($input['qty'] ?? 1)));
$prefix   = preg_replace('/[^A-Za-z0-9\-_]/', '', $input['prefix'] ?? '');
$ulen     = max(4, min(16, (int) ($input['user_len'] ?? 6)));
$plen     = max(4, min(16, (int) ($input['pass_len'] ?? 6)));
$type     = in_array($input['type'] ?? '', ['hotspot', 'usermanager', 'topup'], true) ? $input['type'] : 'hotspot';
$profile  = $input['profile'] ?? 'default';
$customer = $input['customer'] ?? 'admin'; // مطلوب لتفعيل البروفايل في اليوزرمانجر
$price    = (float) ($input['price'] ?? 0);
$charType = ($input['char_type'] ?? 'mixed') === 'numbers' ? 'numbers' : 'mixed';
// حدود الباقة/الصلاحية المحسوبة في الواجهة (مثال: 419430400 لـ 400 ميجا، و 3d00:00:00 لـ 3 أيام)
$limitBytesTotal = trim($input['limit_bytes_total'] ?? '');
$limitUptime     = trim($input['limit_uptime'] ?? '');

try {
    $api = getRouterConnection();
    $created = [];
    $routerErrors = [];

    for ($i = 0; $i < $qty; $i++) {
        $username = $prefix . randomString($ulen, $charType);
        $password = randomString($plen, $charType);

        if ($type === 'hotspot') {
            $params = [
                'name'     => $username,
                'password' => $password,
                'profile'  => $profile,
            ];
            if ($limitBytesTotal !== '') $params['limit-bytes-total'] = $limitBytesTotal;
            if ($limitUptime !== '')     $params['limit-uptime']      = $limitUptime;

            $api->errorStr = '';
            $api->comm('/ip/hotspot/user/add', $params);
            if ($api->errorStr !== '') {
                $routerErrors[] = "{$username}: {$api->errorStr}";
            }
        } elseif ($type === 'usermanager') {
            // الخطوة 1: إنشاء المستخدم
            $api->errorStr = '';
            $api->comm('/tool/user-manager/user/add', [
                'username' => $username,
                'password' => $password,
                'customer' => $customer,
            ]);
            $addErr = $api->errorStr;

            // الخطوة 2: تفعيل البروفايل على المستخدم — هذي الخطوة كانت ناقصة سابقاً،
            // ولهذا كان المستخدم يُنشأ بدون أي باقة/بروفايل فعلي مرتبط به.
            $api->errorStr = '';
            $api->comm('/tool/user-manager/user/create-and-activate-profile', [
                'user'     => $username,
                'customer' => $customer,
                'profile'  => $profile,
            ]);
            $activateErr = $api->errorStr;

            if ($addErr !== '' || $activateErr !== '') {
                $routerErrors[] = "{$username}: " . trim($addErr . ' ' . $activateErr);
            }
        }

        $created[] = ['username' => $username, 'password' => $password];
    }

    $api->disconnect();

    // أرشفة في قاعدة البيانات (اختياري) — إن لم تكن قاعدة البيانات مجهّزة بعد،
    // لا نريد أن يفشل الطلب كاملاً؛ الكروت تكون أُنشئت فعلياً على الراوتر بنجاح
    // بغض النظر عن نجاح الأرشفة.
    $archiveError = null;
    try {
        $db = getDb();
        $stmt = $db->prepare(
            'INSERT INTO cards_archive (username, password, profile_id, card_type, price, is_printed, created_at)
             VALUES (:username, :password, :profile_id, :card_type, :price, 0, NOW())'
        );
        foreach ($created as $c) {
            $stmt->execute([
                'username'   => $c['username'],
                'password'   => $c['password'],
                'profile_id' => $input['profile_id'] ?? null,
                'card_type'  => $type,
                'price'      => $price,
            ]);
        }
    } catch (Throwable $dbEx) {
        $archiveError = 'لم تتم الأرشفة في قاعدة البيانات (الكروت أُنشئت على الراوتر فعلياً): ' . $dbEx->getMessage();
    }

    jsonResponse([
        'created'        => $created,
        'count'          => count($created),
        'router_errors'  => $routerErrors,   // أخطاء حقيقية من الراوتر إن وُجدت، لكل كرت
        'archive_error'  => $archiveError,
    ]);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
