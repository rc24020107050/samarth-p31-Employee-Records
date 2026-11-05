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
/* P31 Employee Records: AJAX Live Search */
$(function () {
    var $search = $('#search');

    // Run only on the Employee Records page
    if (!$search.length) return;

    var timer = null;
    var $suggestions = $(
        '<div id="employee-search-suggestions" ' +
        'class="card" style="display:none; margin-top:8px;">' +
        '</div>'
    );

    $search.closest('.field').append($suggestions);

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    $search.on('input', function () {
        clearTimeout(timer);

        timer = setTimeout(function () {
            var query = $.trim($search.val());

            if (query.length < 2) {
                $suggestions.empty().hide();
                return;
            }

            $.getJSON(RC.base + 'api/search.php', { q: query })
                .done(function (data) {
                    var html = '';

                    $.each(data.rows, function (_, employee) {
                        html +=
                            '<div style="padding:8px 0;">' +
                            '<a href="' + escapeHtml(RC.base) +
'view.php?id=' +
encodeURIComponent(employee.id) + '">' +
                            '<strong>' +
                            escapeHtml(employee.full_name) +
                            '</strong></a>' +
                            '<div class="small">' +
                            escapeHtml(employee.employee_code) +
                            ' · ' +
                            escapeHtml(employee.department) +
                            ' · ' +
                            escapeHtml(employee.designation) +
                            '</div></div>';
                    });

                    if (!html) {
                        html = '<p>No matching employees found.</p>';
                    }

                    $suggestions.html(html).show();
                })
                .fail(function () {
                    $suggestions.empty().hide();
                });
        }, 300);
    });

    $search.on('blur', function () {
        setTimeout(function () {
            $suggestions.hide();
        }, 200);
    });

    $search.on('focus', function () {
        if ($suggestions.children().length) {
            $suggestions.show();
        }
    });
});
// Service Book History: Expand / Collapse
$(document).on('click', '.service-entry-toggle', function () {
    const button = $(this);
    const details = button.closest('.card').find('.service-entry-details').first();

    const isHidden = details.prop('hidden');

    details.prop('hidden', !isHidden);

    button.text(isHidden ? 'Hide Details' : 'Show Details');
});