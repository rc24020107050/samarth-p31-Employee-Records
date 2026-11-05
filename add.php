<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();
require_role('admin');

$data = [
    'employee_code' => '',
    'title' => 'Mr.',
    'full_name' => '',
    'pan' => '',
    'email' => '',
    'date_of_birth' => '',
    'department' => '',
    'designation' => '',
    'joining_date' => '',
    'employment_type' => 'Teaching',
];
$errors = [];

if (is_post()) {
    csrf_verify();
    $input = $_POST;

$input['employee_code'] = normalise_employee_code($input['employee_code'] ?? '');
$input['full_name'] = normalise_employee_name($input['full_name'] ?? '');
$input['pan'] = normalise_pan($input['pan'] ?? '');

list($data, $errors) = validate_employee($input);

    if (!$errors) {
        $sql = 'INSERT INTO emp_employees
            (employee_code, title, full_name, pan, email, date_of_birth,
             department, designation, joining_date, employment_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $st = db()->prepare($sql);
        $st->bind_param(
            'ssssssssss',
            $data['employee_code'],
            $data['title'],
            $data['full_name'],
            $data['pan'],
            $data['email'],
            $data['date_of_birth'],
            $data['department'],
            $data['designation'],
            $data['joining_date'],
            $data['employment_type']
        );

        try {
            $st->execute();
            $id = $st->insert_id;
            audit('create', 'emp_employees', $id);
            flash('ok', 'Employee added successfully.');
            redirect(module_url('view.php?id=' . $id));
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $errors['duplicate'] = 'Employee code, PAN or email already exists.';
            } else {
                throw $e;
            }
        }
    }
}

page_start('Add Employee', [
    'sub' => 'Enter the employee details and appointment information.'
]);
?>
<div class="card">
  <?php if (isset($errors['duplicate'])): ?>
    <div class="field-error"><?= e($errors['duplicate']) ?></div>
  <?php endif; ?>

  <form method="post" class="form">
    <?= csrf_field() ?>

    <div class="grid grid-2">
      <div class="field">
        <label for="employee_code">Employee Code *</label>
        <input id="employee_code" name="employee_code" required
          placeholder="RC-T-0042" value="<?= e($data['employee_code']) ?>">
        <?php if (isset($errors['employee_code'])): ?><div class="field-error"><?= e($errors['employee_code']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="title">Title *</label>
        <select id="title" name="title" required>
          <?php foreach (['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.'] as $title): ?>
            <option value="<?= e($title) ?>"<?= $data['title'] === $title ? ' selected' : '' ?>><?= e($title) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="full_name">Full Name *</label>
        <input id="full_name" name="full_name" maxlength="120" required
          value="<?= e($data['full_name']) ?>">
        <?php if (isset($errors['full_name'])): ?><div class="field-error"><?= e($errors['full_name']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="pan">PAN *</label>
        <input id="pan" name="pan" maxlength="10" required
          placeholder="ABCDE1234F" value="<?= e($data['pan']) ?>">
        <?php if (isset($errors['pan'])): ?><div class="field-error"><?= e($errors['pan']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" maxlength="150" required
          value="<?= e($data['email']) ?>">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="date_of_birth">Date of Birth *</label>
        <input type="date" id="date_of_birth" name="date_of_birth" required
          value="<?= e($data['date_of_birth']) ?>">
        <?php if (isset($errors['date_of_birth'])): ?><div class="field-error"><?= e($errors['date_of_birth']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="department">Department *</label>
        <input id="department" name="department" maxlength="100" required
          value="<?= e($data['department']) ?>">
        <?php if (isset($errors['department'])): ?><div class="field-error"><?= e($errors['department']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="designation">Designation *</label>
        <input id="designation" name="designation" maxlength="100" required
          value="<?= e($data['designation']) ?>">
        <?php if (isset($errors['designation'])): ?><div class="field-error"><?= e($errors['designation']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="joining_date">Joining Date *</label>
        <input type="date" id="joining_date" name="joining_date" required
          value="<?= e($data['joining_date']) ?>">
        <?php if (isset($errors['joining_date'])): ?><div class="field-error"><?= e($errors['joining_date']) ?></div><?php endif; ?>
      </div>

      <div class="field">
        <label for="employment_type">Employee Type *</label>
        <select id="employment_type" name="employment_type" required>
          <option value="Teaching"<?= $data['employment_type'] === 'Teaching' ? ' selected' : '' ?>>Teaching</option>
          <option value="Non-Teaching"<?= $data['employment_type'] === 'Non-Teaching' ? ' selected' : '' ?>>Non-Teaching</option>
        </select>
        <?php if (isset($errors['employment_type'])): ?><div class="field-error"><?= e($errors['employment_type']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="btn-row">
      <button class="btn" type="submit">Save Employee</button>
      <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php page_end(); ?>