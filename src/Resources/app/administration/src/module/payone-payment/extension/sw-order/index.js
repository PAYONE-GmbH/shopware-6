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
        }
    }
});
