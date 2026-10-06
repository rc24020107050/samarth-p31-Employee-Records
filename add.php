<?php
/* Example: POST form with server-side validation, sticky values, CSRF and Post/Redirect/Get (Units 3 and 4). */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

$data = ['category' => '', 'title' => '', 'body' => ''];
$errors = [];

if (is_post()) {
    csrf_verify();
    list($data, $errors) = validate_suggestion($_POST);
    if (!$errors) {
        $uid = user_id();
        $st = db()->prepare('INSERT INTO example_suggestions (user_id, category, title, body) VALUES (?, ?, ?, ?)');
        $st->bind_param('isss', $uid, $data['category'], $data['title'], $data['body']);
        $st->execute();
        $id = $st->insert_id;
        audit('create', 'example_suggestions', $id);
        flash('ok', 'Thank you — your suggestion has been recorded.');
        redirect(module_url('view.php?id=' . $id));
    }
}

page_start('New suggestion');
?>
<div class="card">
  <form method="post" class="form" novalidate>
    <?= csrf_field() ?>
    <div class="field<?= isset($errors['category']) ? ' has-error' : '' ?>">
      <label for="category">Category <span class="req">*</span></label>
      <select id="category" name="category">
        <option value="">Choose…</option>
        <?php foreach (SUGGESTION_CATEGORIES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $k === $data['category'] ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['category'])): ?><div class="field-error"><?= e($errors['category']) ?></div><?php endif; ?>
    </div>
    <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
      <label for="title">Title <span class="req">*</span></label>
      <input type="text" id="title" name="title" maxlength="120" value="<?= e($data['title']) ?>">
      <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
    </div>
    <div class="field<?= isset($errors['body']) ? ' has-error' : '' ?>">
      <label for="body">Your suggestion <span class="req">*</span></label>
      <textarea id="body" name="body"><?= e($data['body']) ?></textarea>
      <div class="help"><span id="words">0</span> words</div>
      <?php if (isset($errors['body'])): ?><div class="field-error"><?= e($errors['body']) ?></div><?php endif; ?>
    </div>
    <div class="btn-row">
      <button class="btn" type="submit">Submit</button>
      <a class="btn btn-ghost" href="<?= e(module_url()) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php page_end();
