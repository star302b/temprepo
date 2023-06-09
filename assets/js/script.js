jQuery( function ($) {

    let select ;

    $('#picanova_product').change( function () {

        let picanova_product = $(this).val();

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data:{
                action: 'save_product',
                picanova_product: picanova_product
            },
            success: function( data ){
                let variations = $.parseJSON(data) ;
                console.log(data);
                let result = '<select id="picanova_variations" name="picanova_variations" class="cfwc-custom-field short">' ;

                $.each( variations.data, function( i, item ) {
                    result = result.concat('<option value="' + variations.data[i].id + '">' + variations.data[i].name + ' - ' + variations.data[i].price_details.formatted + '</option>');
                });
                result = result.concat('</select>');

                select = result ;
                console.log(select);
            },
            error: function( jqXHR, exception ) {
                console.log( jqXHR, exception );
            }
        });

    });

    $( document ).ajaxComplete(function() {
        if ( select != undefined ) {
            $('.woocommerce_variation h3').on('click', function () {
                console.log('============================================');
                console.log(select);
                $('.picanova_variations_field').find('#picanova_variations').remove();
                $('.picanova_variations_field').html($('.picanova_variations_field').html() + select);
            });
        }
    });

});