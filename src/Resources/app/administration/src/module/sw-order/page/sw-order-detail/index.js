import template from './sw-order-detail.html.twig';

export default {
  template,

  methods: {
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
  }
};
