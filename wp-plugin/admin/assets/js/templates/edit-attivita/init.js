jQuery(document).ready(function($) {

    $("table.wp-list-table tbody tr").each(function() {

        var status = $('td[data-colname="Stato"]', this).text(),
            date = moment($('td[data-colname="Data di scadenza"]', this).text(), 'DD/MM/YYYY');

        $(this).attr('data-status', status);

        if(status != 'Effettuata') {

            if(moment().startOf('day').diff(date, 'days', true) > -3) $(this).attr('data-priority', 1);
            else $(this).attr('data-priority', 2);

        }
    });

});