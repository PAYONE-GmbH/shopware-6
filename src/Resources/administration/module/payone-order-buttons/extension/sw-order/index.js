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
            if (!transaction.customFields) {
                return false;
            }

            return !!transaction.customFields.payone_transaction_id;
        },

        hasPayonePayment(order) {
            let me = this;
            let isPayone = false;

            if (!order.transactions) {
                return false;
            }

            order.transactions.map(function(transaction) {
                if (me.isPayonePayment(transaction)) {
                    isPayone = true;
                }
            });

            return isPayone;
        },

        captureOrder(transaction) {
            if (!this.isPayonePayment(transaction)) {
                return;
            }

            this.PayonePaymentService.capturePayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-order-buttons.capture.successTitle'),
                        message: this.$tc('payone-order-buttons.capture.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: this.$tc('payone-order-buttons.capture.errorTitle'),
                        message: errorResponse.response.message
                    });
                });
        },

        refundOrder(transaction) {
            if (!this.isPayonePayment(transaction)) {
                return;
            }

            this.PayonePaymentService.refundPayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-order-buttons.refund.successTitle'),
                        message: this.$tc('payone-order-buttons.refund.successMessage')
                    });
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: this.$tc('payone-order-buttons.refund.errorTitle'),
                        message: errorResponse.response.message
                    });
                });
        },
    }
});
