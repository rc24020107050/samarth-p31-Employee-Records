<?php
/*
 * Samarth: Employee Records & Service Book
 * Module-specific helpers for employee data.
 */
/* Unit 2: Employee string handling */

// Normalize employee code: rc-t-0042 -> RC-T-0042
function normalise_employee_code(string $value): string {
    return strtoupper(preg_replace('/\s+/', '', trim($value)));
}

// Normalize names and standardize titles such as Dr / Prof
function normalise_employee_name(string $value): string {
    $name = preg_replace('/\s+/', ' ', trim($value));

    $titles = [
        'mr'   => 'Mr.',
        'mrs'  => 'Mrs.',
        'ms'   => 'Ms.',
        'dr'   => 'Dr.',
        'prof' => 'Prof.'
    ];

    if (preg_match('/^(Mr|Mrs|Ms|Dr|Prof)\.?\s+/i', $name, $matches)) {
        $key = strtolower(rtrim($matches[1], '.'));
        $name = $titles[$key] . ' ' .
            preg_replace('/^(Mr|Mrs|Ms|Dr|Prof)\.?\s+/i', '', $name);
    }

    return $name;
}

// Normalize and validate PAN
function normalise_pan(string $value): string {
    return strtoupper(trim($value));
}

function is_valid_pan(string $pan): bool {
    return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan) === 1;
}
function validate_employee(array $in)
{
    $data = [
        'employee_code'  => trim($in['employee_code'] ?? ''),
        'title'          => trim($in['title'] ?? 'Mr.'),
        'full_name'      => trim($in['full_name'] ?? ''),
        'pan'            => strtoupper(trim($in['pan'] ?? '')),
        'email'          => trim($in['email'] ?? ''),
        'date_of_birth'  => trim($in['date_of_birth'] ?? ''),
        'department'     => trim($in['department'] ?? ''),
        'designation'    => trim($in['designation'] ?? ''),
        'joining_date'   => trim($in['joining_date'] ?? ''),
        'employment_type'=> trim($in['employment_type'] ?? ''),
    ];

    $errors = [];
    $in['employee_code'] = normalise_employee_code($in['employee_code'] ?? '');
$in['full_name'] = normalise_employee_name($in['full_name'] ?? '');
$in['pan'] = normalise_pan($in['pan'] ?? '');

    if (!preg_match('/^RC-[TN]-\d{4}$/', $data['employee_code'])) {
        $errors['employee_code'] = 'Use employee code format RC-T-0042 or RC-N-0042.';
    }

    if (!in_array($data['title'], ['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.'], true)) {
        $errors['title'] = 'Choose a valid title.';
    }

    if ($data['full_name'] === '' || mb_strlen($data['full_name']) > 120) {
        $errors['full_name'] = 'Enter a name (maximum 120 characters).';
    }

    if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $data['pan'])) {
        $errors['pan'] = 'Enter a valid PAN format, e.g. ABCDE1234F.';
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    foreach (['date_of_birth', 'joining_date'] as $field) {
        $date = DateTime::createFromFormat('!Y-m-d', $data[$field]);
        if (!$date || $date->format('Y-m-d') !== $data[$field]) {
            $errors[$field] = 'Enter a valid date.';
        }
    }

    if (!isset($errors['date_of_birth']) && !isset($errors['joining_date'])) {
        $dob = new DateTime($data['date_of_birth']);
        $joining = new DateTime($data['joining_date']);
        $today = new DateTime('today');

        if ($dob >= $today) {
            $errors['date_of_birth'] = 'Date of birth must be in the past.';
        }

        if ($joining > $today) {
            $errors['joining_date'] = 'Joining date cannot be in the future.';
        }

        if ($joining <= $dob) {
            $errors['joining_date'] = 'Joining date must be after date of birth.';
        }
    }

    if ($data['department'] === '' || mb_strlen($data['department']) > 100) {
        $errors['department'] = 'Enter a department (maximum 100 characters).';
    }

    if ($data['designation'] === '' || mb_strlen($data['designation']) > 100) {
        $errors['designation'] = 'Enter a designation (maximum 100 characters).';
    }

    if (!in_array($data['employment_type'], ['Teaching', 'Non-Teaching'], true)) {
        $errors['employment_type'] = 'Choose Teaching or Non-Teaching.';
    }

    return [$data, $errors];
}

function find_employee($id)
{
    $st = db()->prepare('SELECT * FROM emp_employees WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();

    return $st->get_result()->fetch_assoc() ?: null;
}
function get_my_module_employee_id(): int
{
    $uid = user_id();

    if (!$uid) {
        return 0;
    }

    $st = db()->prepare(
        'SELECT id FROM emp_employees WHERE user_id = ? LIMIT 1'
    );

    $st->bind_param('i', $uid);
    $st->execute();

    $row = $st->get_result()->fetch_assoc();

    return $row ? (int) $row['id'] : 0;
}