<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';

require_login();
require_role('admin');

if (!is_post()) {
    header('Location: ' . module_url());
    exit;
}

csrf_verify();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: ' . module_url());
    exit;
}

$employee = find_employee($id);

if (!$employee) {
    header('Location: ' . module_url());
    exit;
}

$conn = db();

$conn->begin_transaction();

try {
    $st = $conn->prepare(
        'DELETE FROM emp_employees WHERE id = ?'
    );

    $st->bind_param('i', $id);
    $st->execute();

    audit(
        'delete',
        'emp_employees',
        $id,
        'Deleted employee: ' . $employee['employee_code']
        . ' - ' . $employee['full_name']
    );

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

flash('ok', 'Employee deleted successfully.');

header('Location: ' . module_url());
exit;