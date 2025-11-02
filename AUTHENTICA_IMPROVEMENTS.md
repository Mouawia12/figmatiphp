# تحسينات Authentica API - مطابقة للمواصفات

تم تحسين تكامل Authentica API ليكون مطابقاً تماماً لمواصفات `authenticasa.apib`.

## التحسينات المنفذة

### 1. **دالة `authentica_send_otp` محسّنة**
- ✅ دعم `method`: `sms`, `whatsapp`, `email`
- ✅ دعم `template_id` (اختياري)
- ✅ دعم `fallback_phone` و `fallback_email`
- ✅ دعم `custom_otp` (OTP مخصص)
- ✅ التحقق من المعاملات المطلوبة حسب `method`
- ✅ التوافق مع التوقيعات القديمة (backward compatible)

**مثال الاستخدام:**
```php
// SMS
authentica_send_otp(phone: '+966501234567', method: 'sms');

// Email
authentica_send_otp(email: 'user@example.com', method: 'email');

// مع template و fallback
authentica_send_otp(
    phone: '+966501234567',
    method: 'sms',
    template_id: 31,
    fallback_email: 'user@example.com',
    custom_otp: '123456'
);
```

### 2. **دالة `authentica_verify_otp` محسّنة**
- ✅ دعم `phone` و `email` (مطابق للمواصفات)
- ✅ إزالة `reference` (لم يعد مطلوباً في API الجديد)
- ✅ التحقق من وجود `phone` أو `email` على الأقل

**مثال الاستخدام:**
```php
// التحقق عبر SMS
authentica_verify_otp(otp: '123456', phone: '+966501234567');

// التحقق عبر Email
authentica_verify_otp(otp: '123456', email: 'user@example.com');
```

### 3. **دالة `authentica_get_balance` جديدة**
- ✅ الحصول على الرصيد الحالي من Authentica
- ✅ استخدام GET request

**مثال الاستخدام:**
```php
$balance = authentica_get_balance();
if ($balance['success']) {
    echo "الرصيد: " . $balance['data']['balance'];
}
```

### 4. **دالة `authentica_api_request` محسّنة**
- ✅ دعم `GET` و `POST` requests
- ✅ معالجة صحيحة للـ headers
- ✅ تسجيل مفصل للأخطاء في `sms_errors.log`

### 5. **تحديث `login.php`**
- ✅ تحديث جميع استدعاءات `authentica_send_otp` و `authentica_verify_otp`
- ✅ استخدام التوقيعات الجديدة مع named parameters

## الميزات الجديدة المتاحة

### دعم القنوات المتعددة
- **SMS**: إرسال OTP عبر SMS
- **WhatsApp**: إرسال OTP عبر WhatsApp
- **Email**: إرسال OTP عبر البريد الإلكتروني

### Fallback Channel
يمكن تحديد قناة احتياطية في حالة فشل القناة الأساسية:
```php
authentica_send_otp(
    phone: '+966501234567',
    method: 'sms',
    fallback_email: 'user@example.com'
);
```

### Custom OTP
يمكن تحديد OTP مخصص بدلاً من توليده تلقائياً:
```php
authentica_send_otp(
    phone: '+966501234567',
    method: 'sms',
    custom_otp: '123456'
);
```

### Templates
يمكن استخدام قوالب مختلفة للإرسال:
```php
authentica_send_otp(
    phone: '+966501234567',
    method: 'sms',
    template_id: 31
);
```

## التوافق مع الكود القديم

الكود القديم لا يزال يعمل لأن الدوال تدعم المعاملات الاختيارية:
```php
// الكود القديم (لا يزال يعمل)
authentica_send_otp('+966501234567');
authentica_verify_otp('+966501234567', '123456');

// الكود الجديد (مطابق للمواصفات)
authentica_send_otp(phone: '+966501234567', method: 'sms');
authentica_verify_otp(otp: '123456', phone: '+966501234567');
```

## الملفات المعدّلة

1. **`inc/auth.php`**
   - `authentica_send_otp()` - محسّنة بالكامل
   - `authentica_verify_otp()` - محسّنة بالكامل

2. **`inc/functions.php`**
   - `authentica_api_request()` - محسّنة لدعم GET/POST
   - `authentica_get_balance()` - دالة جديدة

3. **`login.php`**
   - تحديث جميع الاستدعاءات للتوقيعات الجديدة

## الخطوات التالية (اختياري)

يمكن إضافة المزيد من الميزات من `authenticasa.apib`:
- ✅ Nafath Verification (`/verify-by-nafath`, `/nafath/request`, `/nafath/verify`)
- ✅ Face Verification (`/verify-by-face`, `/users/{user_id}/image`)
- ✅ Voice Verification (`/verify-by-voice`, `/users/{user_id}/voice`)
- ✅ Webhook handling للـ Nafath

## ملاحظات

- جميع الدوال تسجل الأخطاء في `sms_errors.log`
- الدوال تدعم `SKIP_OTP` environment variable للبيئة التطويرية
- جميع الطلبات تستخدم `X-Authorization` header كما هو مطلوب
- الدوال تدعم SSL verification عبر `AUTHENTICA_SSL_VERIFY` environment variable

