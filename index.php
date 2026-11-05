<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();
require_role('admin');
$search = trim($_GET['search'] ?? '');
$department = trim($_GET['department'] ?? '');
$designation = trim($_GET['designation'] ?? '');

$sql = 'SELECT
            e.id,
            e.employee_code,
            e.title,
            e.full_name,
            COALESCE(d.name, e.department) AS department,
            e.designation,
            e.employment_type,
            e.joining_date
        FROM emp_employees e
        LEFT JOIN departments d
            ON TRIM(LOWER(e.department)) = TRIM(LOWER(d.name))
        WHERE 1=1';

$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (employee_code LIKE ? OR full_name LIKE ?)';
    $term = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $term;
    $params[] = $term;
}

if ($department !== '') {
    $sql .= ' AND department = ?';
    $types .= 's';
    $params[] = $department;
}

if ($designation !== '') {
    $sql .= ' AND designation = ?';
    $types .= 's';
    $params[] = $designation;
}

$sql .= ' ORDER BY full_name ASC';

$st = db()->prepare($sql);

if ($types !== '') {
    $st->bind_param($types, ...$params);
}

$st->execute();
$employees = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$departments = db()->query(
    'SELECT DISTINCT department FROM emp_employees ORDER BY department'
)->fetch_all(MYSQLI_ASSOC);

$designations = db()->query(
    'SELECT DISTINCT designation FROM emp_employees ORDER BY designation'
)->fetch_all(MYSQLI_ASSOC);

page_start('Employee Records', [
    'sub' => 'Search and manage teaching and non-teaching employee records.'
]);
?>

<div class="card">
    <form method="get" class="form">
        <div class="grid grid-2">
            <div class="field">
                <label for="search">Search by name or employee code</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="e.g. Aarav Sharma or RC-T-0042"
                >
            </div>

            <div class="field">
                <label for="department">Department</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $item): ?>
                        <option
                            value="<?= e($item['department']) ?>"
                            <?= $department === $item['department'] ? 'selected' : '' ?>
                        >
                            <?= e($item['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="designation">Designation</label>
                <select id="designation" name="designation">
                    <option value="">All Designations</option>
                    <?php foreach ($designations as $item): ?>
                        <option
                            value="<?= e($item['designation']) ?>"
                            <?= $designation === $item['designation'] ? 'selected' : '' ?>
                        >
                            <?= e($item['designation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-row">
            <button class="btn" type="submit">Search Employees</button>
            <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Clear Filters</a>
            <a class="btn" href="<?= e(module_url('add.php')) ?>">Add Employee</a>
            <a class="btn" href="<?= e(module_url('service_book.php')) ?>">
    Add Service Book Entry
</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Employee Records (<?= count($employees) ?>)</h2>

    <?php if (!$employees): ?>
        <p>No employees found. Try different search filters.</p>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Type</th>
                        <th>Joining Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?= e($employee['employee_code']) ?></td>
                            <td><?= e($employee['title'] . ' ' . $employee['full_name']) ?></td>
                            <td><?= e($employee['department']) ?></td>
                            <td><?= e($employee['designation']) ?></td>
                            <td><?= e($employee['employment_type']) ?></td>
                            <td><?= e($employee['joining_date']) ?></td>
                            <td>
                                <a href="<?= e(module_url('view.php?id=' . $employee['id'])) ?>">
                                    View Details
                                </a>
                                <br>
                                <a href="<?= e(module_url('edit.php?id=' . $employee['id'])) ?>">
                                 Edit
                                </a>
                                <form method="post"
                                   action="<?= e(module_url('delete.php')) ?>"
                                   onsubmit="return confirm('Are you sure you want to delete this employee? Their service-book history will also be deleted.');">
                                 <?= csrf_field() ?>
                                 <input type="hidden" name="id" value="<?= (int) $employee['id'] ?>">
                                 <button class="btn btn-ghost" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php page_end(); ?>