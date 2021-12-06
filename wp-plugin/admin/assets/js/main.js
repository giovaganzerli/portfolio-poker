jQuery(document).ready(function($) {
    
    let field_ddt = $('.acf-field[data-name=allegato-ddt]');
    let field_ddt_firmato = $('.acf-field[data-name=allegato-ddt_firmato]');
    let field_firma = $('.acf-field[data-name=allegato-firma]');
    
    function updateDDTButton() {
        
        if(field_ddt.find('select').val()) {
            field_ddt.find('button').removeClass('button-disabled');
            field_ddt.find('button').attr('href', window.location.protocol +'//'+ window.location.host + field_ddt.find('select').val());
        }
        
        if($('.acf-field[data-name=allegato-ddt_firmato] .acf-input select').val()) {
            field_ddt_firmato.find('button').removeClass('button-disabled');
            field_ddt_firmato.find('button').attr('href', window.location.protocol +'//'+ window.location.host + field_ddt_firmato.find('select').val());
        }
        
        if($('.acf-field[data-name=allegato-firma] .acf-input select').val()) {
            field_firma.find('button').removeClass('button-disabled');
            field_firma.find('button').attr('href', window.location.protocol +'//'+ window.location.host + field_firma.find('select').val());
        }
    }
    
    // window.location.protocol +'//'+ window.location.host + $('.acf-field[data-name=allegato-ddt] .acf-input select').val()
    
    if($('body').hasClass('post-type-consegne') || $('body').hasClass('post-type-scadenze')) {
        
        field_ddt.find('.acf-input').append('<button type="button" href="" class="button button-primary button-large button-disabled" style="margin-top: 10px;">VISUALIZZA</button>');
        field_ddt_firmato.find('.acf-input').append('<button type="button" href="" class="button button-primary button-large button-disabled" style="margin-top: 10px;">VISUALIZZA</button>');
        field_firma.find('.acf-input').append('<button type="button" href="" class="button button-primary button-large button-disabled" style="margin-top: 10px;">VISUALIZZA</button>');
        
        updateDDTButton();
        
        field_ddt.find('button').click(function() {
            if(!$(this).hasClass('button-disabled')) window.open($(this).attr('href'), '_blank', 'location=yes,height=850,width=600,scrollbars=yes,status=yes');
        });
        field_ddt_firmato.find('button').click(function() {
            if(!$(this).hasClass('button-disabled')) window.open($(this).attr('href'), '_blank', 'location=yes,height=800,width=600,scrollbars=yes,status=yes');
        });
        field_firma.find('button').click(function() {
            if(!$(this).hasClass('button-disabled')) window.open($(this).attr('href'), '_blank', 'location=yes,height=800,width=600,scrollbars=yes,status=yes');
        });
    }
});