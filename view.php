<?php
/* Example: one record, its workflow, a staff-only status change and an owner-only delete (Units 3 and 4). */
require __DIR__ . '/boot.php';
require __DIR__ . '/includes/functions.php';
require_login();

$id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
$row = find_suggestion($id);
if (!$row) {
    http_response_code(404);
    page_start('Not found');
    echo '<div class="empty"><h2>Suggestion not found</h2><p><a href="' . e(module_url()) . '">Back to the list</a></p></div>';
    page_end();
    exit;
}

if (is_post()) {
    csrf_verify();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'advance' && has_role('staff', 'principal')) {
        // Move one step along the workflow — never skip a stage.
        $pos = array_search($row['status'], SUGGESTION_FLOW, true);
        if ($pos !== false && $pos < count(SUGGESTION_FLOW) - 1) {
            $next = SUGGESTION_FLOW[$pos + 1];
            $reply = trim(isset($_POST['reply']) ? $_POST['reply'] : '');
            $st = db()->prepare('UPDATE example_suggestions SET status = ?, reply = ?, updated_at = NOW() WHERE id = ?');
            $st->bind_param('ssi', $next, $reply, $id);
            $st->execute();
            audit('status:' . $next, 'example_suggestions', $id);
            notify((int) $row['user_id'], 'Your suggestion "' . excerpt($row['title'], 40) . '" is now ' . $next, 'view.php?id=' . $id);
            flash('ok', 'Marked as ' . $next . '.');
        }
    } elseif ($action === 'delete' && (int) $row['user_id'] === user_id() && $row['status'] === 'open') {
        $st = db()->prepare('DELETE FROM example_suggestions WHERE id = ? AND user_id = ?');
        $uid = user_id();
        $st->bind_param('ii', $id, $uid);
        $st->execute();
        audit('delete', 'example_suggestions', $id);
        flash('ok', 'Suggestion deleted.');
        redirect(module_url());
    }
    redirect(module_url('view.php?id=' . $id));
}

$pos = array_search($row['status'], SUGGESTION_FLOW, true);
page_start($row['title'], ['sub' => suggestion_ref($row) . ' · ' . SUGGESTION_CATEGORIES[$row['category']]]);
?>
<div class="card">
  <ol class="timeline">
    <?php foreach (SUGGESTION_FLOW as $i => $s): ?>
      <li class="<?= $i < $pos ? 'done' : ($i === $pos ? 'current' : '') ?>"><?= e(ucfirst($s)) ?></li>
    <?php endforeach; ?>
  </ol>
</div>
<div class="grid grid-2">
  <div class="card">
    <dl class="dl">
      <dt>Submitted by</dt><dd><?= e($row['full_name']) ?></dd>
      <dt>On</dt><dd><?= e(date('d M Y, h:i A', strtotime($row['created_at']))) ?></dd>
      <dt>Status</dt><dd><?= status_badge($row['status']) ?></dd>
      <dt>Reply</dt><dd><?= $row['reply'] ? e($row['reply']) : '<span class="muted">—</span>' ?></dd>
    </dl>
    <h3 style="margin-top:18px">Suggestion</h3>
    <p><?= nl2br(e($row['body'])) ?></p>
  </div>
  <div class="card">
    <?php if (has_role('staff', 'principal') && $pos < count(SUGGESTION_FLOW) - 1): ?>
      <h3>Move to “<?= e(ucfirst(SUGGESTION_FLOW[$pos + 1])) ?>”</h3>
      <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="advance">
        <div class="field"><label for="reply">Reply to the student (optional)</label>
          <textarea id="reply" name="reply"><?= e($row['reply']) ?></textarea></div>
        <button class="btn btn-ok">Mark as <?= e(SUGGESTION_FLOW[$pos + 1]) ?></button>
      </form>
    <?php elseif ((int) $row['user_id'] === user_id() && $row['status'] === 'open'): ?>
      <h3>Changed your mind?</h3>
      <p class="muted">You can delete a suggestion until it is reviewed.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-danger" data-confirm="Delete this suggestion?">Delete</button>
      </form>
    <?php else: ?>
      <p class="muted">No actions available.</p>
    <?php endif; ?>
  </div>
</div>
<?php page_end();
