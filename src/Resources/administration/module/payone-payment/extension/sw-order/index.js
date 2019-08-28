const { Component, Mixin } = Shopware;
import template from './sw-order.html.twig';
import './sw-order.scss';

Component.override('sw-order-detail-base', {
    template,

    inject: ['PayonePaymentService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            disableButtons: false
        };
    },

    methods: {
        isPayonePayment(transaction) {
            if (!transaction.customFields) {
                return false;
            }

            return transaction.customFields.payone_transaction_id;
        },

        isCapturePossible(transaction) {
            if (!transaction.customFields) {
                return false;
            }

            if (this.disableButtons) {
                return false;
            }

            return transaction.customFields.payone_allow_capture;
        },

        isRefundPossible(transaction) {
            if (!transaction.customFields) {
                return false;
            }

            if (this.disableButtons) {
                return false;
            }

            return transaction.customFields.payone_allow_refund;
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
            let me = this;

            if (!this.isPayonePayment(transaction)) {
                return;
            }

            me.disableButtons = true;

            this.PayonePaymentService.capturePayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-payment.capture.successTitle'),
                        message: this.$tc('payone-payment.capture.successMessage')
                    });

                    me.reloadEntityData();
                    me.disableButtons = false;
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: this.$tc('payone-payment.capture.errorTitle'),
                        message: errorResponse.response.data.message
                    });

                    me.disableButtons = false;
                });
        },

        refundOrder(transaction) {
            let me = this;

            if (!this.isPayonePayment(transaction)) {
                return;
            }

            me.disableButtons = true;

            this.PayonePaymentService.refundPayment(transaction.id)
                .then(() => {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-payment.refund.successTitle'),
                        message: this.$tc('payone-payment.refund.successMessage')
                    });

                    me.reloadEntityData();
                    me.disableButtons = false;
                })
                .catch((errorResponse) => {
                    this.createNotificationError({
                        title: this.$tc('payone-payment.refund.errorTitle'),
                        message: errorResponse.response.data.message
                    });

                    me.disableButtons = false;
                });
        },
    }
});
