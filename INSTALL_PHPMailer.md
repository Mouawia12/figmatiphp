# تثبيت PHPMailer (اختياري)

إذا كنت تريد استخدام PHPMailer لإرسال البريد الإلكتروني عبر SMTP بدلاً من `mail()` الافتراضي:

## الطريقة 1: استخدام Composer (موصى به)

```bash
cd /path/to/crosing
composer install
```

أو إذا لم يكن Composer مثبتاً:

```bash
# Windows (إذا كان Composer مثبتاً)
composer install

# أو حمل composer.phar واشغله
php composer.phar install
```

## الطريقة 2: التحميل اليدوي

إذا لم تستطع استخدام Composer، يمكنك تحميل PHPMailer يدوياً:

1. حمّل PHPMailer من: https://github.com/PHPMailer/PHPMailer/releases
2. ضع المجلد `PHPMailer` في `vendor/phpmailer/phpmailer/`
3. أو استخدم المسار المخصص في `inc/email.php`

## ملاحظة

**لا تقلق إذا لم تكن PHPMailer مثبتاً!** الكود سيستخدم `mail()` الافتراضي تلقائياً كبديل.

