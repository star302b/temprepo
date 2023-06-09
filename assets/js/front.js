jQuery( function($) {

    $( document ).ready( function() {

        $('.picanova_options > div').fadeOut();
        var checkout_form = jQuery( 'form.variations_form' );

        checkout_form.on( 'woocommerce_variation_has_changed', function() {

            var variationId = $("input.variation_id").val();

            if(variationId && envData.options[variationId]) {
                $(".additional_options").html("");
                var $options = $('<div class="additional_options"></div>')

                var options = envData.options[variationId];

                $.each( options, function( key, value ) {

                    var select = '<label for="add_option['+key+']">';
                    if(value.is_required) {
                        select += value.name+'*</label><select required name="add_option['+key+']">'
                    } else {
                        select += value.name+'</label><select name="add_option['+key+']">'
                    }

                    select += '<option value="">Choose an option</option>'
                    $.each( value.values, function( optionKey, optionValues ) {
                        select += '<option data-add_price="'+optionValues.price+'" value="'+optionValues.id+'">'+optionValues.name;
                        if(optionValues.price != 0) {
                            select += ' (+'+optionValues.price+' EUR)';
                        }

                        select += '</option>'

                    });
                    select += "</select>"

                    $options.append(select);
                });
                $(".variations").after($options)
            } else {
                $(".additional_options").html("");
            }
            saveInitialPrice();
        });

        function saveInitialPrice() {
            var priceElement = $(".woocommerce-variation-price .woocommerce-Price-amount.amount");
    
            var initialPrice = priceElement.text().replace(/[^\d.,-]/g, '').replace(',', '.') * 1;
    
            $('.single-product').attr('data-initial-price', initialPrice); 
        }

        $( "body" ).on("change", ".additional_options select", function() {

            var priceElement = $(".woocommerce-variation-price .woocommerce-Price-amount.amount");

            var initPrice = $('.single-product').attr('data-initial-price') * 1;
            var add_price = 0;
            var all_selected_options = $(this).parents('.additional_options').find("option:selected");
            var currencySymbol = priceElement.find('.woocommerce-Price-currencySymbol').text();
            
            $.each( all_selected_options, function( key, value ) {
                if( $(value).data('add_price') ) {
                    add_price += $(value).data('add_price') * 1;
                }
            });

            if(add_price) {
                initPrice += add_price;
                priceElement.html( initPrice.toFixed(2).replace('.', ',') + ' ' + '<span class="woocommerce-Price-currencySymbol">' + currencySymbol + '</span>');
            }
        })
    } );
});