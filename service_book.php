<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

// Fetch employees for the dropdown.
$employees = db()->query(
    'SELECT id, employee_code, title, full_name
     FROM emp_employees
     ORDER BY full_name ASC'
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

$entryTypes = ['Joining', 'Promotion', 'Transfer'];

if (is_post()) {
    csrf_verify();

    // Safely collect submitted values.
    foreach ($data as $key => $value) {
        $posted = $_POST[$key] ?? '';

        $data[$key] = is_scalar($posted)
            ? trim((string) $posted)
            : '';
    }

    $employeeId = filter_var(
        $data['employee_id'],
        FILTER_VALIDATE_INT
    );

    // Validate employee.
    if (
        !$employeeId ||
        $employeeId < 1 ||
        !find_employee($employeeId)
    ) {
        $errors['employee_id'] = 'Please select a valid employee.';
    }

    // Validate entry type.
    if (!in_array($data['entry_type'], $entryTypes, true)) {
        $errors['entry_type'] = 'Please select a valid entry type.';
    }

    // Validate effective date.
    $date = DateTime::createFromFormat(
        '!Y-m-d',
        $data['effective_date']
    );

    if (
        !$date ||
        $date->format('Y-m-d') !== $data['effective_date']
    ) {
        $errors['effective_date'] = 'Please enter a valid effective date.';
    }

    // Validate text lengths.
    $textFields = [
        'previous_designation',
        'new_designation',
        'previous_department',
        'new_department',
    ];

    foreach ($textFields as $field) {
        if (mb_strlen($data[$field]) > 100) {
            $errors[$field] = 'Maximum 100 characters allowed.';
        }
    }

    if (mb_strlen($data['remarks']) > 500) {
        $errors['remarks'] = 'Remarks must not exceed 500 characters.';
    }

    // Entry-specific validation.
    if (
        $data['entry_type'] === 'Joining' &&
        $data['new_designation'] === '' &&
        $data['new_department'] === ''
    ) {
        $errors['new_designation'] =
            'Enter a new designation or department for joining.';
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

    // Save entry if validation passes.
    if (!$errors) {
        $sql = 'INSERT INTO emp_service_book_entries
            (
                employee_id,
                entry_type,
                effective_date,
                previous_designation,
                new_designation,
                previous_department,
                new_department,
                remarks
            )
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

        audit(
            'create',
            'emp_service_book_entries',
            $st->insert_id
        );

        flash('ok', 'Service-book entry added successfully.');

        redirect(module_url('view.php?id=' . $employeeId));
    }
}

page_start('Add Service Book Entry', [
    'sub' => 'Maintain employee joining, promotion and transfer records.'
]);
?>

<div class="card">

    <div class="card-head">
        <div>
            <h2>Service Book Information</h2>
            <p class="muted small">
                Enter accurate details of the employee's service history.
            </p>
        </div>
        <span class="badge badge-brand">P31 Service Book</span>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-bad" role="alert">
            Please correct the highlighted fields before saving.
        </div>
    <?php endif; ?>

    <form method="post" class="form">
        <?= csrf_field() ?>

        <h3>1. Employee & Entry Details</h3>

        <div class="grid grid-2">

            <div class="field">
                <label for="employee_id">Select Employee *</label>

                <select id="employee_id" name="employee_id" required>
                    <option value="">Choose an employee</option>

                    <?php foreach ($employees as $employee): ?>
                        <option
                            value="<?= (int) $employee['id'] ?>"
                            <?= (string) $data['employee_id'] ===
                                (string) $employee['id'] ? 'selected' : '' ?>
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
                    <div class="field-error">
                        <?= e($errors['employee_id']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="entry_type">Entry Type *</label>

                <select id="entry_type" name="entry_type" required>
                    <?php foreach ($entryTypes as $type): ?>
                        <option
                            value="<?= e($type) ?>"
                            <?= $data['entry_type'] === $type ? 'selected' : '' ?>
                        >
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (isset($errors['entry_type'])): ?>
                    <div class="field-error">
                        <?= e($errors['entry_type']) ?>
                    </div>
                <?php endif; ?>

                <div class="help">
                    Choose Joining, Promotion or Transfer.
                </div>
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
                    <div class="field-error">
                        <?= e($errors['effective_date']) ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <hr>

        <h3>2. Designation Details</h3>

        <div class="grid grid-2">

            <div class="field">
                <label for="previous_designation">
                    Previous Designation
                </label>

                <input
                    type="text"
                    id="previous_designation"
                    name="previous_designation"
                    maxlength="100"
                    placeholder="e.g. Assistant Professor"
                    value="<?= e($data['previous_designation']) ?>"
                >

                <?php if (isset($errors['previous_designation'])): ?>
                    <div class="field-error">
                        <?= e($errors['previous_designation']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="new_designation">New Designation</label>

                <input
                    type="text"
                    id="new_designation"
                    name="new_designation"
                    maxlength="100"
                    placeholder="e.g. Associate Professor"
                    value="<?= e($data['new_designation']) ?>"
                >

                <?php if (isset($errors['new_designation'])): ?>
                    <div class="field-error">
                        <?= e($errors['new_designation']) ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <hr>

        <h3>3. Department Details</h3>

        <div class="grid grid-2">

            <div class="field">
                <label for="previous_department">
                    Previous Department
                </label>

                <input
                    type="text"
                    id="previous_department"
                    name="previous_department"
                    maxlength="100"
                    placeholder="e.g. Mathematics"
                    value="<?= e($data['previous_department']) ?>"
                >

                <?php if (isset($errors['previous_department'])): ?>
                    <div class="field-error">
                        <?= e($errors['previous_department']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="new_department">New Department</label>

                <input
                    type="text"
                    id="new_department"
                    name="new_department"
                    maxlength="100"
                    placeholder="e.g. Computer Science"
                    value="<?= e($data['new_department']) ?>"
                >

                <?php if (isset($errors['new_department'])): ?>
                    <div class="field-error">
                        <?= e($errors['new_department']) ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <hr>

        <h3>4. Additional Remarks</h3>

        <div class="field">
            <label for="remarks">Remarks</label>

            <textarea
                id="remarks"
                name="remarks"
                maxlength="500"
                rows="4"
                placeholder="Enter additional service-related information..."
            ><?= e($data['remarks']) ?></textarea>

            <?php if (isset($errors['remarks'])): ?>
                <div class="field-error">
                    <?= e($errors['remarks']) ?>
                </div>
            <?php endif; ?>

            <div class="help">
                Maximum 500 characters.
            </div>
        </div>

        <div class="btn-row">
            <button class="btn" type="submit">
                Save Service Book Entry
            </button>

            <a class="btn btn-ghost" href="<?= e(module_url()) ?>">
                Cancel
            </a>
        </div>

    </form>
</div>

<?php page_end(); ?>