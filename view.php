<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();

$isAdmin = (user_role() === 'admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    flash('error', 'Invalid employee ID.');
    redirect(module_url());
}

// Employees can access only their own records.
if (!$isAdmin) {
    $myEmployeeId = get_my_module_employee_id();

    if (!$myEmployeeId || (int) $id !== $myEmployeeId) {
        http_response_code(403);
        exit('Access denied. You can only view your own employee record.');
    }
}

$employee = find_employee((int) $id);

if (!$employee) {
    flash('error', 'Employee record not found.');
    redirect(module_url());
}

// Fetch service-book history for this employee.
$sql = 'SELECT * FROM emp_service_book_entries
        WHERE employee_id = ?
        ORDER BY effective_date DESC, id DESC';

$st = db()->prepare($sql);
$st->bind_param('i', $id);
$st->execute();

$entries = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$employeeName = trim(
    ($employee['title'] ?? '') . ' ' . ($employee['full_name'] ?? '')
);

page_start('Employee Details', [
    'sub' => 'Employee profile and service-book history.'
]);
?>

<style>
.p31-profile-hero {
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:18px;
    padding:22px;
    margin-bottom:20px;
    background:var(--brand-soft);
    border:1px solid var(--line);
    border-radius:var(--radius);
}
.p31-profile-identity {
    display:flex;
    align-items:center;
    gap:16px;
}
.p31-profile-avatar {
    width:58px;
    height:58px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    border-radius:16px;
    background:var(--brand);
    color:#fff;
    font-size:22px;
    font-weight:700;
}
.p31-profile-name {
    margin:0 0 5px;
    font-size:21px;
    font-weight:700;
}
.p31-profile-meta {
    color:var(--muted);
    font-size:13px;
}
.p31-profile-grid {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
}
.p31-profile-item {
    min-width:0;
    padding:14px;
    border:1px solid var(--line);
    border-radius:var(--radius);
    background:var(--surface);
}
.p31-profile-label {
    display:block;
    margin-bottom:6px;
    color:var(--muted);
    font-size:12px;
}
.p31-profile-value {
    display:block;
    font-weight:600;
    overflow-wrap:anywhere;
}
.p31-section-title {
    margin:0 0 16px;
    font-size:17px;
}
.p31-history-list {
    display:grid;
    gap:14px;
}
.p31-history-item {
    padding:16px;
    border:1px solid var(--line);
    border-left:4px solid var(--brand);
    border-radius:var(--radius);
    background:var(--surface);
}
.p31-history-top {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:10px;
}
.p31-history-date {
    color:var(--muted);
    font-size:13px;
}
.p31-history-details {
    margin-top:14px;
    padding-top:14px;
    border-top:1px solid var(--line);
}
.p31-history-details p {
    margin:7px 0;
    overflow-wrap:anywhere;
}
.p31-history-toggle {
    margin-top:8px;
}
@media(max-width:650px) {
    .p31-profile-grid {
        grid-template-columns:1fr;
    }
    .p31-profile-hero {
        align-items:flex-start;
    }
    .p31-profile-name {
        font-size:18px;
    }
}
</style>

<!-- Employee profile header -->
<div class="card">
    <div class="p31-profile-hero">
        <div class="p31-profile-identity">
            <div class="p31-profile-avatar" aria-hidden="true">
                <?= e(strtoupper(substr($employeeName ?: 'E', 0, 1))) ?>
            </div>

            <div>
                <h2 class="p31-profile-name">
                    <?= e($employeeName) ?>
                </h2>

                <div class="p31-profile-meta">
                    <?= e($employee['employee_code'] ?? '—') ?>
                    ·
                    <?= e($employee['designation'] ?? '—') ?>
                </div>
            </div>
        </div>

        <span class="badge">
            <?= e($employee['employment_type'] ?? 'Employee') ?>
        </span>
    </div>

    <!-- Personal information -->
    <h3 class="p31-section-title">Personal Information</h3>

    <div class="p31-profile-grid">
        <div class="p31-profile-item">
            <span class="p31-profile-label">Full Name</span>
            <span class="p31-profile-value">
                <?= e($employeeName ?: '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Employee Code</span>
            <span class="p31-profile-value">
                <?= e($employee['employee_code'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Email Address</span>
            <span class="p31-profile-value">
                <?= e($employee['email'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Date of Birth</span>
            <span class="p31-profile-value">
                <?= e($employee['date_of_birth'] ?? '—') ?>
            </span>
        </div>

        <?php if ($isAdmin): ?>
            <div class="p31-profile-item">
                <span class="p31-profile-label">PAN Number</span>
                <span class="p31-profile-value">
                    <?= e($employee['pan'] ?? '—') ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Employment information -->
<div class="card">
    <h3 class="p31-section-title">Employment Information</h3>

    <div class="p31-profile-grid">
        <div class="p31-profile-item">
            <span class="p31-profile-label">Department</span>
            <span class="p31-profile-value">
                <?= e($employee['department'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Designation</span>
            <span class="p31-profile-value">
                <?= e($employee['designation'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Employment Type</span>
            <span class="p31-profile-value">
                <?= e($employee['employment_type'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Joining Date</span>
            <span class="p31-profile-value">
                <?= e($employee['joining_date'] ?? '—') ?>
            </span>
        </div>

        <div class="p31-profile-item">
            <span class="p31-profile-label">Record Created</span>
            <span class="p31-profile-value">
                <?= e($employee['created_at'] ?? '—') ?>
            </span>
        </div>
    </div>

    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= e(module_url()) ?>">
            Back to Employees
        </a>

        <?php if ($isAdmin): ?>
            <a class="btn" href="<?= e(module_url('edit.php?id=' . (int) $id)) ?>">
                Edit Employee
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Service book history -->
<div class="card">
    <div class="card-head">
        <h2 class="p31-section-title">Service Book History</h2>
        <span class="badge"><?= count($entries) ?> Entries</span>
    </div>

    <?php if (!$entries): ?>
        <div class="alert">
            No service-book entries found for this employee.
        </div>
    <?php else: ?>

        <div class="p31-history-list">
            <?php foreach ($entries as $entry): ?>
                <article class="p31-history-item">

                    <div class="p31-history-top">
                        <div>
                            <strong>
                                <?= e($entry['entry_type'] ?? 'Service Entry') ?>
                            </strong>

                            <div class="p31-history-date">
                                Effective Date:
                                <?= e($entry['effective_date'] ?? '—') ?>
                            </div>
                        </div>

                        <span class="badge">
                            #<?= e($entry['id']) ?>
                        </span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline btn-sm service-entry-toggle p31-history-toggle"
                        aria-expanded="false">
                        Show Details
                    </button>

                    <div class="service-entry-details p31-history-details" hidden>

                        <?php if (!empty($entry['previous_designation']) || !empty($entry['new_designation'])): ?>
                            <p>
                                <strong>Designation:</strong>
                                <?= e($entry['previous_designation'] ?: '—') ?>
                                →
                                <?= e($entry['new_designation'] ?: '—') ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($entry['previous_department']) || !empty($entry['new_department'])): ?>
                            <p>
                                <strong>Department:</strong>
                                <?= e($entry['previous_department'] ?: '—') ?>
                                →
                                <?= e($entry['new_department'] ?: '—') ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($entry['remarks'])): ?>
                            <p>
                                <strong>Remarks:</strong>
                                <?= e($entry['remarks']) ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong>Entry ID:</strong>
                            <?= e($entry['id']) ?>
                        </p>

                        <p>
                            <strong>Created At:</strong>
                            <?= e($entry['created_at'] ?? '—') ?>
                        </p>

                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<?php page_end(); ?>