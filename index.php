<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

// Safely read scalar GET parameters.
$getValue = static function ($key) {
    $value = $_GET[$key] ?? '';

    return is_string($value) ? trim($value) : '';
};

$search = $getValue('search');
$department = $getValue('department');
$designation = $getValue('designation');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

$perPage = 10;

// Shared filter conditions.
$where = ' WHERE 1=1';
$types = '';
$params = [];

if ($search !== '') {
    $where .= ' AND (e.employee_code LIKE ? OR e.full_name LIKE ?)';
    $term = '%' . $search . '%';

    $types .= 'ss';
    $params[] = $term;
    $params[] = $term;
}

if ($department !== '') {
    $where .= ' AND COALESCE(d.name, e.department) = ?';
    $types .= 's';
    $params[] = $department;
}

if ($designation !== '') {
    $where .= ' AND e.designation = ?';
    $types .= 's';
    $params[] = $designation;
}

// Helper to bind a dynamic number of parameters safely.
$bindDynamic = static function ($statement, $types, &$params) {
    if ($types === '') {
        return;
    }

    $arguments = [$types];

    foreach ($params as $key => &$value) {
        $arguments[] = &$value;
    }
    unset($value);

    call_user_func_array(
        [$statement, 'bind_param'],
        $arguments
    );
};

// Count matching records.
$countSql = 'SELECT COUNT(*) AS total
             FROM emp_employees e
             LEFT JOIN departments d
                ON TRIM(LOWER(e.department)) = TRIM(LOWER(d.name))'
             . $where;

$countStatement = db()->prepare($countSql);

$countParams = $params;
$bindDynamic($countStatement, $types, $countParams);

$countStatement->execute();

$totalEmployees = (int) $countStatement
    ->get_result()
    ->fetch_assoc()['total'];

$totalPages = max(1, (int) ceil($totalEmployees / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// Fetch only the current page.
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
            ON TRIM(LOWER(e.department)) = TRIM(LOWER(d.name))'
        . $where .
        ' ORDER BY e.full_name ASC, e.id ASC
          LIMIT ? OFFSET ?';

$st = db()->prepare($sql);

$dataParams = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;

$bindDynamic($st, $types . 'ii', $dataParams);

$st->execute();

$employees = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// Department filter options use the same display value as the table.
$departments = db()->query(
    'SELECT DISTINCT COALESCE(d.name, e.department) AS department
     FROM emp_employees e
     LEFT JOIN departments d
        ON TRIM(LOWER(e.department)) = TRIM(LOWER(d.name))
     WHERE e.department IS NOT NULL
       AND e.department != ""
     ORDER BY department'
)->fetch_all(MYSQLI_ASSOC);

// Designation filter options.
$designations = db()->query(
    'SELECT DISTINCT designation
     FROM emp_employees
     WHERE designation IS NOT NULL
       AND designation != ""
     ORDER BY designation'
)->fetch_all(MYSQLI_ASSOC);

// Preserve current filters in pagination links.
$paginationQuery = [
    'search' => $search,
    'department' => $department,
    'designation' => $designation,
];

$paginationUrl = static function ($targetPage) use ($paginationQuery) {
    $query = $paginationQuery;
    $query['page'] = $targetPage;

    return module_url('index.php?' . http_build_query($query));
};

$startRecord = $totalEmployees > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $perPage, $totalEmployees);

page_start('Employee Records', [
    'sub' => 'Search and manage teaching and non-teaching employee records.'
]);
?>

<!-- Search and filters -->
<div class="card">
    <div class="card-head">
        <h2>Find Employees</h2>
        <span class="badge">P31 Employee Management</span>
    </div>

    <form method="get" class="form p31-filter-form">

        <div class="p31-filter-grid">

            <div class="field">
                <label for="search">Search by Name or Employee Code</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Enter employee name or code">
                <div class="help">
                    Search using employee name or unique employee code.
                </div>
            </div>

            <div class="field">
                <label for="department">Department</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>

                    <?php foreach ($departments as $item): ?>
                        <option
                            value="<?= e($item['department']) ?>"
                            <?= $department === $item['department'] ? 'selected' : '' ?>>
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
                            <?= $designation === $item['designation'] ? 'selected' : '' ?>>
                            <?= e($item['designation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="btn-row">
            <button class="btn" type="submit">
                Search Employees
            </button>

            <a class="btn btn-outline" href="<?= e(module_url()) ?>">
                Clear Filters
            </a>

            <a class="btn btn-ok" href="<?= e(module_url('add.php')) ?>">
                + Add Employee
            </a>

            <a class="btn btn-outline" href="<?= e(module_url('service_book.php')) ?>">
                Service Book Entry
            </a>
        </div>
    </form>
</div>

<!-- Employee records -->
<div class="card">

    <div class="card-head">
        <div>
            <h2>Employee Records</h2>
            <p class="muted small">
                Showing <?= $startRecord ?>–<?= $endRecord ?>
                of <?= $totalEmployees ?> employees.
            </p>
        </div>

        <span class="badge">
            <?= $totalEmployees ?> Employees
        </span>
    </div>

    <?php if (!$employees): ?>

        <div class="empty">
            <h2>No Employees Found</h2>
            <p>Try changing your search keywords or filters.</p>

            <a class="btn" href="<?= e(module_url()) ?>">
                Reset Search
            </a>
        </div>

    <?php else: ?>

        <div class="p31-table-wrap">
            <table class="table p31-table">

                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Employment Type</th>
                        <th>Joining Date</th>
                        <th class="p31-action-cell">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <strong class="mono">
                                    <?= e($employee['employee_code']) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= e($employee['title'] . ' ' . $employee['full_name']) ?>
                                </strong>
                            </td>

                            <td><?= e($employee['department']) ?></td>
                            <td><?= e($employee['designation']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($employee['employment_type']) ?>
                                </span>
                            </td>

                            <td><?= e($employee['joining_date']) ?></td>

                            <td class="p31-action-cell">
                                <div class="p31-actions">

                                    <a
                                        class="btn btn-outline btn-sm"
                                        href="<?= e(module_url('view.php?id=' . (int) $employee['id'])) ?>">
                                        View
                                    </a>

                                    <a
                                        class="btn btn-sm"
                                        href="<?= e(module_url('edit.php?id=' . (int) $employee['id'])) ?>">
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="<?= e(module_url('delete.php')) ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this employee? This action may also affect associated service-book history.');">

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $employee['id'] ?>">

                                        <button
                                            class="btn btn-danger btn-sm"
                                            type="submit">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="btn-row" style="justify-content:center; margin-top:20px;">

                <?php if ($page > 1): ?>
                    <a
                        class="btn btn-outline"
                        href="<?= e($paginationUrl($page - 1)) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <span class="badge">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>

                <?php if ($page < $totalPages): ?>
                    <a
                        class="btn btn-outline"
                        href="<?= e($paginationUrl($page + 1)) ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php page_end(); ?>