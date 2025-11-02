<?php
/**
 * admin/ai-training.php
 * إدارة تدريب الذكاء الاصطناعي
 */

require __DIR__ . '/../inc/functions.php';
require __DIR__ . '/../inc/init_chat_db.php';

session_start();
if (empty($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . app_href('login.php'));
    exit;
}

$config = cfg();
init_chat_database();

$siteTitle = $config->site_title ?? 'عزم الإنجاز';

// معالجة إضافة بيانات تدريب جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        $pdo = pdo_open($config->db_requests);
        
        if ($action === 'add_training') {
            $question = $_POST['question'] ?? '';
            $answer = $_POST['answer'] ?? '';
            $category = $_POST['category'] ?? '';
            $keywords = $_POST['keywords'] ?? '';
            $confidence = (float)($_POST['confidence_score'] ?? 0.8);
            
            if (!$question || !$answer) {
                throw new Exception('السؤال والإجابة مطلوبان');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO ai_training_data
                (question, answer, category, keywords, confidence_score, usage_count, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$question, $answer, $category, $keywords, $confidence]);
            
            $_SESSION['success_msg'] = 'تم إضافة بيانات التدريب بنجاح';
        } elseif ($action === 'update_training') {
            $id = (int)($_POST['id'] ?? 0);
            $question = $_POST['question'] ?? '';
            $answer = $_POST['answer'] ?? '';
            $category = $_POST['category'] ?? '';
            $keywords = $_POST['keywords'] ?? '';
            $confidence = (float)($_POST['confidence_score'] ?? 0.8);
            
            if (!$id || !$question || !$answer) {
                throw new Exception('بيانات ناقصة');
            }
            
            $stmt = $pdo->prepare("
                UPDATE ai_training_data
                SET question = ?, answer = ?, category = ?, keywords = ?, 
                    confidence_score = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$question, $answer, $category, $keywords, $confidence, $id]);
            
            $_SESSION['success_msg'] = 'تم تحديث بيانات التدريب بنجاح';
        } elseif ($action === 'delete_training') {
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('معرف غير صحيح');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ai_training_data WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success_msg'] = 'تم حذف بيانات التدريب بنجاح';
        }
        
        header('Location: ' . app_href('admin/ai-training.php'));
        exit;
    } catch (Exception $e) {
        $_SESSION['error_msg'] = $e->getMessage();
    }
}

// جلب بيانات التدريب
$training_data = [];
try {
    $pdo = pdo_open($config->db_requests);
    $stmt = $pdo->prepare("
        SELECT * FROM ai_training_data
        ORDER BY confidence_score DESC, usage_count DESC
    ");
    $stmt->execute();
    $training_data = $stmt->fetchAll();
} catch (Throwable $e) { /* تجاهل */ }
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title><?= e($siteTitle) ?> – تدريب الذكاء الاصطناعي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251007-4')) ?>">
    <style>
        .training-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .training-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        
        .confidence-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="app-bg">

<header class="shadow-sm bg-white sticky-top">
    <nav class="navbar container-fluid navbar-expand-lg py-3">
        <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('admin/')) ?>">
            <?= e($siteTitle) ?> – تدريب الذكاء الاصطناعي
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navMenu" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('admin/')) ?>">لوحة التحكم</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('admin/chat.php')) ?>">الدردشة</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('admin/analytics.php')) ?>">التحليلات</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?= e(app_href('admin/ai-training.php')) ?>">تدريب الذكاء الاصطناعي</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('logout.php')) ?>">تسجيل الخروج</a></li>
            </ul>
        </div>
    </nav>
</header>

<main class="container-fluid p-4">
    <!-- الرسائل -->
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ <?= e($_SESSION['success_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ❌ <?= e($_SESSION['error_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
    
    <!-- نموذج إضافة بيانات تدريب جديدة -->
    <div class="form-section">
        <h4 class="mb-4">🤖 إضافة بيانات تدريب جديدة</h4>
        <form method="POST" class="vstack gap-3">
            <input type="hidden" name="action" value="add_training">
            
            <div>
                <label class="form-label">السؤال</label>
                <input type="text" name="question" class="form-control" placeholder="مثال: كيف أغير كلمة المرور؟" required>
            </div>
            
            <div>
                <label class="form-label">الإجابة</label>
                <textarea name="answer" class="form-control" rows="4" placeholder="أدخل الإجابة المفصلة..." required></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select">
                        <option value="عام">عام</option>
                        <option value="دعم فني">دعم فني</option>
                        <option value="مالي">مالي</option>
                        <option value="مبيعات">مبيعات</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">درجة الثقة (0-1)</label>
                    <input type="number" name="confidence_score" class="form-control" min="0" max="1" step="0.1" value="0.8">
                </div>
            </div>
            
            <div>
                <label class="form-label">الكلمات المفتاحية (مفصولة بفاصلة)</label>
                <input type="text" name="keywords" class="form-control" placeholder="كلمة1، كلمة2، كلمة3">
            </div>
            
            <button type="submit" class="btn btn-primary">➕ إضافة بيانات التدريب</button>
        </form>
    </div>
    
    <!-- قائمة بيانات التدريب -->
    <div class="training-card">
        <h4 class="mb-4">📊 بيانات التدريب الموجودة (<?= count($training_data) ?>)</h4>
        
        <?php if (empty($training_data)): ?>
            <div class="alert alert-info">لا توجد بيانات تدريب حتى الآن</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>السؤال</th>
                            <th>الفئة</th>
                            <th>الثقة</th>
                            <th>الاستخدام</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($training_data as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e(substr($item['question'], 0, 50)) ?></strong>
                                    <?php if (strlen($item['question']) > 50): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= e($item['category'] ?? 'عام') ?></span>
                                </td>
                                <td>
                                    <span class="confidence-badge">
                                        <?= round($item['confidence_score'] * 100) ?>%
                                    </span>
                                </td>
                                <td><?= (int)$item['usage_count'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editTraining(<?= $item['id'] ?>)">✏️</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_training">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
