<?php
/* Example JSON endpoint for jQuery (Unit 5): GET api/search.php?q=wifi&status=open */
require __DIR__ . '/../boot.php';
require __DIR__ . '/../includes/functions.php';

if (!current_user()) {
    json_response(['error' => 'Not logged in'], 401);
}

$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$status = isset($_GET['status']) && in_array($_GET['status'], SUGGESTION_FLOW, true) ? $_GET['status'] : '';
$like = '%' . $q . '%';
$mine = has_role('staff', 'principal') ? 0 : user_id();   // 0 = everyone's

$st = db()->prepare(
    'SELECT s.id, s.category, s.title, s.body, s.status, s.created_at, u.full_name
     FROM example_suggestions s JOIN users u ON u.id = s.user_id
     WHERE (? = 0 OR s.user_id = ?) AND (s.title LIKE ? OR s.body LIKE ?) AND (? = \'\' OR s.status = ?)
     ORDER BY s.created_at DESC LIMIT 50'
);
$st->bind_param('iissss', $mine, $mine, $like, $like, $status, $status);
$st->execute();

$out = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $out[] = [
        'id'       => (int) $r['id'],
        'ref'      => suggestion_ref($r),
        'title'    => $r['title'],
        'excerpt'  => excerpt($r['body']),
        'category' => SUGGESTION_CATEGORIES[$r['category']],
        'by'       => $r['full_name'],
        'status'   => $r['status'],
        'badge'    => status_badge($r['status']),     // already-escaped HTML from the shared helper
        'path'     => 'view.php?id=' . $r['id'],   // relative to the module folder; JS adds RC.base
    ];
}
json_response(['count' => count($out), 'rows' => $out, 'counts' => suggestion_counts()]);
