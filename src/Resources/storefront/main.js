import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';
import PayonePaymentDebitCard from './debit-card/payone-payment.debit-card';
import PayonePaymentPaysafeInvoicing from './paysafe-invoicing/payone-payment.paysafe-invoicing';

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');
PluginManager.register('PayonePaymentDebitCard', PayonePaymentDebitCard, '[data-is-payone-debit-card]');
PluginManager.register('PayonePaymentPaysafeInvoicing', PayonePaymentPaysafeInvoicing, '[data-is-payone-paysafe-invoicing]');

if (module.hot) {
    module.hot.accept();
}