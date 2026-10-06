<?php
/* Example module page: list + GET search/filter (Unit 3), prepared SELECT (Unit 4), live search via AJAX (Unit 5). */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

// GET filters — they belong in the URL, so the filtered list can be bookmarked or shared.
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$status = isset($_GET['status']) && in_array($_GET['status'], SUGGESTION_FLOW, true) ? $_GET['status'] : '';

$sql = 'SELECT s.id, s.category, s.title, s.body, s.status, s.created_at, u.full_name
        FROM example_suggestions s JOIN users u ON u.id = s.user_id WHERE 1 = 1';
$types = '';
$params = [];
if (!has_role('staff', 'principal')) {           // students see only their own
    $sql .= ' AND s.user_id = ?';
    $types .= 'i';
    $params[] = user_id();
}
if ($q !== '') {
    $sql .= ' AND (s.title LIKE ? OR s.body LIKE ?)';
    $types .= 'ss';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
if ($status !== '') {
    $sql .= ' AND s.status = ?';
    $types .= 's';
    $params[] = $status;
}
$sql .= ' ORDER BY s.created_at DESC';

$st = db()->prepare($sql);
if ($types !== '') {
    $st->bind_param($types, ...$params);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$counts = suggestion_counts();

page_start('Suggestions', [
    'sub' => 'Ideas from students and staff for improving the college.',
    'actions' => '<a class="btn" href="' . e(module_url('add.php')) . '">+ New suggestion</a>',
]);
?>
<div class="grid grid-3" style="margin-bottom:18px" id="tiles">
  <div class="stat wait"><div class="n" data-count="open"><?= $counts['open'] ?></div><div class="l">Open</div></div>
  <div class="stat"><div class="n" data-count="reviewed"><?= $counts['reviewed'] ?></div><div class="l">Reviewed</div></div>
  <div class="stat ok"><div class="n" data-count="closed"><?= $counts['closed'] ?></div><div class="l">Closed</div></div>
</div>

<form method="get" class="toolbar" id="filter">
  <input type="search" name="q" id="q" value="<?= e($q) ?>" placeholder="Search title or text…" autocomplete="off">
  <select name="status" id="status">
    <option value="">Any status</option>
    <?php foreach (SUGGESTION_FLOW as $s): ?>
      <option value="<?= e($s) ?>"<?= $s === $status ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-outline">Filter</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Ref</th><th>Suggestion</th><th>Category</th><th>By</th><th>Status</th><th></th></tr></thead>
    <tbody id="rows">
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="mono small"><?= e(suggestion_ref($r)) ?></td>
        <td><b><?= e($r['title']) ?></b><div class="small muted"><?= e(excerpt($r['body'])) ?></div></td>
        <td><?= e(SUGGESTION_CATEGORIES[$r['category']]) ?></td>
        <td><?= e($r['full_name']) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="actions"><a class="btn btn-sm btn-outline" href="<?= e(module_url('view.php?id=' . $r['id'])) ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$rows): ?><div class="empty" id="none">No suggestions match.</div><?php endif; ?>
</div>
<?php page_end();
