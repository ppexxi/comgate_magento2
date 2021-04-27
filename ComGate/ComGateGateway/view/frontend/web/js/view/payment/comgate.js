/* ComGate Payment frontend component */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'comgate',
            component: 'ComGate_ComGateGateway/js/view/payment/method-renderer/comgatemethod'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);