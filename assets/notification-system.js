/**
 * notification-system.js
 * نظام الإشعارات الذكي
 * - لا تظهر تلقائياً عند الأخطاء
 * - تظهر مرة واحدة فقط بعد 3 دقائق من فتح الصفحة
 * - تأثير تلاشي عند الظهور والاختفاء
 */

class NotificationSystem {
    constructor() {
        this.shown = false; // تم عرض الإشعار مرة واحدة فقط
        this.pageOpenTime = Date.now();
        this.minShowDelay = 3 * 60 * 1000; // 3 دقائق بالميلي ثانية
        this.init();
    }

    init() {
        // إنشاء حاوية الإشعارات
        this.createNotificationContainer();
        
        // بدء المراقبة بعد 3 دقائق
        setTimeout(() => {
            this.enableNotifications();
        }, this.minShowDelay);
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    enableNotifications() {
        console.log('✅ نظام الإشعارات مفعّل (بعد 3 دقائق)');
    }

    /**
     * عرض إشعار
     * @param {string} title - العنوان
     * @param {string} message - الرسالة
     * @param {string} type - النوع: success, error, warning, info
     * @param {number} duration - مدة العرض بالميلي ثانية (0 = يدوي)
     */
    show(title, message, type = 'info', duration = 4000) {
        // تحقق من أن العميل قضى أكثر من 3 دقائق في الصفحة
        const elapsed = Date.now() - this.pageOpenTime;
        if (elapsed <= this.minShowDelay) {
            console.log(`⏳ الإشعارات معطلة (العميل في الصفحة منذ ${Math.round(elapsed / 1000)}s فقط - يحتاج 180s)`);
            return;
        }

        // تحقق من أن الإشعار لم يتم عرضه مسبقاً
        if (this.shown) {
            console.log('⚠️ تم عرض الإشعار مسبقاً في هذه الجلسة');
            return;
        }

        console.log('✅ عرض الإشعار (العميل في الصفحة أكثر من 3 دقائق)');
        this.shown = true;
        this.displayNotification(title, message, type, duration);
    }

    displayNotification(title, message, type, duration) {
        const container = document.getElementById('notification-container');
        
        // الألوان حسب النوع
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };

        const icons = {
            success: '✓',
            error: '⚠',
            warning: '⚠',
            info: 'ℹ'
        };

        const bgColor = colors[type] || colors.info;
        const icon = icons[type] || icons.info;

        // إنشاء عنصر الإشعار
        const notification = document.createElement('div');
        notification.style.cssText = `
            background: ${bgColor};
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            text-align: right;
            direction: rtl;
            animation: fadeIn 0.5s ease-out;
            pointer-events: auto;
        `;

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px; justify-content: flex-end;">
                <div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 5px;">${title}</div>
                    <div style="font-size: 14px; opacity: 0.95;">${message}</div>
                </div>
                <div style="font-size: 24px; flex-shrink: 0;">${icon}</div>
            </div>
        `;

        // إضافة الأنماط
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                @keyframes fadeOut {
                    from {
                        opacity: 1;
                        transform: scale(1);
                    }
                    to {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        container.appendChild(notification);

        // إزالة الإشعار بعد المدة المحددة
        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, duration);
        }
    }

    /**
     * إعادة تعيين النظام (للاختبار)
     */
    reset() {
        this.shown = false;
        this.pageOpenTime = Date.now();
        console.log('🔄 تم إعادة تعيين نظام الإشعارات');
    }
}

// إنشاء نسخة عامة
window.notificationSystem = new NotificationSystem();

// مثال على الاستخدام:
// window.notificationSystem.show('إرسال ناجح!', 'تم إرسال الرسالة بنجاح', 'success', 4000);
// window.notificationSystem.show('خطأ!', 'حدث خطأ ما', 'error', 4000);
