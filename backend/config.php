<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * إعدادات عامة للباك إند.
 * عدّل قيم قاعدة البيانات أدناه لتطابق بيئة الاستضافة الخاصة بك.
 * ملاحظة: التطبيق يعمل الآن بدون قاعدة بيانات أيضاً — تسجيل الدخول (مثل Winbox)
 * يُبقي بيانات الراوتر في جلسة PHP. قاعدة البيانات تبقى مفيدة فقط لأرشفة الكروت.
 * إن لم تكن قد جهّزت قاعدة بيانات بعد، اترك القيم كما هي؛ شاشات الكروت ستُظهر
 * خطأ عند الحفظ فقط، بينما لوحة المراقبة وPing والترافيك تعمل بدون أي قاعدة بيانات.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'connect_visual');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password');

/**
 * كود التفعيل بعد انتهاء الفترة التجريبية (10 أيام). غيّره لأي كود تريده،
 * ثم أعطه للعميل عند الدفع/التفعيل. يمكنك توليد كود مختلف لكل عميل لاحقاً
 * إن احتجت ذلك (هذا الإصدار يدعم كوداً واحداً ثابتاً للتبسيط).
 */
define('ACTIVATION_CODE', 'MTA-2026-7724');
define('TRIAL_DAYS', 10);
define('LICENSE_FILE', __DIR__ . '/data/license.json');

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

/**
 * يفتح اتصالاً جاهزاً بالراوتر.
 * الأولوية لبيانات جلسة تسجيل الدخول (مثل Winbox) التي حُفظت في connect.php.
 * إن لم يكن هناك تسجيل دخول حالي، يحاول استخدام سجل محفوظ في جدول routers_config
 * (مفيد إن كنت تريد ربط راوتر افتراضي ثابت بدون تسجيل دخول يدوي كل مرة).
 */
function getRouterConnection(): RouterosAPI
{
    require_once __DIR__ . '/classes/RouterosAPI.php';

    if (!empty($_SESSION['router'])) {
        $cfg = $_SESSION['router'];
    } else {
        $row = getDb()->query('SELECT * FROM routers_config ORDER BY id ASC LIMIT 1')->fetch();
        if (!$row) {
            throw new RuntimeException('غير متصل بأي راوتر — سجّل الدخول أولاً من شاشة الاتصال');
        }
        $cfg = [
            'host' => $row['api_host'],
            'port' => (int) $row['api_port'],
            'user' => $row['api_user'],
            'pass' => decryptPassword($row['api_pass']),
        ];
    }

    $api = new RouterosAPI();
    $ok = $api->connect($cfg['host'], $cfg['user'], $cfg['pass'], (int) $cfg['port']);

    if (!$ok) {
        throw new RuntimeException($api->errorStr);
    }

    return $api;
}

/**
 * نقطة تشفير/فك تشفير كلمة مرور الميكروتيك المخزّنة في قاعدة البيانات.
 * استبدل هذا بـ openssl_decrypt مع مفتاح سري حقيقي قبل النشر على الإنتاج.
 */
function decryptPassword(string $stored): string
{
    return $stored;
}

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
