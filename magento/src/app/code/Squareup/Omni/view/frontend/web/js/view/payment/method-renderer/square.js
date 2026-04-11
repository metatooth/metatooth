define(
    [
        'jquery',
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Squareup_Omni/js/view/payment/giftcard/square',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        ko,
        Component,
        url,
        additionalValidators,
        redirectOnSuccessAction,
        squareGiftCard,
        quote
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: {
                    name: 'Squareup_Omni/payment/square'
                },
                SquareupApplicationId: window.checkoutConfig.payment.squareup.squareupApplicationId,
                SquareupLocationId: window.checkoutConfig.payment.squareup.squareupLocationId,
                giftCards: squareGiftCard.giftCards,
                displayForm: squareGiftCard.displayForm,
                showBalance: squareGiftCard.showBalance,
                requestShippingAddress: false,
                requestBillingInfo: false,
                currencyCode: 'USD',
                squareupMerchantName: window.checkoutConfig.payment.squareup.squareupMerchantName,
            },
            verificationToken: null,

            context: function () {
                return this;
            },

            getCode: function () {
                return 'squareup_payment';
            },

            isActive: function () {
                return true;
            },

            getActionUrl: function () {
                return url.build('square/index/saveNonce');
            },

            getHaveSavedCards: function () {
                return window.checkoutConfig.payment.squareup.getHaveSavedCards;
            },

            getIsSaveOnFileEnabled: function () {
                return window.checkoutConfig.payment.squareup.getIsSaveOnFileEnabled;
            },

            displaySaveCcCheckbox: function () {
                return window.checkoutConfig.payment.squareup.displaySaveCcCheckbox;
            },

            getCustomerCards: function () {
                if (typeof(window.checkoutConfig.payment.squareup.customerCards.length) !== "undefined"
                    && typeof(window.checkoutConfig.payment.squareup.customerCards.length) !== "number") {
                    var cards = [];

                    Object.keys(window.checkoutConfig.payment.squareup.customerCards)
                        .forEach(function (key, index) {
                            window.checkoutConfig.payment.squareup.customerCards[key]['card_id'] = key;
                            cards.push(window.checkoutConfig.payment.squareup.customerCards[key]);
                        });
                    window.checkoutConfig.payment.squareup.customerCards = cards;
                    return cards;
                } else if (typeof(window.checkoutConfig.payment.squareup.customerCards.length) === "number") {
                    return window.checkoutConfig.payment.squareup.customerCards;
                } else if (typeof(window.checkoutConfig.payment.squareup.customerCards) === "object") {
                    var cards = [];

                    Object.keys(window.checkoutConfig.payment.squareup.customerCards)
                        .forEach(function (key, index) {
                            window.checkoutConfig.payment.squareup.customerCards[key]['card_id'] = key;

                            cards.push(window.checkoutConfig.payment.squareup.customerCards[key]);
                        });
                    return cards;
                } else {
                    return [];
                }
            },

            getCardInputTitle: function (data) {
                 return data.cardholder_name + " | " + data.card_brand + " | " + data.exp_month + "/" + data.exp_year + " | **** " + data.last_4;
                // return window.checkoutConfig.payment.squareup.cardInputTitles[data];
                // return 'Temporary title';
            },

            getCanSaveCards: function () {
                return window.checkoutConfig.payment.squareup.getCanSaveCards;
            },

            onlyCardOnFileEnabled: function () {
                return window.checkoutConfig.payment.squareup.onlyCardOnFileEnabled;
            },

            isGiftCardEnabled: function () {
                return window.checkoutConfig.payment.squareup.isGiftCardEnabled;
            },

            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function (response) {
                                alert(response.responseJSON.message);
                                squareGiftCard.resetGiftCards();
                                window.Squareup.paymentForm.recalculateSize();
                                if (self.displayForm()) {
                                    if (false === window.squareupCardOnFileUsed) {
                                        document.getElementById('card-nonce').value = "";
                                    }

                                    self.isPlaceOrderActionAllowed(true);
                                }

                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            initialize: function () {

                this._super();
                var _self = this;

                window.Squareup = {};
                window.Squareup.options = {};
                window.Squareup.paymentForm = {};
                window.Squareup.createPaymentRequest = function() {
                    var shippingAddressData = quote.shippingAddress();
                    var email = (!quote.guestEmail)? window.checkoutConfig.customerData.email : quote.guestEmail;
                    var quoteTotals = quote.totals();
                    var itemsInfo = window.Squareup.getOrderItems(quoteTotals);
                    return {
                        requestShippingAddress: _self.requestShippingAddress,
                        requestBillingInfo: _self.requestBillingInfo,
                        currencyCode: _self.currencyCode,
                        countryCode: shippingAddressData.countryId,
                        shippingContact: {
                            familyName: shippingAddressData.firstname,
                            givenName: shippingAddressData.lastname,
                            email: email,
                            country: shippingAddressData.countryId,
                            region: shippingAddressData.regionCode,
                            city: shippingAddressData.city,
                            addressLines: shippingAddressData.street,
                            postalCode: shippingAddressData.postcode
                        },
                        total: {
                            label: _self.squareupMerchantName,
                            amount: quoteTotals.grand_total.toString(),
                            pending: false
                        },
                        lineItems: itemsInfo
                    };
                };

                window.Squareup.getOrderItems = function(totals) {
                    return [
                        {
                            label: "Subtotal",
                            amount: totals.subtotal.toString(),
                            pending: false
                        },
                        {
                            label: "Shipping",
                            amount: totals.shipping_amount.toString(),
                            pending: true
                        },
                        {
                            label: "Tax",
                            amount: totals.tax_amount.toString(),
                            pending: false
                        }
                    ];
                };

                window.Squareup.callbacks = {
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
                        }

                        // Only show the button if Masterpass is enabled
                        // Otherwise, display the wallet not enabled message.
                        if (methods.masterpass === true) {
                            masterpassBtn.style.display = 'inline-block';
                        }
                    },

                    /*
                     * callback function: createPaymentRequest
                     * Triggered when: a digital wallet payment button is clicked.
                     */
                    createPaymentRequest: function () {
                        return window.Squareup.createPaymentRequest();
                    },

                    /*
                     * callback function: validateShippingContact
                     * Triggered when: a shipping address is selected/changed in a digital
                     *                 wallet UI that supports address selection.
                     */
                    // validateShippingContact: function (contact) {

                    //     let validationErrorObj ;
                    //     /* ADD CODE TO SET validationErrorObj IF ERRORS ARE FOUND */
                    //     return validationErrorObj ;
                    // },

                    /*
                     * callback function: cardNonceResponseReceived
                     * Triggered when: SqPaymentForm completes a card nonce request
                     */
                    cardNonceResponseReceived: function (errors, nonce, cardData) {
                        var shippingAddressData = quote.shippingAddress();
                        var email = (!quote.guestEmail)? window.checkoutConfig.customerData.email : quote.guestEmail;
                        _self.messageContainer.clear();
                        if (errors) {
                            // Log errors from nonce generation to the Javascript console
                            console.log("Encountered errors:");
                            errors.forEach(
                                function (error) {
                                    _self.messageContainer.addErrorMessage({
                                        message: error.message,
                                        parameters: {}
                                    });
                                    console.log('  ' + error.message);
                                }
                            );

                            return;
                        }

                        // Assign the nonce value to the hidden form field
                        document.getElementById('card-nonce').value = nonce;
                        document.getElementById('digital-wallet').value = cardData.digital_wallet_type;
                        document.getElementById('card-brand').value = cardData.card_brand;
                        document.getElementById('card-last-4').value = cardData.last_4;
                        document.getElementById('card-exp-month').value = cardData.exp_month;
                        document.getElementById('card-exp-year').value = cardData.exp_year;

                        window.Squareup.paymentForm.verifyBuyer(
                            nonce,
                            {
                                amount: quote.totals().grand_total.toString(),
                                intent: "CHARGE",
                                currencyCode: "USD",
                                billingContact: {
                                    familyName: shippingAddressData.firstname,
                                    givenName: shippingAddressData.lastname,
                                    email: email,
                                    country: shippingAddressData.countryId,
                                    city: shippingAddressData.city,
                                    addressLines: shippingAddressData.street,
                                    postalCode: shippingAddressData.postcode,
                                    phone: shippingAddressData.telephone
                                }
                            },
                            function (err, verification) {

                                if (err == null) {
                                    document.getElementById('buyerVerification-token').value = verification.token;
                                    console.log(_self.verificationToken);

                                    _self.verificationToken = verification.token;
                                    console.log(_self.verificationToken);
                                    _self.placeOrder();
                                }
                            }
                        );

                        console.log('Nonce received');
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
                        Squareup.paymentForm.setPostalCode(quote.shippingAddress().postcode);
                        jQuery('#sq-creditcard').prop('disabled',false);
                    }
                };

                /* Initialize options function */
                var init = function () {
                    _self.initOptions();
                };

                /* Initialize options event trigger */
                /* wait for document to be loaded to initialize the events */
                /*document.observe(
                    "dom:loaded", function () {
                        init();
                    }
                );*/
                $(document).ready(function () {
                    init();
                });
            },

            initOptions: function () {
                var _self = this;
                window.Squareup.options = {
                    applicationId: _self.SquareupApplicationId,
                    locationId: _self.SquareupLocationId,
                    inputClass: 'sq-input',

                    // Customize the CSS for SqPaymentForm iframe elements
                    inputStyles: [{
                        fontSize: '.9em'
                    }],

                    // Initialize Apple Pay placeholder ID
                    applePay: {
                        elementId: 'sq-apple-pay'
                    },

                    // Initialize Masterpass placeholder ID
                    masterpass: false,
                    /*masterpass: {
                        elementId: 'sq-masterpass'
                    },*/

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
            },

            initPayment: function () {
                var _self = this;
                squareGiftCard.getGiftCards();
                if (parseFloat(window.checkoutConfig.quoteData.grand_total) > 0) {
                    _self.displayForm(true);
                }
                window.squareupCardOnFileUsed = false;
                console.log('Initing payment');
                window.Squareup.paymentForm = new SqPaymentForm(window.Squareup.options);
                window.Squareup.paymentForm.build();

                if(this.isGiftCardEnabled()) {
                    /** Build gift card form*/
                    squareGiftCard.build("sq-gift-card", "GIFT CARD NUMBER");
                }

                $(document).ready(function () {
                    if(_self.onlyCardOnFileEnabled() == true && window.checkoutConfig.payment.squareup.getHaveSavedCards) {
                        jQuery('#save_square_cards_empty').val('0');
                        jQuery('#save-square-card').val('0');
                    } else if(_self.onlyCardOnFileEnabled() == true) {
                        jQuery('#save_square_cards_empty').val('1');
                        jQuery('#save-square-card').val('1');
                    }
                    $('body').on('change', 'input[type=radio][name=squareup_cards]', function () {
                        if (this.value == 'other_card') {
                            if (_self.onlyCardOnFileEnabled() == true) {
                                var input = document.createElement("input");
                                input.type = 'hidden';
                                input.name = 'payment[save_square_card]';
                                input.id = 'save_card_on_file_input';
                                input.value = 1;
                                document.getElementById("square_form_fields").appendChild(input);
                            }
                            document.getElementById("square_form_fields").style.display = "block";
                            jQuery('input[name="payment[nonce]"]').val('');
                            window.squareupCardOnFileUsed = false;
                            // $('#payment_form_squareup_payment + .actions-toolbar .primary').addClass('hide');
                        } else {
                            var element =  document.getElementById('save_card_on_file_input');
                            if (typeof(element) != 'undefined' && element != null) {
                                // jQuery('input[name="payment[save_square_card]"]').remove();
                                jQuery('input[name="payment[save_square_card]"]').val('');
                            }
                            document.getElementById("square_form_fields").style.display = "none";
                            jQuery('input[name="payment[nonce]"]').val(this.value);
                            window.squareupCardOnFileUsed = true;
                            // $('#payment_form_squareup_payment + .actions-toolbar .primary').removeClass('hide');
                        }
                    });
                });

                if(this.isGiftCardEnabled()) {
                  squareGiftCard.resetTotals();
                }
            },

            requestCardNonce: function (item, event) {
                event.preventDefault();
                var existingNonce = document.getElementById('card-nonce');
                var _self = this;

                if (!_self.displayForm()) {
                    _self.placeOrder();
                } else {
                    if (existingNonce.value.length === 0) {
                        Squareup.paymentForm.requestCardNonce();
                    } else {
                        _self.placeOrder();
                    }
                }
            },

            getData: function () {
                var data = this._super();
                var self = this;

                var cardNonce = $('#card-nonce').val();
                var ccType = $('#card-brand').val();
                var digitalWallet = $('#digital-wallet').val();
                var ccLast4 = $('#card-last-4').val();
                var ccExpMonth = $('#card-exp-month').val();
                var ccExpYear = $('#card-exp-year').val();
                if (cardNonce) {
                    data.additional_data.nonce = cardNonce;
                    data.additional_data.cc_type = ccType;
                    data.additional_data.digital_wallet = digitalWallet;
                    data.additional_data.cc_last_4 = ccLast4;
                    data.additional_data.cc_exp_month = ccExpMonth;
                    data.additional_data.cc_exp_year = ccExpYear;
                }

                var buyerVerification = $('#buyerVerification-token').val();

                data.additional_data.buyerVerificationToken = self.verificationToken;
                data.additional_data.display_form = self.displayForm();

                var saveCard = $('#save-square-card').is(':checked');
                if (saveCard === true) {
                    data.additional_data.save_square_card = 1;
                }

                var saveCardHasValue = $('#save-square-card').val();
                if (saveCardHasValue == 1) {
                    data.additional_data.save_square_card = 1;
                }

                return data;
            },

            checkGiftCardBalance: function () {
                squareGiftCard.checkGiftCardBalance();
            },

            applyGiftCard: function () {
                squareGiftCard.applyGiftCard();
            },

            removeGiftCard: function (giftCard) {
                squareGiftCard.removeGiftCard(giftCard);
            },
        });
    }
);
