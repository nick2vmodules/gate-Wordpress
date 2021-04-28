var sdk = new MercurySDK({
    checkoutUrl: '/wc-api/create_transaction',
    statusUrl: '/wc-api/status',
    checkStatusInterval: parseInt(mercury_param.time),
    mount: '#mercury-cash',
    lang: 'en',
    limits: {
        BTC: mercury_param.btc,
        ETH: mercury_param.eth,
        DASH: mercury_param.dash
    }
});
var status = false;

jQuery(function(jQuery){

    function successCallback(data) {
        var checkout_form = jQuery( 'form.woocommerce-checkout' );
            checkout_form.find('#payment_method_mercury_validate').detach();
            checkout_form.submit();
    }

    function qrRequest() {
        var error_count = jQuery('.woocommerce-error li').length;

        if (error_count == 1) { // Validation Passed (Just the Fake Error I Created Exists)
            jQuery('.woocommerce-error').detach();
            jQuery( 'html, body' ).stop();
            let mail = jQuery('#billing_email').val(),
                price = mercury_param.cart_price,
                currency = mercury_param.currency;

                sdk.checkout(price, currency, mail);

                sdk.on('close', (obj) => {
                    if(obj.status && (obj.status == 'TRANSACTION_APROVED' || obj.status == 'TRANSACTION_RECEIVED')) {
                        status = obj.status;
                        successCallback();
                    }
                });

        } else { // Validation Failed (Real Errors Exists, Remove the Fake One)

            jQuery('.woocommerce-error li').each(function(){
                var error_text = jQuery(this).find('.mercury_fake_error').text();
                if (error_text == 'mercury_fake_error'){
                    jQuery(this).css('display', 'none');
                }
            });
        }
        //
        return false;
    };


    var checkout_form = jQuery('form.woocommerce-checkout');

    checkout_form.on('checkout_place_order', function () {
        console.log(status);
        if(status === 'false' || status === false) {
            if(jQuery('#payment_method_mercury').is(':checked')) {
                checkout_form.append('<input type="hidden" id="payment_method_mercury_validate" name="payment_method_mercury_validate"' +
                    ' value="1">');
            } else {
                checkout_form.find('#payment_method_mercury_validate').detach();
            }
            return true;
        }

        return true;
    });

    jQuery(document.body).on('checkout_error', qrRequest);
});


