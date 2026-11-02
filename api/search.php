<?php
require __DIR__ . '/../boot.php';
require __DIR__ . '/../includes/functions.php';

if (!current_user()) {
    json_response(['error' => 'Not logged in'], 401);
}

require_role('admin');

$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100);
$like = '%' . $q . '%';

$sql = 'SELECT id, employee_code, full_name, department, designation
        FROM emp_employees
        WHERE employee_code LIKE ?
           OR full_name LIKE ?
           OR department LIKE ?
           OR designation LIKE ?
        ORDER BY full_name ASC
        LIMIT 20';

$st = db()->prepare($sql);
$st->bind_param('ssss', $like, $like, $like, $like);
$st->execute();

$rows = [];
$result = $st->get_result();

while ($employee = $result->fetch_assoc()) {
    $rows[] = [
        'id' => (int) $employee['id'],
        'employee_code' => $employee['employee_code'],
        'full_name' => $employee['full_name'],
        'department' => $employee['department'],
        'designation' => $employee['designation'],
        'path' => 'view.php?id=' . (int) $employee['id']
    ];
}

json_response([
    'count' => count($rows),
    'rows' => $rows
]);