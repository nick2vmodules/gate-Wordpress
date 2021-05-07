if (typeof mercury_param != "undefined") {
    var sdk = new MercurySDK({
        checkoutUrl: "/wc-api/create_transaction",
        statusUrl: "/wc-api/status",
        checkStatusInterval: parseInt(mercury_param.time, 2),
        mount: "#mercury-cash",
        lang: "en",
        limits: {
            BTC: mercury_param.btc,
            ETH: mercury_param.eth,
            DASH: mercury_param.dash
        }
    });
}
var status = false;

jQuery(function(jQuery){

    function successCallback(data) {
        var checkoutForm = jQuery( "form.woocommerce-checkout");
            checkoutForm.find("#payment_method_mercury_validate").detach();
            checkoutForm.submit();
    }

    function qrRequest() {
        var errorCount = jQuery(".woocommerce-error li").length;

        if (errorCount === 1) { // Validation Passed (Just the Fake Error I Created Exists)
            jQuery(".woocommerce-error").detach();
            jQuery( "html, body").stop();
            let mail = jQuery("#billing_email").val(),
                price = mercury_param.cart_price,
                currency = mercury_param.currency;

                sdk.checkout(price, currency, mail);

                sdk.on("close", (obj) => {
                    if(obj.status && (obj.status === "TRANSACTION_APROVED" || obj.status === "TRANSACTION_RECEIVED")) {
                        status = obj.status;
                        successCallback();
                    }
                });

        } else { // Validation Failed (Real Errors Exists, Remove the Fake One)

            jQuery('.woocommerce-error li').each(function(){
                var error_text = jQuery(this).find(".mercury_fake_error").text();
                if (error_text == "mercury_fake_error"){
                    jQuery(this).css("display", "none");
                }
            });
        }
        //
        return false;
    }


    var checkoutForm = jQuery('form.woocommerce-checkout');

    checkoutForm.on("checkout_place_order", function () {
        if(status === "false" || status === false) {
            if(jQuery("#payment_method_mercury").is(":checked")) {
                checkoutForm.append("<input type='hidden' id='payment_method_mercury_validate' name='payment_method_mercury_validate'" +
                    " value='1'>");
            } else {
                checkoutForm.find("#payment_method_mercury_validate").detach();
            }
            return true;
        }
        return true;
    });

    jQuery(document.body).on("checkout_error", qrRequest);
});


