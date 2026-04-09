Squareup = {};
Squareup.options = {};
Squareup.paymentForm = {};
Squareup.squareUpThis = null;
Squareup.init = function () {
    Squareup.initOptions();
};
Squareup.initPayment = function () {
    if (typeof(Squareup.options.applicationId) === 'undefined') {
        Squareup.init();
    }

    Squareup.paymentForm = new SqPaymentForm(Squareup.options);
    Squareup.paymentForm.build();
    Squareup.bindPayButton();
};
Squareup.requestCardNonce = function (event) {
    if (typeof event !== 'undefined') {
        event.preventDefault();
    }

    Squareup.paymentForm.requestCardNonce();
    return false;
};
Squareup.buttonExists = function () {
    return document.getElementById('sq-creditcard');
};
Squareup.bindPayButton = function() {
    jQuery('#sq-creditcard').on('click', function () {
        Squareup.requestCardNonce();
    });
};
Squareup.callbacks = {
    /*
     * callback function: methodsSupported
     * Triggered when: the page is loaded.
     */
    methodsSupported: function (methods) {

        var applePayBtn = document.getElementById('sq-apple-pay');
        var applePayLabel = document.getElementById('sq-apple-pay-label');
        var masterpassBtn = document.getElementById('sq-masterpass');
        var masterpassLabel = document.getElementById('sq-masterpass-label');

        // Only show the button if Apple Pay for Web is enabled
        // Otherwise, display the wallet not enabled message.
        if (methods.applePay === true) {
            applePayBtn.style.display = 'inline-block';
            applePayLabel.style.display = 'none' ;
        }

        // Only show the button if Masterpass is enabled
        // Otherwise, display the wallet not enabled message.
        if (methods.masterpass === true) {
            masterpassBtn.style.display = 'inline-block';
            masterpassLabel.style.display = 'none';
        }
    },

    /*
     * callback function: createPaymentRequest
     * Triggered when: a digital wallet payment button is clicked.
     */
    createPaymentRequest: function () {

        var paymentRequestJson ;
        /* ADD CODE TO SET/CREATE paymentRequestJson */
        return paymentRequestJson ;
    },

    /*
     * callback function: validateShippingContact
     * Triggered when: a shipping address is selected/changed in a digital
     *                 wallet UI that supports address selection.
     */
    validateShippingContact: function (contact) {

        var validationErrorObj ;
        /* ADD CODE TO SET validationErrorObj IF ERRORS ARE FOUND */
        return validationErrorObj ;
    },

    /*
     * callback function: cardNonceResponseReceived
     * Triggered when: SqPaymentForm completes a card nonce request
     */
    cardNonceResponseReceived: function (errors, nonce, cardData) {
        jQuery('#payment_form_squareup_payment .message-wrapper').hide().html('');
        if (errors) {
            // Log errors from nonce generation to the Javascript console
            // console.log("Encountered errors:");
            var errorString = '';
            errors.forEach(
                function (error) {
                    errorString += '  ' + error.message;
                }
            );

            alert(errorString);

            return false;
        }

        document.getElementById('digital-wallet').value = cardData.digital_wallet_type;
        document.getElementById('card-brand').value = cardData.card_brand;
        document.getElementById('card-last-4').value = cardData.last_4;
        document.getElementById('card-exp-month').value = cardData.exp_month;
        document.getElementById('card-exp-year').value = cardData.exp_year;

        window.Squareup.paymentForm.verifyBuyer(
            nonce,
            {
                amount: window.SquareupSessionQuote.amount.toString(),
                intent: "CHARGE",
                currencyCode: "USD",
                billingContact: {
                    familyName: window.SquareupSessionQuote.familyName,
                    givenName: window.SquareupSessionQuote.givenName,
                    email: window.SquareupSessionQuote.email,
                    country: window.SquareupSessionQuote.country,
                    city: window.SquareupSessionQuote.city,
                    addressLines: window.SquareupSessionQuote.addressLines,
                    postalCode: window.SquareupSessionQuote.postalCode,
                    phone: window.SquareupSessionQuote.phone,
                }
            },
            function (err, verification) {
                console.log(err);
                console.log(verification);
                if (err == null) {
                   // document.getElementById('buyerVerification-token').value = verification.token;
                    //console.log(_self.verificationToken);

                    //_self.verificationToken = verification.token;
                    //console.log(_self.verificationToken);
                    document.getElementById('verification-token').value = verification.token;
                }
            }
        );

        // Assign the nonce value to the hidden form field
        document.getElementById('card-nonce').value = nonce;
        alert('Credit card information received you can now proceed to order submission');

        return false;
    },

    /*
     * callback function: unsupportedBrowserDetected
     * Triggered when: the page loads and an unsupported browser is detected
     */
    unsupportedBrowserDetected: function () {
        /* PROVIDE FEEDBACK TO SITE VISITORS */
    },

    /*
     * callback function: inputEventReceived
     * Triggered when: visitors interact with SqPaymentForm iframe elements.
     */
    inputEventReceived: function (inputEvent) {
        switch (inputEvent.eventType) {
            case 'focusClassAdded':
                /* HANDLE AS DESIRED */
                break;
            case 'focusClassRemoved':
                /* HANDLE AS DESIRED */
                break;
            case 'errorClassAdded':
                /* HANDLE AS DESIRED */
                break;
            case 'errorClassRemoved':
                /* HANDLE AS DESIRED */
                break;
            case 'cardBrandChanged':
                /* HANDLE AS DESIRED */
                break;
            case 'postalCodeChanged':
                /* HANDLE AS DESIRED */
                break;
        }
    },

    /*
     * callback function: paymentFormLoaded
     * Triggered when: SqPaymentForm is fully loaded
     */
    paymentFormLoaded: function () {
        /* HANDLE AS DESIRED */
        jQuery('#sq-creditcard').prop('disabled',false);
    }
};
Squareup.initOptions = function () {
    Squareup.options = {
        applicationId: window.SquareupApplicationId,
        locationId: window.SquareupLocationId,
        inputClass: 'sq-input',

        // Customize the CSS for SqPaymentForm iframe elements
        inputStyles: [{
            fontSize: '.9em'
        }],

        // Initialize Apple Pay placeholder ID
        applePay: false,

        // Initialize Masterpass placeholder ID
        masterpass: false,

        // Initialize the credit card placeholders
        cardNumber: {
            elementId: 'sq-card-number',
            placeholder: '•••• •••• •••• ••••'
        },
        cvv: {
            elementId: 'sq-cvv',
            placeholder: 'CVV'
        },
        expirationDate: {
            elementId: 'sq-expiration-date',
            placeholder: 'MM/YY'
        },
        postalCode: {
            elementId: 'sq-postal-code'
        },

        // SqPaymentForm callback functions
        callbacks: Squareup.callbacks
    };
};
require(
    [
        'jquery',
        'mage/translate',
    ],
    function ($) {
        Squareup.init();
    }
);
