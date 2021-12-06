jQuery(document).ready(function ($) {

    $("table.wp-list-table tbody tr").each(function() {

        var status = $('td[data-colname="Stato"]', this).text();

        $(this).attr('data-status', status);
    });

});