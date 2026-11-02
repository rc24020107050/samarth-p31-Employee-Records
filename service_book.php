<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();
require_role('admin');

$employees = db()->query(
    'SELECT id, employee_code, title, full_name
     FROM emp_employees
     ORDER BY full_name'
)->fetch_all(MYSQLI_ASSOC);

$data = [
    'employee_id' => (string) ($_GET['employee_id'] ?? ''),
    'entry_type' => 'Joining',
    'effective_date' => '',
    'previous_designation' => '',
    'new_designation' => '',
    'previous_department' => '',
    'new_department' => '',
    'remarks' => '',
];

$errors = [];

if (is_post()) {
    csrf_verify();

    foreach ($data as $key => $value) {
        $data[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $employeeId = filter_var(
        $data['employee_id'],
        FILTER_VALIDATE_INT
    );

    $allowedTypes = ['Joining', 'Promotion', 'Transfer'];

    if (!$employeeId || !find_employee($employeeId)) {
        $errors['employee_id'] = 'Select a valid employee.';
    }

    if (!in_array($data['entry_type'], $allowedTypes, true)) {
        $errors['entry_type'] = 'Select a valid entry type.';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $data['effective_date']);

    if (
        !$date ||
        $date->format('Y-m-d') !== $data['effective_date']
    ) {
        $errors['effective_date'] = 'Enter a valid effective date.';
    }

    if (mb_strlen($data['remarks']) > 500) {
        $errors['remarks'] = 'Remarks must be 500 characters or fewer.';
    }
    if (
    $data['entry_type'] === 'Joining' &&
    $data['new_designation'] === '' &&
    $data['new_department'] === ''
) {
    $errors['new_designation'] =
        'Enter a new designation or new department for joining.';
}

if (
    $data['entry_type'] === 'Promotion' &&
    $data['new_designation'] === ''
) {
    $errors['new_designation'] =
        'New designation is required for promotion.';
}

if (
    $data['entry_type'] === 'Transfer' &&
    $data['new_department'] === ''
) {
    $errors['new_department'] =
        'New department is required for transfer.';
}
    $textFields = [
    'previous_designation',
    'new_designation',
    'previous_department',
    'new_department',
];

foreach ($textFields as $field) {
    if (mb_strlen($data[$field]) > 100) {
        $errors[$field] = 'This field must be 100 characters or fewer.';
    }
}

    if (!$errors) {
        $sql = 'INSERT INTO emp_service_book_entries
            (employee_id, entry_type, effective_date,
             previous_designation, new_designation,
             previous_department, new_department, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $st = db()->prepare($sql);

        $st->bind_param(
            'isssssss',
            $employeeId,
            $data['entry_type'],
            $data['effective_date'],
            $data['previous_designation'],
            $data['new_designation'],
            $data['previous_department'],
            $data['new_department'],
            $data['remarks']
        );

        $st->execute();

        audit('create', 'emp_service_book_entries', $st->insert_id);
        flash('ok', 'Service-book entry added successfully.');

        redirect(module_url('view.php?id=' . $employeeId));
    }
}

page_start('Add Service Book Entry', [
    'sub' => 'Record an employee joining, promotion or transfer.'
]);
?>

<div class="card">
    <?php if ($errors): ?>
        <div class="field-error">
            Please correct the highlighted fields and try again.
        </div>
    <?php endif; ?>

    <form method="post" class="form">
        <?= csrf_field() ?>

        <div class="grid grid-2">
            <div class="field">
                <label for="employee_id">Employee *</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">Select employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option
                            value="<?= e($employee['id']) ?>"
                            <?= $data['employee_id'] == $employee['id'] ? 'selected' : '' ?>
                        >
                            <?= e(
                                $employee['employee_code'] . ' — ' .
                                $employee['title'] . ' ' .
                                $employee['full_name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['employee_id'])): ?>
                    <div class="field-error"><?= e($errors['employee_id']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="entry_type">Entry Type *</label>
                <select id="entry_type" name="entry_type" required>
                    <?php foreach (['Joining', 'Promotion', 'Transfer'] as $type): ?>
                        <option
                            value="<?= e($type) ?>"
                            <?= $data['entry_type'] === $type ? 'selected' : '' ?>
                        >
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['entry_type'])): ?>
                    <div class="field-error"><?= e($errors['entry_type']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="effective_date">Effective Date *</label>
                <input
                    type="date"
                    id="effective_date"
                    name="effective_date"
                    value="<?= e($data['effective_date']) ?>"
                    required
                >
                <?php if (isset($errors['effective_date'])): ?>
                    <div class="field-error"><?= e($errors['effective_date']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="previous_designation">Previous Designation</label>
                <input
                    id="previous_designation"
                    name="previous_designation"
                    maxlength="100"
                    value="<?= e($data['previous_designation']) ?>"
                ><?php if (isset($errors['previous_designation'])): ?>
    <div class="field-error"><?= e($errors['previous_designation']) ?></div>
<?php endif; ?>
            </div>

            <div class="field">
                <label for="new_designation">New Designation</label>
                <input
                    id="new_designation"
                    name="new_designation"
                    maxlength="100"
                    value="<?= e($data['new_designation']) ?>"
                >
                <?php if (isset($errors['new_designation'])): ?>
    <div class="field-error"><?= e($errors['new_designation']) ?></div>
<?php endif; ?>
            </div>

            <div class="field">
                <label for="previous_department">Previous Department</label>
                <input
                    id="previous_department"
                    name="previous_department"
                    maxlength="100"
                    value="<?= e($data['previous_department']) ?>"
                >
            </div>

            <div class="field">
                <label for="new_department">New Department</label>
                <input
                    id="new_department"
                    name="new_department"
                    maxlength="100"
                    value="<?= e($data['new_department']) ?>"
                >
                <?php if (isset($errors['new_department'])): ?>
    <div class="field-error"><?= e($errors['new_department']) ?></div>
<?php endif; ?>
            </div>

            <div class="field">
                <label for="remarks">Remarks</label>
                <textarea
                    id="remarks"
                    name="remarks"
                    maxlength="500"
                    rows="3"
                ><?= e($data['remarks']) ?></textarea>
                <?php if (isset($errors['remarks'])): ?>
                    <div class="field-error"><?= e($errors['remarks']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn" type="submit">Save Entry</button>
            <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Cancel</a>
        </div>
    </form>
</div>

<?php page_end(); ?>