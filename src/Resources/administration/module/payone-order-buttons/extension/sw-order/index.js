import { Component, Mixin } from 'src/core/shopware';
import template from './sw-order.html.twig';
import './sw-order.scss';

Component.override('sw-order-detail-base', {
    template,

    inject: ['PayonePaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    methods: {
        isPayonePayment(transaction) {
            return true;
        },

        hasPayonePayment(order) {
            return true;
        },

        captureOrder(transaction) {
            this.PayonePaymentService.capturePayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('payone-order-buttons.capture.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        message: errorResponse.response.message
                    });
                });
        },

        refundOrder(transaction) {
            debugger;

            this.PayonePaymentService.refundPayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('payone-order-buttons.refund.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        message: errorResponse.response.message
                    });
                });
        },
    }
});
