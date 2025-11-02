<?php
require_once __DIR__ . '/../inc/functions.php';
$me = require_admin();

$dbf = pdo_open(cfg()->db_forms);

// DB Schema setup (remains the same)
if ((cfg()->db_driver ?? 'sqlite') === 'mysql') {
    $dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), slug VARCHAR(255) UNIQUE, fields LONGTEXT, created_at DATETIME) ENGINE=InnoDB");
    try { $dbf->query("SELECT slug FROM forms LIMIT 1"); } catch (PDOException $e) { $dbf->exec("ALTER TABLE forms ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title"); }
} else {
    $dbf->exec("CREATE TABLE IF NOT EXISTS forms (id INTEGER PRIMARY KEY, title TEXT, slug TEXT UNIQUE, fields TEXT, created_at TEXT)");
    try { $dbf->query("SELECT slug FROM forms LIMIT 1"); } catch (PDOException $e) { $dbf->exec("ALTER TABLE forms ADD COLUMN slug TEXT UNIQUE"); }
}
$dbf->exec("CREATE TABLE IF NOT EXISTS app_settings (k TEXT PRIMARY KEY, v TEXT)");

// API endpoint (remains the same)
if (isset($_GET['api']) && $_GET['api'] === 'get_form' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $st = $dbf->prepare("SELECT id, title, slug, fields FROM forms WHERE id = ?");
    $st->execute([(int)$_GET['id']]);
    $form = $st->fetch(PDO::FETCH_ASSOC);
    echo json_encode($form ? ['ok' => true, 'data' => $form] : ['ok' => false, 'error' => 'Not found']);
    exit;
}

$flash = null;

// POST handling (remains the same, logic is complex)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $flash = ['type' => 'danger', 'msg' => 'CSRF Error.'];
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'save_form') {
                $id = (int)($_POST['form_id'] ?? 0);
                $title = trim($_POST['form_title'] ?? '');
                $slug = trim($_POST['form_slug'] ?? '');
                if (empty($title) || empty($slug)) throw new RuntimeException('Title and slug are required.');
                $st = $dbf->prepare("SELECT id FROM forms WHERE slug = ? AND id != ?");
                $st->execute([$slug, $id]);
                if ($st->fetch()) throw new RuntimeException('Slug is already in use.');
                $fields_blob = '';
                if (isset($_POST['field_label'])) {
                    $fields_str = [];
                    foreach ($_POST['field_label'] as $i => $label) {
                        $label = trim($label);
                        $name = trim($_POST['field_name'][$i] ?? '');
                        $type = trim($_POST['field_type'][$i] ?? 'text');
                        if (empty($label) || empty($name) || empty($type)) continue;
                        $field_line = "{$label}:{$name}:{$type}";
                        if ($type === 'select') {
                            $options = trim($_POST['field_options'][$i] ?? '');
                            if(!empty($options)) $field_line .= ":{$options}";
                        }
                        $fields_str[] = $field_line;
                    }
                    $fields_blob = implode("\n", $fields_str);
                }
                if ($id > 0) {
                    $dbf->prepare("UPDATE forms SET title=?, slug=?, fields=? WHERE id=?")->execute([$title, $slug, $fields_blob, $id]);
                    $flash = ['type' => 'success', 'msg' => 'Form updated.'];
                } else {
                    $dbf->prepare("INSERT INTO forms (title, slug, fields, created_at) VALUES (?, ?, ?, NOW())")->execute([$title, $slug, $fields_blob]);
                    $flash = ['type' => 'success', 'msg' => 'Form created.'];
                }
            } elseif ($action === 'delete_form') {
                $dbf->prepare("DELETE FROM forms WHERE id = ?")->execute([ (int)($_POST['form_id'] ?? 0) ]);
                $flash = ['type' => 'success', 'msg' => 'Form deleted.'];
            } elseif ($action === 'set_active') {
                $dbf->prepare("REPLACE INTO app_settings (k, v) VALUES ('active_form_id', ?)")->execute([ (int)($_POST['form_id'] ?? 0) ]);
                $flash = ['type' => 'success', 'msg' => 'Active form has been set.'];
            }
        } catch (Throwable $e) {
            $flash = ['type' => 'danger', 'msg' => $e->getMessage()];
        }
    }
}

$forms = $dbf->query("SELECT id, title, slug, created_at FROM forms ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$active_id = (int)($dbf->query("SELECT v FROM app_settings WHERE k='active_form_id'")->fetchColumn() ?: 0);

$page_title = 'إدارة النماذج';
$content = function() use ($forms, $active_id, $flash) { ?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : 'success' ?> text-white font-weight-bold" role="alert"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Form Builder -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">إنشاء / تعديل نموذج</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="formBuilder">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_form">
                    <input type="hidden" name="form_id" id="form_id" value="0">

                    <div class="row">
                        <div class="col-md-6"><div class="mb-3"><label class="form-label">عنوان النموذج</label><input type="text" class="form-control" id="form_title" name="form_title" required></div></div>
                        <div class="col-md-6"><div class="mb-3"><label class="form-label">الرابط (Slug)</label><input type="text" class="form-control" id="form_slug" name="form_slug" required></div></div>
                    </div>
                    <hr class="horizontal dark my-3">
                    <h6 class="text-sm">حقول النموذج</h6>
                    <div id="fieldsContainer" class="mb-3"></div>
                    <button type="button" id="addField" class="btn btn-outline-primary btn-sm mb-0">إضافة حقل</button>
                    <hr class="horizontal dark my-3">
                    <div class="d-flex justify-content-end">
                        <button type="button" id="clearForm" class="btn btn-link text-secondary me-2">إلغاء</button>
                        <button type="submit" class="btn bg-gradient-primary mb-0">حفظ النموذج</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Card -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header pb-0"><h6 class="mb-0">مساعدة</h6></div>
            <div class="card-body pt-3">
                <p class="text-sm">استخدم الأسماء البرمجية التالية للحقول الخاصة:</p>
                <ul class="list-group list-group-flush ps-0">
                    <li class="list-group-item border-0"><code class="text-xs">name</code>: اسم العميل</li>
                    <li class="list-group-item border-0"><code class="text-xs">email</code>: بريد العميل</li>
                    <li class="list-group-item border-0"><code class="text-xs">phone</code>: جوال العميل</li>
                </ul>
                <hr class="horizontal dark my-3">
                <p class="text-sm">للقوائم المنسدلة (select), استخدم الصيغة:<br><code class="text-xs">value1=Label 1|value2=Label 2</code></p>
            </div>
        </div>
    </div>
</div>

<!-- Forms List -->
<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0"><h6>النماذج الحالية</h6></div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead><tr><th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">النموذج</th><th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">الرابط</th><th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">الحالة</th><th class="text-secondary opacity-7"></th></tr></thead>
                        <tbody>
                            <?php foreach ($forms as $form): ?>
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1"><div class="d-flex flex-column justify-content-center">
                                        <h6 class="mb-0 text-sm"><?= e($form['title'] ?? '') ?></h6>
                                        <p class="text-xs text-secondary mb-0">#<?= (int)$form['id'] ?></p>
                                    </div></div>
                                </td>
                                <td><a href="<?= e(app_href(($form['slug'] ?? ''))) ?>" target="_blank" class="text-xs font-weight-bold mb-0">/<?= e($form['slug'] ?? '') ?></a></td>
                                <td class="align-middle text-center text-sm"><?= ($form['id'] ?? 0) == $active_id ? '<span class="badge badge-sm bg-gradient-success">الأساسي</span>' : '<span class="badge badge-sm bg-gradient-secondary">غير نشط</span>' ?></td>
                                <td class="align-middle">
                                    <div class="dropdown float-end">
                                        <a class="btn btn-link text-secondary mb-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v text-xs"></i></a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:;" onclick="editForm(<?= (int)$form['id'] ?>)">تعديل</a></li>
                                                                                        <li><a class="dropdown-item" href="requests.php?form_id=<?= (int)$form['id'] ?>">عرض الطلبات</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_active"><input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>"><button type="submit" class="dropdown-item" <?= ($form['id'] ?? 0) == $active_id ? 'disabled' : '' ?>>تعيين كأساسي</button></form></li>
                                            <li><form method="POST" class="d-inline" onsubmit="return confirm('سيتم حذف النموذج وجميع الطلبات المرتبطة به. هل أنت متأكد؟');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_form"><input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>"><button type="submit" class="dropdown-item text-danger">حذف</button></form></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Template for a field -->
<div id="fieldTemplate" class="d-none">
    <div class="p-3 mb-3 border rounded-2 bg-gray-100 field-item">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-sm mb-0">حقل جديد</h6>
            <button type="button" class="btn btn-link text-danger p-0 m-0 remove-field"><i class="fa fa-times"></i></button>
        </div>
        <div class="row">
            <div class="col-md-4"><div class="mb-3"><label class="form-label text-xs">التسمية (Label)</label><input type="text" name="field_label[]" class="form-control form-control-sm" required></div></div>
            <div class="col-md-4"><div class="mb-3"><label class="form-label text-xs">الاسم البرمجي (Name)</label><input type="text" name="field_name[]" class="form-control form-control-sm" required></div></div>
            <div class="col-md-4"><div class="mb-3"><label class="form-label text-xs">النوع</label><select name="field_type[]" class="form-select form-select-sm field-type"><option value="text">Text</option><option value="textarea">Textarea</option><option value="email">Email</option><option value="tel">Tel</option><option value="number">Number</option><option value="date">Date</option><option value="select">Select</option><option value="file">File</option></select></div></div>
        </div>
        <div class="form-group options-container d-none mt-2"><label class="form-label text-xs">الخيارات (لـ Select)</label><textarea name="field_options[]" class="form-control form-control-sm" rows="2" placeholder="value1=Label 1|value2=Label 2"></textarea></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fieldsContainer = document.getElementById('fieldsContainer');
    const addFieldBtn = document.getElementById('addField');
    const fieldTemplate = document.getElementById('fieldTemplate').innerHTML;
    const formBuilder = document.getElementById('formBuilder');
    const formIdInput = document.getElementById('form_id');
    const formTitleInput = document.getElementById('form_title');
    const formSlugInput = document.getElementById('form_slug');

    function addField(data = {}) {
        const newField = document.createElement('div');
        newField.innerHTML = fieldTemplate;
        fieldsContainer.appendChild(newField.firstElementChild);
        const fieldItem = fieldsContainer.lastElementChild;

        if (data.label) fieldItem.querySelector('input[name="field_label[]"]').value = data.label;
        if (data.name) fieldItem.querySelector('input[name="field_name[]"]').value = data.name;
        if (data.type) fieldItem.querySelector('select[name="field_type[]"]').value = data.type;
        
        const optionsContainer = fieldItem.querySelector('.options-container');
        if (data.type === 'select') {
            optionsContainer.classList.remove('d-none');
            if(data.options) fieldItem.querySelector('textarea[name="field_options[]"]').value = data.options;
        }
    }

    addFieldBtn.addEventListener('click', () => addField());

    fieldsContainer.addEventListener('click', e => {
        if (e.target.closest('.remove-field')) {
            e.target.closest('.field-item').remove();
        }
    });

    fieldsContainer.addEventListener('change', e => {
        if (e.target.classList.contains('field-type')) {
            const fieldItem = e.target.closest('.field-item');
            const optionsContainer = fieldItem.querySelector('.options-container');
            optionsContainer.classList.toggle('d-none', e.target.value !== 'select');
        }
    });

    document.getElementById('clearForm').addEventListener('click', () => {
        formIdInput.value = '0';
        formBuilder.reset();
        fieldsContainer.innerHTML = '';
    });

    window.editForm = async (id) => {
        const response = await fetch(`?api=get_form&id=${id}`);
        const result = await response.json();
        if (result.ok) {
            const form = result.data;
            formIdInput.value = form.id;
            formTitleInput.value = form.title;
            formSlugInput.value = form.slug;
            fieldsContainer.innerHTML = '';
            
            const fields = form.fields.split("\n").filter(line => line.trim() !== '');
            fields.forEach(line => {
                const parts = line.split(':', 4);
                const fieldData = { label: parts[0] || '', name: parts[1] || '', type: parts[2] || 'text' };
                if (parts.length > 3 && fieldData.type === 'select') {
                    fieldData.options = parts[3];
                }
                addField(fieldData);
            });
            window.scrollTo(0, 0);
        } else { alert(result.error); }
    };
});
</script>
<?php };

include __DIR__ . '/_layout.php';
