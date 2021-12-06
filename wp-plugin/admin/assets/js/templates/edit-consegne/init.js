jQuery(document).ready(function($) {

    $("table.wp-list-table tbody tr").each(function() {

        var status = $('td[data-colname="Stato"]', this).text(),
            tipoDoc = $('td[data-colname="Tipologia doc"]', this).text(),
            riconsegna = $($('td.column-5e6a4cbf2f53c', this).html()).hasClass("green"),
            ddt = $('td[data-colname="DDT"]', this).text();

        $(this).attr('data-status', status);
        $(this).addClass(associaClasse(status, tipoDoc));
        $(this).attr('data-riconsegna', riconsegna);

        if(ddt.indexOf('/wp-content/uploads/app') != -1) {
            $('td[data-colname="DDT"]', this).html('<button type="button" href="https://apppokersrl.it'+ ddt +'" class="button button-primary button-large" style="margin-top: 10px;">VISUALIZZA</button>');
        }
    });

    $("table.wp-list-table tbody tr td[data-colname='DDT']").on('click', 'button', function() {
        window.open($(this).attr('href'), '_blank', 'location=yes,height=850,width=600,scrollbars=yes,status=yes');
    });
    
    function associaClasse(status, tipoDoc){
        if(status === 'Presa in carico'){
            return 'giallo';
        }else if(status === 'Non Effettuata'){
            return 'rosso';
        }else if(status === 'Pending'){
            return 'grigino';
        }else if(status === 'Effettuata'){
            let arr = ['R', 'T', 'S'];
            return (arr.includes(tipoDoc)) ? 'verdino' : 'verde';
        }
    }

});
