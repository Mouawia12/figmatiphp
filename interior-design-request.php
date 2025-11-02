<?php
require __DIR__ . '/inc/functions.php';
$config = cfg();
session_start();

$form_id = 5; // The ID of the 'Interior Design Request' form
$siteTitle = $config->site_title ?? 'عزم الإنجاز';
$page_title = 'طلب تصميم داخلي';

// Fetch form fields from DB to build the form dynamically
$dbf = pdo_open($config->db_forms);
$st = $dbf->prepare("SELECT fields FROM forms WHERE id = ?");
$st->execute([$form_id]);
$form_fields_raw = $st->fetchColumn();
$form_fields = array_filter(array_map('trim', explode("\n", (string)$form_fields_raw)));

require __DIR__ . '/partials/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card card-auth fade-in">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="h3 mb-2"><?= e($page_title) ?></h2>
                        <p class="text-muted">املأ التفاصيل التالية للحصول على عرض سعر فوري لتصميمك.</p>
                    </div>

                    <?php if (empty($_SESSION['user']['id'] ?? null)):
                        // The original string had "\n" which is a literal backslash followed by 'n'. 
                        // PHP interprets this as a literal string, not a newline. 
                        // Corrected to a literal newline character.
                    ?>
                        <div class="alert alert-warning text-center"><strong>يجب تسجيل الدخول أولاً.</strong><br><a href="<?= e(app_href('login.php')) ?>" class="alert-link">اضغط هنا لتسجيل الدخول</a>.</div>
                    <?php else:
                        // The original string had "\n" which is a literal backslash followed by 'n'. 
                        // PHP interprets this as a literal string, not a newline. 
                        // Corrected to a literal newline character.
                    ?>
                        <?php if (!empty($_GET['ok'])):
                            // The original string had "\n" which is a literal backslash followed by 'n'. 
                            // PHP interprets this as a literal string, not a newline. 
                            // Corrected to a literal newline character.
                        ?>
                            <div class="alert alert-success">✅ تم استلام طلبك بنجاح. سنتواصل معك قريبًا.</div>
                        <?php elseif (!empty($_GET['err'])):
                            // The original string had "\n" which is a literal backslash followed by 'n'. 
                            // PHP interprets this as a literal string, not a newline. 
                            // Corrected to a literal newline character.
                        ?>
                            <div class="alert alert-danger">❌ حدث خطأ: <?= e(urldecode($_GET['err'])) ?></div>
                        <?php endif; ?>

                        <form action="<?= e(app_href('send-design-request.php')) ?>" method="post" enctype="multipart/form-data" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="form_id" value="<?= (int)$form_id ?>">

                            <?php foreach ($form_fields as $field): 
                                // The original string had "\n" which is a literal backslash followed by 'n'. 
                                // PHP interprets this as a literal string, not a newline. 
                                // Corrected to a literal newline character.
                                $parts = explode(':', $field, 4);
                                $label = trim($parts[0], ' *');
                                $name = $parts[1] ?? '';
                                $type = $parts[2] ?? 'text';
                                $options_str = $parts[3] ?? '';
                                $is_required = str_ends_with($parts[0], '*');
                            ?>
                                <div class="col-md-6">
                                    <label for="field_<?= e($name) ?>" class="form-label"><?= e($label) ?><?= $is_required ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <?php if ($type === 'select'): ?>
                                        <select class="form-select" id="field_<?= e($name) ?>" name="<?= e($name) ?>" <?= $is_required ? 'required' : '' ?> >
                                            <?php foreach (explode('|', $options_str) as $option): 
                                                // The original string had "\n" which is a literal backslash followed by 'n'. 
                                                // PHP interprets this as a literal string, not a newline. 
                                                // Corrected to a literal newline character.
                                                [$val, $text] = explode('=', $option, 2);
                                            ?>
                                                <option value="<?= e($val) ?>"><?= e($text) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($type === 'textarea'): ?>
                                        <textarea class="form-control" id="field_<?= e($name) ?>" name="<?= e($name) ?>" rows="3"></textarea>
                                    <?php elseif ($type === 'checkbox'): ?>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" id="field_<?= e($name) ?>" name="<?= e($name) ?>" value="1">
                                            <label class="form-check-label" for="field_<?= e($name) ?>"></label>
                                        </div>
                                    <?php else: ?>
                                        <input type="<?= e($type) ?>" id="field_<?= e($name) ?>" name="<?= e($name) ?>" class="form-control" <?= $is_required ? 'required' : '' ?> >
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <!-- Calculation Display -->
                            <div class="col-12 mt-4">
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">قيمة التصميم (المساحة * 5 ريال):</span>
                                            <strong id="design-cost-display">0.00 ريال</strong>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted">تكلفة المعاينة:</span>
                                            <strong id="visit-cost-display">0.00 ريال</strong>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center h5 mb-0">
                                            <strong>التكلفة الإجمالية:</strong>
                                            <strong id="total-cost-display" class="text-primary">0.00 ريال</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">إرسال طلب التصميم</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const PRICE_PER_METER = 5;
    const SITE_VISIT_COST = 100;

    const areaInput = document.getElementById('field_area_sqm');
    const siteVisitCheckbox = document.getElementById('field_site_visit');
    
    const designCostDisplay = document.getElementById('design-cost-display');
    const visitCostDisplay = document.getElementById('visit-cost-display');
    const totalCostDisplay = document.getElementById('total-cost-display');

    function calculateCosts() {
        const area = parseFloat(areaInput.value) || 0;
        const isSiteVisitChecked = siteVisitCheckbox.checked;

        const designCost = area * PRICE_PER_METER;
        const visitCost = isSiteVisitChecked ? SITE_VISIT_COST : 0;
        const totalCost = designCost + visitCost;

        designCostDisplay.textContent = designCost.toFixed(2) + ' ريال';
        visitCostDisplay.textContent = visitCost.toFixed(2) + ' ريال';
        totalCostDisplay.textContent = totalCost.toFixed(2) + ' ريال';
    }

    areaInput.addEventListener('input', calculateCosts);
    siteVisitCheckbox.addEventListener('change', calculateCosts);

    // Initial calculation
    calculateCosts();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
