import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');

if (module.hot) {
    module.hot.accept();
}
