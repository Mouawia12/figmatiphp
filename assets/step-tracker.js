/**
 * step-tracker.js
 * نظام تتبع خطوات العميل وإرسال التنبيهات
 */

class StepTracker {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || `${window.APP_BASE_URL}/api_chat.php`;
        this.conversationId = options.conversationId || null;
        this.customerId = options.customerId || null;
        this.currentStep = 0;
        this.steps = [];
        this.stepStartTime = null;
    }
    
    /**
     * تسجيل خطوة جديدة
     * @param {string} stepName - اسم الخطوة
     * @param {number} stepNumber - رقم الخطوة
     * @param {string} status - حالة الخطوة (in_progress, completed, abandoned)
     */
    async trackStep(stepName, stepNumber, status = 'in_progress') {
        if (!this.customerId) {
            console.warn('معرف العميل غير محدد');
            return;
        }
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'track_step',
                    conversation_id: this.conversationId || 0,
                    customer_id: this.customerId,
                    step_name: stepName,
                    step_number: stepNumber,
                    status: status
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.currentStep = stepNumber;
                this.stepStartTime = Date.now();
                
                // تسجيل الخطوة محلياً
                this.steps.push({
                    name: stepName,
                    number: stepNumber,
                    status: status,
                    startTime: this.stepStartTime
                });
                
                console.log(`✅ تم تسجيل الخطوة: ${stepName}`);
            }
        } catch (error) {
            console.error('خطأ في تسجيل الخطوة:', error);
        }
    }
    
    /**
     * تسجيل خطوة مكتملة
     */
    async completeStep(stepName, stepNumber) {
        await this.trackStep(stepName, stepNumber, 'completed');
    }
    
    /**
     * تسجيل خطوة معلقة (العميل توقف)
     */
    async abandonStep(stepName, stepNumber) {
        await this.trackStep(stepName, stepNumber, 'abandoned');
        
        // إرسال تنبيه فوري للمدير
        this.sendAlert(
            `العميل ${this.customerId} توقف عند الخطوة: ${stepName}`,
            stepName
        );
    }
    
    /**
     * إرسال تنبيه للمدير
     */
    async sendAlert(message, stepName) {
        try {
            // يمكن إضافة endpoint خاص للتنبيهات
            console.log(`⚠️ تنبيه: ${message}`);
        } catch (error) {
            console.error('خطأ في إرسال التنبيه:', error);
        }
    }
    
    /**
     * جلب خطوات العميل
     */
    async getSteps() {
        if (!this.customerId) return [];
        
        try {
            const response = await fetch(
                `${this.apiUrl}?action=get_customer_steps&customer_id=${this.customerId}`
            );
            
            const data = await response.json();
            if (data.success) {
                return data.data;
            }
        } catch (error) {
            console.error('خطأ في جلب الخطوات:', error);
        }
        
        return [];
    }
    
    /**
     * مراقبة الخروج من الصفحة (العميل ترك الموقع)
     */
    monitorPageExit() {
        window.addEventListener('beforeunload', () => {
            if (this.currentStep > 0) {
                // إرسال تنبيه بأن العميل ترك الموقع
                navigator.sendBeacon(this.apiUrl, new URLSearchParams({
                    action: 'track_step',
                    customer_id: this.customerId,
                    step_name: `الخطوة ${this.currentStep}`,
                    step_number: this.currentStep,
                    status: 'abandoned'
                }));
            }
        });
    }
    
    /**
     * مراقبة عدم النشاط
     */
    monitorInactivity(timeoutSeconds = 300) {
        let inactivityTimer;
        
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                if (this.currentStep > 0) {
                    this.abandonStep(
                        `الخطوة ${this.currentStep}`,
                        this.currentStep
                    );
                }
            }, timeoutSeconds * 1000);
        };
        
        // إعادة تعيين المؤقت عند أي نشاط
        document.addEventListener('click', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('mousemove', resetTimer);
        
        resetTimer();
    }
}

// تصدير للاستخدام العام
window.StepTracker = StepTracker;
