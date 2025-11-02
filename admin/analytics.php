<?php
/**
 * admin/analytics.php
 * لوحة التحليلات المتقدمة
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
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title><?= e($siteTitle) ?> – التحليلات المتقدمة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset_href('favicon-32x32.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_href('assets/styles.css?v=20251007-4')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .questions-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .question-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .question-item:last-child {
            border-bottom: none;
        }
        
        .question-text {
            flex: 1;
        }
        
        .question-count {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .category-technical {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .category-financial {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .category-sales {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .category-general {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .tabs-container {
            margin-bottom: 30px;
        }
        
        .nav-tabs .nav-link {
            color: #666;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 20px;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: none;
        }
        
        .ai-training-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .training-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .confidence-bar {
            width: 100px;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .confidence-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="app-bg">

<header class="shadow-sm bg-white sticky-top">
    <nav class="navbar container-fluid navbar-expand-lg py-3">
        <a class="navbar-brand fw-bold brand-text" href="<?= e(app_href('admin/')) ?>">
            <?= e($siteTitle) ?> – التحليلات
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navMenu" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('admin/')) ?>">لوحة التحكم</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('admin/chat.php')) ?>">الدردشة</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?= e(app_href('admin/analytics.php')) ?>">التحليلات</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(app_href('logout.php')) ?>">تسجيل الخروج</a></li>
            </ul>
        </div>
    </nav>
</header>

<main class="container-fluid analytics-container">
    <!-- الإحصائيات الرئيسية -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">إجمالي المحادثات</div>
                <div class="stat-value" id="total-conversations">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">إجمالي الرسائل</div>
                <div class="stat-value" id="total-messages">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">متوسط مدة الدردشة</div>
                <div class="stat-value" id="avg-duration">0</div>
                <div class="stat-label" style="font-size: 12px;">دقيقة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">رضا العملاء</div>
                <div class="stat-value" id="avg-satisfaction">0</div>
                <div class="stat-label" style="font-size: 12px;">من 5</div>
            </div>
        </div>
    </div>
    
    <!-- الرسوم البيانية -->
    <div class="tabs-container">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#usage">الاستخدام</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#questions">الأسئلة الشائعة</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#ai">تدريب الذكاء الاصطناعي</a>
            </li>
        </ul>
    </div>
    
    <div class="tab-content">
        <!-- تحليل الاستخدام -->
        <div id="usage" class="tab-pane fade show active">
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="usageChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <canvas id="satisfactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- أهم 10 أسئلة -->
        <div id="questions" class="tab-pane fade">
            <div class="questions-list">
                <h5 class="mb-4">🔝 أهم 10 أسئلة يسألها العملاء</h5>
                <div id="top-questions-container"></div>
            </div>
        </div>
        
        <!-- تدريب الذكاء الاصطناعي -->
        <div id="ai" class="tab-pane fade">
            <div class="ai-training-section">
                <h5 class="mb-4">🤖 بيانات تدريب الذكاء الاصطناعي</h5>
                <div id="ai-training-container"></div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API_URL = `${window.APP_BASE_URL}/api_chat.php`;
    let usageChart, satisfactionChart;
    
    // تحميل الإحصائيات الرئيسية
    async function loadAnalyticsSummary() {
        try {
            const response = await fetch(`${API_URL}?action=get_analytics_summary`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const summary = data.data;
                document.getElementById('total-conversations').textContent = 
                    summary.total_conversations || 0;
                document.getElementById('total-messages').textContent = 
                    summary.total_messages || 0;
                document.getElementById('avg-duration').textContent = 
                    Math.round((summary.avg_duration || 0) / 60);
                document.getElementById('avg-satisfaction').textContent = 
                    (summary.avg_satisfaction || 0).toFixed(1);
            }
        } catch (error) {
            console.error('خطأ في تحميل الإحصائيات:', error);
        }
    }
    
    // تحميل تحليلات الاستخدام
    async function loadUsageAnalytics() {
        try {
            const response = await fetch(`${API_URL}?action=get_usage_analytics&days=30`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const analytics = data.data.reverse();
                
                const labels = analytics.map(a => a.date);
                const conversations = analytics.map(a => a.conversations || 0);
                const satisfaction = analytics.map(a => a.avg_satisfaction || 0);
                
                // رسم بياني الاستخدام
                const usageCtx = document.getElementById('usageChart').getContext('2d');
                if (usageChart) usageChart.destroy();
                usageChart = new Chart(usageCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'المحادثات اليومية',
                            data: conversations,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { font: { size: 12 } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                
                // رسم بياني الرضا
                const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
                if (satisfactionChart) satisfactionChart.destroy();
                satisfactionChart = new Chart(satisfactionCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'رضا العملاء',
                            data: satisfaction,
                            borderColor: '#764ba2',
                            backgroundColor: 'rgba(118, 75, 162, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { font: { size: 12 } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5
                            }
                        }
                    }
                });
            }
        } catch (error) {
            console.error('خطأ في تحميل تحليلات الاستخدام:', error);
        }
    }
    
    // تحميل أهم 10 أسئلة
    async function loadTopQuestions() {
        try {
            const response = await fetch(`${API_URL}?action=get_top_questions`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const container = document.getElementById('top-questions-container');
                container.innerHTML = '';
                
                data.data.forEach((q, index) => {
                    const categoryClass = `category-${q.category === 'دعم فني' ? 'technical' : 
                                                      q.category === 'مالي' ? 'financial' : 
                                                      q.category === 'مبيعات' ? 'sales' : 'general'}`;
                    
                    const html = `
                        <div class="question-item">
                            <div class="question-text">
                                <div><strong>${index + 1}. ${q.question}</strong></div>
                                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                                    ${q.answer ? q.answer.substring(0, 100) + '...' : 'بدون إجابة'}
                                </div>
                                <span class="category-badge ${categoryClass}">${q.category}</span>
                            </div>
                            <div class="question-count">${q.frequency} مرة</div>
                        </div>
                    `;
                    
                    container.innerHTML += html;
                });
            }
        } catch (error) {
            console.error('خطأ في تحميل الأسئلة الشائعة:', error);
        }
    }
    
    // تحميل بيانات تدريب الذكاء الاصطناعي
    async function loadAITrainingData() {
        try {
            const response = await fetch(`${API_URL}?action=get_ai_training_data`);
            const data = await response.json();
            
            if (data.success && data.data) {
                const container = document.getElementById('ai-training-container');
                container.innerHTML = '';
                
                data.data.slice(0, 20).forEach(item => {
                    const confidencePercent = Math.round(item.confidence_score * 100);
                    
                    const html = `
                        <div class="training-item">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 5px;">
                                    ${item.question}
                                </div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                    ${item.answer.substring(0, 80)}...
                                </div>
                                <div class="confidence-bar">
                                    <div class="confidence-fill" style="width: ${confidencePercent}%"></div>
                                </div>
                            </div>
                            <div style="text-align: center; margin-right: 20px;">
                                <div style="font-weight: bold; color: #667eea;">${confidencePercent}%</div>
                                <div style="font-size: 11px; color: #999;">ثقة</div>
                                <div style="font-size: 11px; color: #999; margin-top: 5px;">
                                    ${item.usage_count} استخدام
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.innerHTML += html;
                });
            }
        } catch (error) {
            console.error('خطأ في تحميل بيانات الذكاء الاصطناعي:', error);
        }
    }
    
    // تحديث دوري
    function initializeAnalytics() {
        loadAnalyticsSummary();
        loadUsageAnalytics();
        loadTopQuestions();
        loadAITrainingData();
        
        // تحديث كل 30 ثانية
        setInterval(() => {
            loadAnalyticsSummary();
            loadUsageAnalytics();
            loadTopQuestions();
        }, 30000);
    }
    
    // التحميل الأولي
    document.addEventListener('DOMContentLoaded', initializeAnalytics);
</script>

</body>
</html>
