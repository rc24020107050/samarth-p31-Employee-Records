/* Your module's jQuery (Unit 5). Loaded on every page of your module, after jQuery and shared/portal.js.
   Example: live search on the list page, and a word counter on the form. */
$(function () {
  // ---- Live search: ask api/search.php for JSON and redraw the table without reloading ----
  var $q = $('#q'), $status = $('#status'), timer = null;

  function esc(s) { return $('<div>').text(s).html(); }   // escape text before putting it into HTML

  function search() {
    $.getJSON(RC.base + 'api/search.php', { q: $q.val(), status: $status.val() }, function (data) {
      var html = $.map(data.rows, function (r) {
        return '<tr><td class="mono small">' + esc(r.ref) + '</td>' +
               '<td><b>' + esc(r.title) + '</b><div class="small muted">' + esc(r.excerpt) + '</div></td>' +
               '<td>' + esc(r.category) + '</td><td>' + esc(r.by) + '</td><td>' + r.badge + '</td>' +
               '<td class="actions"><a class="btn btn-sm btn-outline" href="' + esc(RC.base + r.path) + '">Open</a></td></tr>';
      }).join('');
      $('#rows').html(html);
      $('#none').remove();
      if (data.count === 0) { $('#rows').closest('.table-wrap').append('<div class="empty" id="none">No suggestions match.</div>'); }
      $.each(data.counts, function (status, n) { $('[data-count="' + status + '"]').text(n); });
    });
  }

  if ($('#filter').length) {
    $q.on('input', function () { clearTimeout(timer); timer = setTimeout(search, 250); });   // wait until typing pauses
    $status.on('change', search);
  }

  // ---- Word counter on the form ----
  $('#body').on('input', function () {
    var words = $.trim($(this).val()).split(/\s+/).filter(Boolean).length;
    $('#words').text(words).css('color', words < 5 ? 'var(--bad)' : 'var(--ok)');
  }).trigger('input');
});
