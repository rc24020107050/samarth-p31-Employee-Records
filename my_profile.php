<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();

// This page is only for mapped non-admin employees.
if (user_role() === 'admin') {
    redirect(module_url());
}

$employeeId = get_my_module_employee_id();

if (!$employeeId) {
    http_response_code(403);
    exit('Access denied. Your employee account is not mapped.');
}

$employee = find_employee($employeeId);

if (!$employee) {
    http_response_code(404);
    exit('Employee record not found.');
}

// Fetch only this employee's service-book history.
$sql = 'SELECT * FROM emp_service_book_entries
        WHERE employee_id = ?
        ORDER BY effective_date DESC, id DESC';

$st = db()->prepare($sql);
$st->bind_param('i', $employeeId);
$st->execute();
$entries = $st->get_result()->fetch_all(MYSQLI_ASSOC);

page_start('My Employee Profile', [
    'sub' => 'View your personal employee information and service-book history.'
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

    <p>
        <strong>PAN:</strong>
        Hidden for privacy
    </p>
</div>

<div class="card">
    <h2>My Service Book History</h2>

    <?php if (!$entries): ?>
        <p>No service-book entries found.</p>
    <?php else: ?>
        <?php foreach ($entries as $entry): ?>
            <div class="card">
                <h3><?= e($entry['entry_type']) ?></h3>

                <p>
                    <strong>Effective Date:</strong>
                    <?= e($entry['effective_date']) ?>
                </p>

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