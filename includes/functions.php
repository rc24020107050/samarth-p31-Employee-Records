<?php
/*
 * Your module's own functions. Keep SQL and string handling here so pages stay short.
 * (Example module: Suggestion Box — replace with your own.)
 */

const SUGGESTION_CATEGORIES = [
    'academics' => 'Academics', 'campus' => 'Campus', 'canteen' => 'Canteen',
    'library' => 'Library', 'it' => 'IT / Wi-Fi', 'other' => 'Other',
];
const SUGGESTION_FLOW = ['open', 'reviewed', 'closed'];   // the workflow, in order

/** Unit 2 — string handling: a short preview of a long text, cut at a word boundary. */
function excerpt($text, $max = 90)
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (strlen($text) <= $max) {
        return $text;
    }
    $cut = substr($text, 0, $max);
    $space = strrpos($cut, ' ');
    return rtrim(substr($cut, 0, $space ?: $max), ' ,.;') . '…';
}

/** Unit 2 — reference number like SUG-2026-0007 from the row id and date. */
function suggestion_ref(array $row)
{
    return 'SUG-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
}

/** Unit 3 — validate the form. Returns [clean data, errors]. */
function validate_suggestion(array $in)
{
    $data = [
        'category' => isset($in['category']) ? $in['category'] : '',
        'title'    => trim(isset($in['title']) ? $in['title'] : ''),
        'body'     => trim(isset($in['body']) ? $in['body'] : ''),
    ];
    $errors = [];
    if (!isset(SUGGESTION_CATEGORIES[$data['category']])) {
        $errors['category'] = 'Choose a category.';
    }
    if (strlen($data['title']) < 8 || strlen($data['title']) > 120) {
        $errors['title'] = 'Title must be 8 to 120 characters.';
    }
    if (str_word_count($data['body']) < 5) {
        $errors['body'] = 'Describe the suggestion in at least 5 words.';
    }
    return [$data, $errors];
}

/** Unit 4 — one suggestion, only if the user may see it (owner or staff). Returns null otherwise. */
function find_suggestion($id)
{
    $st = db()->prepare('SELECT s.*, u.full_name FROM example_suggestions s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }
    if ((int) $row['user_id'] !== user_id() && !has_role('staff', 'principal')) {
        return null;   // not yours: behave exactly as if it did not exist (no IDOR)
    }
    return $row;
}

/** Status counts for the stat tiles and the AJAX endpoint. */
function suggestion_counts()
{
    $counts = array_fill_keys(SUGGESTION_FLOW, 0);
    $res = db()->query('SELECT status, COUNT(*) AS n FROM example_suggestions GROUP BY status');
    while ($r = $res->fetch_assoc()) {
        $counts[$r['status']] = (int) $r['n'];
    }
    return $counts;
}
