# مهائة تلقائية لتحويل المشروع إلى Laravel
# تشغيل: pwsh -File .\migrate-to-laravel.ps1

param(
  [string]$BaseDir = (Resolve-Path ".").Path,
  [string]$SrcDir = (Join-Path (Resolve-Path ".").Path "crosing"),
  [string]$LaravelTarget = (Join-Path (Resolve-Path ".").Path "crosing-laravel"),
  [string]$DbHost = "127.0.0.1",
  [string]$DbPort = "3306",
  [string]$DbName = "azzm_sin",
  [string]$DbUser = "root",
  [string]$DbPass = "",
  [string]$AppUrl = "http://127.0.0.1:8000"
)

function Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Err($msg){ Write-Host "[ERR ] $msg" -ForegroundColor Red }

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# اكتشاف مجلد المصدر الحقيقي (هذه السكربت يفترض التشغيل من C:\xampp\htdocs)
if (Test-Path (Join-Path $BaseDir 'laravel')) {
  # شغال من داخل مجلد crosing مباشرة
  $SrcDir = $BaseDir
}

Info "المجلد الحالي: $BaseDir"
Info "مجلد المشروع الحالي (المصدر): $SrcDir"

# 1) تأكيد وجود Composer
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
  Err "Composer غير موجود في المسار. ثبّت Composer أو أضفه للـ PATH."
  throw "composer-not-found"
}

# 2) إنشاء مشروع Laravel جديد إن لم يوجد
if (-not (Test-Path $LaravelTarget)) {
  Info "إنشاء مشروع Laravel جديد: $LaravelTarget"
  Push-Location (Split-Path $LaravelTarget)
  try {
    composer create-project laravel/laravel (Split-Path -Leaf $LaravelTarget) "^10.0"
  } finally {
    Pop-Location
  }
} else {
  Warn "مجلسد الهدف موجود مسبقًا: $LaravelTarget (سيتم النسخ فوقه)."
}

# مسارات هامة داخل الهدف
$T_App = Join-Path $LaravelTarget 'app'
$T_Routes = Join-Path $LaravelTarget 'routes'
$T_Migrations = Join-Path $LaravelTarget 'database/migrations'
$T_Views = Join-Path $LaravelTarget 'resources/views'
$T_Public = Join-Path $LaravelTarget 'public'
$T_PublicArgon = Join-Path $T_Public 'argon'
$T_PublicAssets = Join-Path $T_Public 'assets'

# مسارات المصدر داخل هذا المشروع
$S_Laravel = Join-Path $SrcDir 'laravel'
$S_App = Join-Path $S_Laravel 'app'
$S_Routes = Join-Path $S_Laravel 'routes'
$S_Migrations = Join-Path $S_Laravel 'database/migrations'
$S_Views = Join-Path $S_Laravel 'resources/views'
$S_PublicArgon = Join-Path $S_Laravel 'public/argon'
$S_PublicAssets = Join-Path $SrcDir 'assets'

if (-not (Test-Path $S_Laravel)) {
  Err "لم يتم العثور على مجلد laravel/ داخل المصدر: $S_Laravel"
  throw "scaffold-not-found"
}

# 3) نسخ الملفات الجاهزة
Info "نسخ app/"
robocopy $S_App $T_App /E | Out-Null

Info "نسخ routes/"
robocopy $S_Routes $T_Routes *.php | Out-Null

Info "نسخ migrations/"
robocopy $S_Migrations $T_Migrations /E | Out-Null

Info "نسخ views/"
robocopy $S_Views $T_Views /E | Out-Null

Info "نسخ Argon إلى public/argon"
robocopy $S_PublicArgon $T_PublicArgon /E | Out-Null

Info "نسخ الأصول العامة assets/ إلى public/assets"
robocopy $S_PublicAssets $T_PublicAssets /E | Out-Null

# 4) نقل SEO robots/sitemap إن وجدت
foreach ($seo in @('robots.txt','sitemap.xml')) {
  $srcSeo = Join-Path $SrcDir $seo
  if (Test-Path $srcSeo) {
    Copy-Item $srcSeo -Destination (Join-Path $T_Public (Split-Path $srcSeo -Leaf)) -Force
    Info "تم نسخ $seo"
  }
}

# 5) تصحيح مسارات chatbot.js من /crosing/ إلى /assets/
$T_Chatbot = Join-Path $T_PublicAssets 'chatbot.js'
if (Test-Path $T_Chatbot) {
  $content = Get-Content $T_Chatbot -Raw
  $content = $content -replace '/crosing/assets','/assets'
  Set-Content $T_Chatbot -Value $content -Encoding UTF8
  Info "تم تصحيح مسارات chatbot.js"
} else {
  Warn "لم يتم العثور على public/assets/chatbot.js (تخطي التصحيح)."
}

# 6) ضبط .env
$EnvFile = Join-Path $LaravelTarget '.env'
if (Test-Path $EnvFile) {
  Info "تحديث ملف .env"
  $env = Get-Content $EnvFile -Raw
  $env = $env -replace '(?m)^APP_URL=.*$', "APP_URL=$AppUrl"
  $env = $env -replace '(?m)^DB_CONNECTION=.*$', 'DB_CONNECTION=mysql'
  $env = $env -replace '(?m)^DB_HOST=.*$', "DB_HOST=$DbHost"
  $env = $env -replace '(?m)^DB_PORT=.*$', "DB_PORT=$DbPort"
  $env = $env -replace '(?m)^DB_DATABASE=.*$', "DB_DATABASE=$DbName"
  $env = $env -replace '(?m)^DB_USERNAME=.*$', "DB_USERNAME=$DbUser"
  $env = $env -replace '(?m)^DB_PASSWORD=.*$', "DB_PASSWORD=$DbPass"
  Set-Content $EnvFile -Value $env -Encoding UTF8
} else {
  Warn ".env غير موجود داخل الهدف (تأكد من نجاح create-project)."
}

# 7) تثبيت الحزم وتشغيل الهجرات
Push-Location $LaravelTarget
try {
  Info "تشغيل composer install (لو لزم)"
  composer install | Out-Null

  Info "تشغيل الهجرات"
  php artisan migrate -n

  Info "ربط storage"
  php artisan storage:link -n
} finally {
  Pop-Location
}

Info "نجاح التحويل. لتشغيل التطبيق:"
Write-Host "  cd `"$LaravelTarget`"" -ForegroundColor Green
Write-Host "  php artisan serve" -ForegroundColor Green
Write-Host "  ثم افتح: $AppUrl/admin" -ForegroundColor Green

Write-Host "اختياري (استيراد البيانات القديمة):" -ForegroundColor DarkCyan
Write-Host "  - سجّل الأمر في app\\Console\\Kernel.php: protected \$commands = [\\App\\Console\\Commands\\ImportLegacyCommand::class];" -ForegroundColor DarkCyan
Write-Host "  - ثم php artisan legacy:import" -ForegroundColor DarkCyan

