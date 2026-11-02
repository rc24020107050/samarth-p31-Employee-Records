<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

$errors = [];
$success = '';

if (is_post()) {
    csrf_verify();

    $employee_id = filter_input(
        INPUT_POST,
        'employee_id',
        FILTER_VALIDATE_INT
    );

    $user_id = filter_input(
        INPUT_POST,
        'user_id',
        FILTER_VALIDATE_INT
    );

    if (!$employee_id || $employee_id < 1 ||
        !$user_id || $user_id < 1) {
        $errors['form'] = 'Please select a valid employee and user.';
    } else {
        // Verify that the selected user is active and is not an admin.
        $user_st = db()->prepare(
            "SELECT id FROM users
             WHERE id = ? AND role <> 'admin'
             AND status = 'active'
             LIMIT 1"
        );
        $user_st->bind_param('i', $user_id);
        $user_st->execute();
        $valid_user = $user_st->get_result()->fetch_assoc();

        // Verify that the employee is not already mapped.
        $employee_st = db()->prepare(
            'SELECT id FROM emp_employees
             WHERE id = ? AND user_id IS NULL
             LIMIT 1'
        );
        $employee_st->bind_param('i', $employee_id);
        $employee_st->execute();
        $valid_employee = $employee_st->get_result()->fetch_assoc();

        // Verify that this login account is not already mapped.
        $mapped_st = db()->prepare(
            'SELECT id FROM emp_employees
             WHERE user_id = ? LIMIT 1'
        );
        $mapped_st->bind_param('i', $user_id);
        $mapped_st->execute();
        $already_mapped = $mapped_st->get_result()->fetch_assoc();

        if (!$valid_user) {
            $errors['form'] = 'Selected login account is invalid or inactive.';
        } elseif (!$valid_employee) {
            $errors['form'] = 'Employee is already mapped or does not exist.';
        } elseif ($already_mapped) {
            $errors['form'] = 'This login account is already mapped.';
        } else {
            $update = db()->prepare(
                'UPDATE emp_employees
                 SET user_id = ?
                 WHERE id = ? AND user_id IS NULL'
            );
            $update->bind_param('ii', $user_id, $employee_id);
            $update->execute();

            if ($update->affected_rows === 1) {
                flash('ok', 'Employee login mapped successfully.');
                redirect(module_url('map_employee.php'));
            }

            $errors['form'] = 'Mapping could not be saved. Please try again.';
        }
    }
}

// Fetch employees which are not mapped yet.
$employees = db()->query(
    'SELECT id, employee_code, full_name
     FROM emp_employees
     WHERE user_id IS NULL
     ORDER BY full_name'
)->fetch_all(MYSQLI_ASSOC);

// Fetch active non-admin users which are not mapped yet.
$users = db()->query(
    "SELECT u.id, u.username, u.full_name, u.role
     FROM users u
     WHERE u.role <> 'admin'
       AND u.status = 'active'
       AND NOT EXISTS (
           SELECT 1 FROM emp_employees e
           WHERE e.user_id = u.id
       )
     ORDER BY u.full_name"
)->fetch_all(MYSQLI_ASSOC);

// Existing mappings.
$mappings = db()->query(
    'SELECT e.employee_code, e.full_name AS employee_name,
            u.username, u.full_name AS user_name, u.role
     FROM emp_employees e
     INNER JOIN users u ON u.id = e.user_id
     ORDER BY e.full_name'
)->fetch_all(MYSQLI_ASSOC);

page_start('Employee Login Mapping', [
    'sub' => 'Link employee records with their correct login accounts.'
]);
?>

<div class="card">
    <h2>Assign Login Account</h2>

    <?php if (isset($errors['form'])): ?>
        <div class="field-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <?php if (!$employees || !$users): ?>
        <p>
            No eligible unmapped employees or login accounts are
            currently available.
        </p>
    <?php else: ?>
        <form method="post" class="form">
            <?= csrf_field() ?>

            <div class="field">
                <label for="employee_id">Employee *</label>
                <select name="employee_id" id="employee_id" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['id'] ?>">
                            <?= e($employee['employee_code'] . ' - ' .
                                $employee['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="user_id">Login Account *</label>
                <select name="user_id" id="user_id" required>
                    <option value="">Select Login Account</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>">
                            <?= e($user['username'] . ' - ' .
                                $user['full_name'] . ' (' .
                                $user['role'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="btn-row">
                <button class="btn" type="submit">
                    Save Mapping
                </button>
                <a class="btn btn-ghost"
                   href="<?= e(module_url()) ?>">
                    Back
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Existing Mappings</h2>

    <?php if (!$mappings): ?>
        <p>No employee login mappings found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Employee</th>
                        <th>Username</th>
                        <th>Login Name</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mappings as $mapping): ?>
                        <tr>
                            <td><?= e($mapping['employee_code']) ?></td>
                            <td><?= e($mapping['employee_name']) ?></td>
                            <td><?= e($mapping['username']) ?></td>
                            <td><?= e($mapping['user_name']) ?></td>
                            <td><?= e($mapping['role']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php page_end(); ?>