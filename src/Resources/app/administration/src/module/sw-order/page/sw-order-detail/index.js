import template from './sw-order-detail.html.twig';

export default {
    template,

    inject: ['acl'],

    methods: {
        hasPayoneTransaction(order) {
            let me = this;
            let isPayone = false;

            if (!order.transactions) {
                return false;
            }

            order.transactions.map(function (transaction) {
                if (me.isPayoneTransaction(transaction) && me.isActiveTransaction(transaction)) {
                    isPayone = true;
                }
            });


            return isPayone;
        },

        isPayoneTransaction(transaction) {
            if (!transaction.extensions || !transaction.extensions.payonePaymentOrderTransactionData || !transaction.extensions.payonePaymentOrderTransactionData.transactionId) {
                return false;
            }

            return transaction.extensions.payonePaymentOrderTransactionData.transactionId;
        },

        isActiveTransaction(transaction) {
            return transaction.stateMachineState.technicalName !== 'cancelled';
        },

        canAccessPayoneTab() {
            return (this.acl.can('payone_order_management')
                || this.acl.can('payone_payment_order_action_log:read')
                || this.acl.can('payone_payment_notification_forward:read')
                || this.acl.can('payone_payment_webhook_log:read'))
            && this.order && this.hasPayoneTransaction(this.order);
        }
    }
};
