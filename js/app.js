
/* P31 Employee Records & Service Book
   Module-specific jQuery functionality.
*/

// Employee AJAX Live Search
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
    var button = $(this);
    var details = button.closest('.card')
        .find('.service-entry-details').first();

    var isHidden = details.prop('hidden');

    details.prop('hidden', !isHidden);

    button.text(isHidden ? 'Hide Details' : 'Show Details');
    button.attr('aria-expanded', isHidden ? 'true' : 'false');
});
