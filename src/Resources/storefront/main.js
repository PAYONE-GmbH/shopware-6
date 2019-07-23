import 'jquery/dist/jquery.slim';
import 'bootstrap';

// TODO: remove imports above when shopware fixes https://issues.shopware.com/issues/NEXT-4127

import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';
import PayonePaymentDebitCard from './debit-card/payone-payment.debit-card';

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');
PluginManager.register('PayonePaymentDebitCard', PayonePaymentDebitCard, '[data-is-payone-debit-card]');

if (module.hot) {
    module.hot.accept();
}