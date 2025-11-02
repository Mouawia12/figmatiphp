#!/bin/bash
# ============================================
# سكريبت ضبط تصريحات الملفات - عزم الإنجاز
# المسار: /home/azzm/
# ============================================
# استخدام: chmod +x SET_PERMISSIONS_azzm.sh && ./SET_PERMISSIONS_azzm.sh
# أو: bash SET_PERMISSIONS_azzm.sh

echo "🔐 بدء ضبط تصريحات الملفات..."

# تحديد المجلد الجذر
PROJECT_ROOT="/home/azzm"

# اللون للمخرجات
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}📁 المجلد الجذر: $PROJECT_ROOT${NC}"

# التحقق من وجود المجلد
if [ ! -d "$PROJECT_ROOT" ]; then
    echo -e "${RED}❌ المجلد $PROJECT_ROOT غير موجود!${NC}"
    exit 1
fi

# ============================================
# 1. ضبط تصريحات الملفات العادية (644)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات الملفات العادية...${NC}"
find "$PROJECT_ROOT" -type f -exec chmod 644 {} \;
echo -e "${GREEN}✅ تم ضبط الملفات إلى 644${NC}"

# ============================================
# 2. ضبط تصريحات المجلدات (755)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات المجلدات...${NC}"
find "$PROJECT_ROOT" -type d -exec chmod 755 {} \;
echo -e "${GREEN}✅ تم ضبط المجلدات إلى 755${NC}"

# ============================================
# 3. ضبط الملفات القابلة للتنفيذ (755)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات ملفات PHP...${NC}"
chmod 755 "$PROJECT_ROOT"/*.php 2>/dev/null
chmod 755 "$PROJECT_ROOT"/admin/*.php 2>/dev/null
echo -e "${GREEN}✅ تم ضبط ملفات PHP إلى 755${NC}"

# ============================================
# 4. المجلدات القابلة للكتابة (775)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات المجلدات القابلة للكتابة...${NC}"

# مجلد الملفات المرفوعة
if [ -d "$PROJECT_ROOT/uploads" ]; then
    chmod 775 "$PROJECT_ROOT/uploads"
    chown -R azzm:azzm "$PROJECT_ROOT/uploads" 2>/dev/null || chown -R www-data:www-data "$PROJECT_ROOT/uploads" 2>/dev/null
    echo -e "${GREEN}✅ uploads: 775${NC}"
fi

# مجلد قاعدة البيانات
if [ -d "$PROJECT_ROOT/data" ]; then
    chmod 775 "$PROJECT_ROOT/data"
    chown -R azzm:azzm "$PROJECT_ROOT/data" 2>/dev/null || chown -R www-data:www-data "$PROJECT_ROOT/data" 2>/dev/null
    echo -e "${GREEN}✅ data: 775${NC}"
fi

# مجلد السجلات (إن وجد)
if [ -d "$PROJECT_ROOT/logs" ]; then
    chmod 775 "$PROJECT_ROOT/logs"
    chown -R azzm:azzm "$PROJECT_ROOT/logs" 2>/dev/null || chown -R www-data:www-data "$PROJECT_ROOT/logs" 2>/dev/null
    echo -e "${GREEN}✅ logs: 775${NC}"
fi

# ============================================
# 5. ضبط ملف .env (600 - خاص جداً)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات ملف .env...${NC}"
if [ -f "$PROJECT_ROOT/.env" ]; then
    chmod 600 "$PROJECT_ROOT/.env"
    chown azzm:azzm "$PROJECT_ROOT/.env" 2>/dev/null || chown www-data:www-data "$PROJECT_ROOT/.env" 2>/dev/null
    echo -e "${GREEN}✅ .env: 600 (آمن)${NC}"
else
    echo -e "${RED}⚠️  ملف .env غير موجود${NC}"
fi

# ============================================
# 6. ضبط ملفات السجلات (644)
# ============================================
echo -e "${YELLOW}⚙️  ضبط تصريحات ملفات السجلات...${NC}"
find "$PROJECT_ROOT" -name "*.log" -type f -exec chmod 644 {} \;
echo -e "${GREEN}✅ ملفات السجلات: 644${NC}"

# ============================================
# 7. ضبط ملكية الملفات
# ============================================
echo -e "${YELLOW}⚙️  ضبط ملكية الملفات...${NC}"

# محاولة استخدام azzm كمالك أول
if id "azzm" &>/dev/null; then
    chown -R azzm:azzm "$PROJECT_ROOT" 2>/dev/null && echo -e "${GREEN}✅ تم ضبط الملكية إلى azzm:azzm${NC}"
elif id "www-data" &>/dev/null; then
    chown -R www-data:www-data "$PROJECT_ROOT" 2>/dev/null && echo -e "${GREEN}✅ تم ضبط الملكية إلى www-data:www-data${NC}"
elif id "apache" &>/dev/null; then
    chown -R apache:apache "$PROJECT_ROOT" 2>/dev/null && echo -e "${GREEN}✅ تم ضبط الملكية إلى apache:apache${NC}"
else
    echo -e "${YELLOW}⚠️  لم يتم العثور على المستخدم، اضبط الملكية يدوياً${NC}"
    echo "   جرب: chown -R azzm:azzm $PROJECT_ROOT"
fi

# ============================================
# 8. SELinux (إن كان مفعّلاً)
# ============================================
echo -e "${YELLOW}⚙️  فحص SELinux...${NC}"
if command -v getenforce &> /dev/null; then
    if [ "$(getenforce)" = "Enforcing" ]; then
        echo -e "${YELLOW}⚠️  SELinux مفعّل - قد تحتاج لضبط السياق${NC}"
        echo "   جرب: chcon -R -t httpd_sys_content_t $PROJECT_ROOT"
        echo "   للملفات القابلة للكتابة: chcon -R -t httpd_sys_rw_content_t $PROJECT_ROOT/uploads"
    fi
fi

echo ""
echo -e "${GREEN}✅ تم الانتهاء من ضبط التصريحات!${NC}"
echo ""
echo "📋 ملخص التصريحات:"
echo "   📁 المجلدات: 755"
echo "   📄 الملفات: 644"
echo "   📁 uploads/data: 775"
echo "   🔐 .env: 600"
echo "   👤 المالك: azzm:azzm (أو www-data:www-data)"
echo "   📍 المسار: $PROJECT_ROOT"
echo ""

