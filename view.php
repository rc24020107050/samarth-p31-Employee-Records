<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();
$isAdmin = (user_role() === 'admin');


$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    flash('error', 'Invalid employee ID.');
    redirect(module_url());
}
// Employee can access only their own record.
if (!$isAdmin) {
    $myEmployeeId = get_my_module_employee_id();

    if (!$myEmployeeId || (int) $id !== $myEmployeeId) {
        http_response_code(403);
        exit('Access denied. You can only view your own employee record.');
    }
}

$employee = find_employee($id);

if (!$employee) {
    flash('error', 'Employee record not found.');
    redirect(module_url());
}

$sql = 'SELECT * FROM emp_service_book_entries
        WHERE employee_id = ?
        ORDER BY effective_date DESC, id DESC';

$st = db()->prepare($sql);
$st->bind_param('i', $id);
$st->execute();
$entries = $st->get_result()->fetch_all(MYSQLI_ASSOC);

page_start('Employee Details', [
    'sub' => 'Employee information and service-book history.'
]);
?>

<div class="card">
    <h2>
        <?= e($employee['title'] . ' ' . $employee['full_name']) ?>
    </h2>

    <p>
        <strong>Employee Code:</strong>
        <?= e($employee['employee_code']) ?>
    </p>

    <p>
        <strong>Department:</strong>
        <?= e($employee['department']) ?>
    </p>

    <p>
        <strong>Designation:</strong>
        <?= e($employee['designation']) ?>
    </p>

    <p>
        <strong>Employee Type:</strong>
        <?= e($employee['employment_type']) ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?= e($employee['email']) ?>
    </p>

    <p>
        <strong>Date of Birth:</strong>
        <?= e($employee['date_of_birth']) ?>
    </p>

    <p>
        <strong>Joining Date:</strong>
        <?= e($employee['joining_date']) ?>
    </p>    
        <?php if($isAdmin): ?>
            <<p>
            <strong>PAN:</strong>
        <?= e($employee['pan']) ?>
    </p>
    <?php endif; ?>

    <p>
        <strong>Record Created:</strong>
        <?= e($employee['created_at']) ?>
    </p>

    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= e(module_url()) ?>">
            Back to Employees
        </a>
    </div>
</div>

<div class="card">
    <h2>Service Book History</h2>

    <?php if (!$entries): ?>
        <p>No service-book entries found.</p>
    <?php else: ?>
        <?php foreach ($entries as $entry): ?>
            <div class="card">
                <h3><?= e($entry['entry_type']) ?></h3>
                <button type="button" class="btn btn-ghost service-entry-toggle">
                           Show Details
                </button>
                <div class="service-entry-details" hidden>
                    <p>
                        <strong>Entry ID:</strong>
                        <?= e($entry['id']) ?>
                    </p>

                    <p>
                        <strong>Employee ID:</strong>
                        <?= e($entry['employee_id']) ?>
                    </p>

                    <p>
                        <strong>Created At:</strong>
                        <?= e($entry['created_at']) ?>
                    </p>
                <p>
                    <strong>Effective Date:</strong>
                    <?= e($entry['effective_date']) ?>
                </p>
        </div>

                <?php if ($entry['previous_designation'] || $entry['new_designation']): ?>
                    <p>
                        <strong>Designation:</strong>
                        <?= e($entry['previous_designation'] ?: '—') ?>
                        →
                        <?= e($entry['new_designation'] ?: '—') ?>
                    </p>
                <?php endif; ?>

                <?php if ($entry['previous_department'] || $entry['new_department']): ?>
                    <p>
                        <strong>Department:</strong>
                        <?= e($entry['previous_department'] ?: '—') ?>
                        →
                        <?= e($entry['new_department'] ?: '—') ?>
                    </p>
                <?php endif; ?>

                <?php if ($entry['remarks']): ?>
                    <p>
                        <strong>Remarks:</strong>
                        <?= e($entry['remarks']) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php page_end(); ?>