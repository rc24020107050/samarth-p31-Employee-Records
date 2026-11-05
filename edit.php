<?php
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();
require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    flash('error', 'Invalid employee ID.');
    redirect(module_url());
}

$employee = find_employee($id);

if (!$employee) {
    flash('error', 'Employee record not found.');
    redirect(module_url());
}

$data = $employee;
$errors = [];

if (is_post()) {
    csrf_verify();
    $input = $_POST;

$input['employee_code'] = normalise_employee_code($input['employee_code'] ?? '');
$input['full_name'] = normalise_employee_name($input['full_name'] ?? '');
$input['pan'] = normalise_pan($input['pan'] ?? '');

list($data, $errors) = validate_employee($input);

    if (!$errors) {
        $sql = 'UPDATE emp_employees SET
            employee_code = ?, title = ?, full_name = ?, pan = ?,
            email = ?, date_of_birth = ?, department = ?,
            designation = ?, joining_date = ?, employment_type = ?
            WHERE id = ?';

        $st = db()->prepare($sql);
        $st->bind_param(
            'ssssssssssi',
            $data['employee_code'],
            $data['title'],
            $data['full_name'],
            $data['pan'],
            $data['email'],
            $data['date_of_birth'],
            $data['department'],
            $data['designation'],
            $data['joining_date'],
            $data['employment_type'],
            $id
        );

        try {
            $st->execute();
            audit('update', 'emp_employees', $id);
            flash('ok', 'Employee updated successfully.');
            redirect(module_url('view.php?id=' . $id));
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                $errors['duplicate'] =
                    'Employee code, PAN or email already exists.';
            } else {
                throw $e;
            }
        }
    }
}

page_start('Edit Employee', [
    'sub' => 'Update employee details and appointment information.'
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
          value="<?= e($data['employee_code']) ?>">
        <?php if (isset($errors['employee_code'])): ?>
          <div class="field-error"><?= e($errors['employee_code']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="title">Title *</label>
        <select id="title" name="title" required>
          <?php foreach (['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.'] as $title): ?>
            <option value="<?= e($title) ?>"
              <?= $data['title'] === $title ? 'selected' : '' ?>>
              <?= e($title) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['title'])): ?>
          <div class="field-error"><?= e($errors['title']) ?></div>
        <?php endif; ?>
      </div>

      <?php
      $fields = [
          'full_name' => ['Full Name', 120],
          'pan' => ['PAN', 10],
          'email' => ['Email', 150],
          'date_of_birth' => ['Date of Birth', null],
          'department' => ['Department', 100],
          'designation' => ['Designation', 100],
          'joining_date' => ['Joining Date', null],
      ];
      foreach ($fields as $name => [$label, $maxLength]):
          $type = in_array($name, ['date_of_birth', 'joining_date'], true)
              ? 'date' : ($name === 'email' ? 'email' : 'text');
      ?>
        <div class="field">
          <label for="<?= e($name) ?>"><?= e($label) ?> *</label>
          <input type="<?= e($type) ?>" id="<?= e($name) ?>"
            name="<?= e($name) ?>" required
            <?= $maxLength ? 'maxlength="' . (int) $maxLength . '"' : '' ?>
            value="<?= e($data[$name] ?? '') ?>">
          <?php if (isset($errors[$name])): ?>
            <div class="field-error"><?= e($errors[$name]) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="field">
        <label for="employment_type">Employee Type *</label>
        <select id="employment_type" name="employment_type" required>
          <?php foreach (['Teaching', 'Non-Teaching'] as $type): ?>
            <option value="<?= e($type) ?>"
              <?= $data['employment_type'] === $type ? 'selected' : '' ?>>
              <?= e($type) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['employment_type'])): ?>
          <div class="field-error"><?= e($errors['employment_type']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="btn-row">
      <button class="btn" type="submit">Update Employee</button>
      <a class="btn btn-ghost"
        href="<?= e(module_url('view.php?id=' . $id)) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php page_end(); ?>