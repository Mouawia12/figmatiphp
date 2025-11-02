# سجل التغييرات - نظام الإشعارات المركزي

**التاريخ:** 2025-01-21  
**الإصدار:** 1.0.0

## ملخص التغييرات

تم إنشاء نظام مركزي لإرسال الإشعارات (SMS + Email) لضمان وصول جميع الإشعارات للعملاء عند:
- إنشاء طلب جديد في أي نموذج
- تحديث حالة الطلب
- إرسال ملاحظات للعميل
- طلب تعديل من العميل

---

## الملفات الجديدة

### 1. `inc/notifications.php`
نظام مركزي لإدارة الإشعارات يحتوي على:

- `extract_customer_info()` - استخراج معلومات العميل من بيانات الطلب
- `send_new_request_notification()` - إرسال إشعارات عند إنشاء طلب جديد
- `send_status_update_notification()` - إرسال إشعارات عند تحديث حالة الطلب
- `send_revision_request_notification()` - إرسال إشعارات عند طلب تعديل

**المميزات:**
- ✅ إرسال SMS و Email تلقائياً
- ✅ استخراج معلومات العميل تلقائياً من بيانات الطلب
- ✅ تسجيل الأخطاء في error_log
- ✅ دعم رسائل HTML للإيميل

---

## كيفية الاستخدام

### 1. إعداد ملف `.env`

تأكد من وجود المفاتيح التالية في ملف `.env`:

```env
# Authentica API
AUTHENTICA_BASE_URL=https://api.authentica.sa/api/v2
AUTHENTICA_API_KEY=your_api_key_here
AUTHENTICA_SMS_ENDPOINT=/send-sms
SMS_SENDER=CROSING

# Email (اختياري - إذا كان لديك PHPMailer)
MAIL_FROM_NAME=شركة عزم الإنجاز
MAIL_FROM_ADDRESS=noreply@azmalenjaz.com
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### 2. استخدام النظام في الكود

#### عند إنشاء طلب جديد:
```php
require_once __DIR__ . '/inc/notifications.php';

// بعد إدراج الطلب في قاعدة البيانات
$new_request = /* جلب بيانات الطلب */;
$results = send_new_request_notification($new_request);

if ($results['sms']['sent']) {
    echo "تم إرسال SMS";
}
if ($results['email']['sent']) {
    echo "تم إرسال Email";
}
```

#### عند تحديث حالة الطلب:
```php
$updated_request = /* جلب بيانات الطلب المحدثة */;
$results = send_status_update_notification($updated_request, $new_status, $note);
```

#### عند طلب تعديل:
```php
$results = send_revision_request_notification($request, $note, $edit_link);
```

---

## ملاحظات مهمة

### 1. معلومات العميل
النظام يحاول استخراج معلومات العميل تلقائياً من:
- حقول النموذج (`data_json.fields`)
- بيانات المستخدم المسجل (`user_id`)
- الحقول الأساسية (`name`, `email`, `phone`)

### 2. تسجيل الأخطاء
جميع الأخطاء يتم تسجيلها في `error_log` مع تفاصيل كاملة

### 3. رسائل Email
- استخدام HTML formatting
- رسائل احترافية مع تنسيق جميل
- روابط مباشرة لتتبع الطلب

### 4. رسائل SMS
- رسائل قصيرة وواضحة
- تضمين رقم التتبع ورابط التتبع
- دعم اللغة العربية

---

**تم التطوير بواسطة:** AI Assistant  
**التاريخ:** 2025-01-21

