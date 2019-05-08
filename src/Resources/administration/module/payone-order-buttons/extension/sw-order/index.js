import { Component, Mixin } from 'src/core/shopware';
import template from './sw-order.html.twig';

Component.override('sw-order-detail-base', {
    template,

    inject: ['PayonePaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    methods: {
        captureOrder(order) {
            this.PayonePaymentService.capturePayment(order.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-order-buttons.captureAction.successTitle'),
                        message: this.$tc('payone-order-buttons.captureAction.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: errorResponse.title,
                        message: errorResponse.message
                    });
                });
        },

        refundOrder(order) {
            this.PayonePaymentService.refundPayment(order.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-order-buttons.captureAction.successTitle'),
                        message: this.$tc('payone-order-buttons.captureAction.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: errorResponse.title,
                        message: errorResponse.message
                    });
                });
        },
    }
});
