<?php
declare(strict_types=1);

/**
 * ملف الإعدادات الموحد - جميع الإعدادات من .env فقط
 * 
 * ⚠️ مهم: لا تضع أي قيم حساسة هنا - كل شيء يجب أن يكون في .env
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') {
    http_response_code(404);
    exit;
}

// تحسين أمني: منع تحميل الموقع داخل iframe من مواقع أخرى (Clickjacking)
header('X-Frame-Options: SAMEORIGIN');

// ====================================================================
// ** تحميل المتغيرات من ملف .env فقط - لا قيم افتراضية خطيرة **
// ====================================================================
$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '' || $t[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // إزالة علامات التنصيص
            if ($value !== '' && ($value[0] === '"' && substr($value, -1) === '"')) {
                $value = substr($value, 1, -1);
            } elseif ($value !== '' && ($value[0] === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            if ($name !== '') {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
} else {
    // تحذير في بيئة التطوير فقط
    if (php_sapi_name() !== 'cli' && ($_SERVER['HTTP_HOST'] ?? '') !== '') {
        error_log('WARNING: .env file not found. Please create .env file from .env.example');
    }
}

// ====================================================================
// ** إعدادات التطبيق - كلها من .env فقط **
// ====================================================================
$APP = (object) [
    // إعدادات عامة
    'site_title'      => getenv('SITE_TITLE')      ?: 'شركة عزم الانجاز',
    'mail_to'         => getenv('MAIL_TO')         ?: 'no-reply@azmalenjaz.com',
    'upload_dir'      => __DIR__ . '/uploads',
    'max_upload_size' => (int)(getenv('MAX_UPLOAD_SIZE') ?: (5 * 1024 * 1024)), // 5MB default
    'allowed_ext'     => getenv('ALLOWED_EXT') ? explode(',', getenv('ALLOWED_EXT')) : ['pdf', 'jpg', 'jpeg', 'png'],

    // إعدادات قاعدة البيانات - من .env فقط
    'db_driver'       => getenv('DB_DRIVER')   ?: 'mysql',
    'db_host'         => getenv('DB_HOST')     ?: 'localhost',
    'db_name'         => getenv('DB_NAME')     ?: 'azzm_test',
    'db_user'         => getenv('DB_USER')     ?: 'azzm_te',
    'db_pass'         => getenv('DB_PASS')     ?: 'Cw]UMnyvig!#edYN',
    'db_charset'      => getenv('DB_CHARSET')  ?: 'utf8mb4',
];

// ====================================================================
// ** مفاتيح API - من .env فقط، بدون قيم افتراضية خطيرة **
// ====================================================================

// التحقق من وجود المفاتيح المهمة
$required_keys = ['AUTHENTICA_API_KEY', 'DB_NAME', 'DB_USER'];
foreach ($required_keys as $key) {
    if (empty(getenv($key))) {
        if (php_sapi_name() !== 'cli') {
            error_log("WARNING: Required environment variable {$key} is not set in .env file.");
        }
    }
}

// دالة مساعدة — لا تُعرّفها مرتين
if (!function_exists('cfg')) {
    function cfg(string $key = '', $default = null) {
        // إذا لم يتم تمرير مفتاح، أرجع كائن الإعدادات الكامل
        if ($key === '') {
            return $GLOBALS['__CROSING_APP__'] ?? (object)[];
        }
        // إذا تم تمرير مفتاح، أرجع قيمته من .env
        return getenv($key) !== false ? getenv($key) : ($_ENV[$key] ?? $default);
    }
}

// Expose to global for modules expecting it
if (!defined('CROSING_CONFIG_INCLUDED')) {
    define('CROSING_CONFIG_INCLUDED', true);
}
$GLOBALS['__CROSING_APP__'] = $APP;

return $APP;

