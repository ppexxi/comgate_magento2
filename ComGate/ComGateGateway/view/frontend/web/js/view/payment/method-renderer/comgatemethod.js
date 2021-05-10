/* ComGate Payment frontend method renderer */
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer'
    ],
    function(
        ko,
        $,
        Component,
        urlBuilder,
        storage,
        fullScreenLoader,
        placeOrderAction,
        additionalValidators,
        quote,
        customer
    ) {
        'use strict';
        var tfConfig = window.checkoutConfig.payment.comgate;

        console.log('ComGate initialized');

        var gSelection = ko.observable(null);

        return Component.extend({
            defaults: {
                template: 'ComGate_ComGateGateway/payment/comgate'
            },

            redirectAfterPlaceOrder: false,

            selectPaymentMethodCard: function() {
                gSelection('card');
                return this.selectPaymentMethod();
            },

            selectPaymentMethodWire: function() {
                gSelection('wire');
                return this.selectPaymentMethod();
            },

            selectPaymentMethodDelay: function() {
                gSelection('delay');
                return this.selectPaymentMethod();
            },

            isChecked: ko.computed(function() {
                return gSelection();
            }),

            /**
             * After placing order, we need to create gateway redirection URL 
            */
            placeOrder: function(data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                console.log('Placing order ...');

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function() {
                                console.log('Error placing order!');
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function(response) {
                                console.log('Order placed: ' + response);
                                var data = JSON.parse(response);
                                self.redirectToGateway(data);
                            }
                        );

                    return true;
                }

                return false;
            },

            /**
             * Redirects the user to comgate payment page
             */
            redirectToGateway: function(data) {

                var selection = gSelection();

                if (!selection) {
                    console.log('No ComGate method selected!');
                    self.isPlaceOrderActionAllowed(true);
                    return;
                }

                console.log('Redirecting: ' + JSON.stringify(data));

                $.get(tfConfig.form_url, {
                    //'order_id': data
                    'selection': selection
                }).success(function(response) {
                    try {
                        var data = JSON.parse(response);
                        window.location.href = data;
                    }
                    catch(e) {
                        console.log('Error placing order!');
                        self.isPlaceOrderActionAllowed(true);
                    }
                });
            }
        });
    }
);