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

    computed: {
        payoneTransactions: function() {
            return this.order.transactions.filter(transaction => this.isPayoneTransaction(transaction)).sort((a, b) => { // newest transaction first
                if(a.createdAt < b.createdAt) {
                    return 1;
                } else if(a.createdAt > b.createdAt) {
                    return -1;
                } else {
                    return 0;
                }
            });
        }
    },

    methods: {
        isPayoneTransaction(transaction) {
            if (!transaction.customFields) {
                return false;
            }

            return transaction.customFields.payone_transaction_id;
        },

        isActiveTransaction(transaction) {
            return transaction.stateMachineState.technicalName !== 'cancelled';
        },

        hasPayoneTransaction(order) {
            let me = this;
            let isPayone = false;

            if (!order.transactions) {
                return false;
            }

            order.transactions.map(function(transaction) {
                if (me.isPayoneTransaction(transaction) && me.isActiveTransaction(transaction)) {
                    isPayone = true;
                }
            });

            return isPayone;
        }
    }
});
