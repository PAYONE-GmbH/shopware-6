import template from './sw-order-user-card.html.twig';

Shopware.Component.override('sw-order-user-card', {
  template,

  computed: {
    payoneCardType() {
      let cardType = this.currentOrder.transactions.last().extensions.payonePaymentOrderTransactionData?.additionalData?.card_type;

      return cardType ? this.$tc('sw-order.payone-payment.creditCard.cardTypes.' + cardType) : null;
    }
  }
});