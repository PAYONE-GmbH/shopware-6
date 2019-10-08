import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';
import PayonePaymentDebitCard from './debit-card/payone-payment.debit-card';
import PayonePaymentPayolutionInvoicing from './payolution-invoicing/payone-payment.payolution-invoicing';
import PayonePaymentPayolutionInstallment from './payolution-installment/payone-payment.payolution-installment';

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');
PluginManager.register('PayonePaymentDebitCard', PayonePaymentDebitCard, '[data-is-payone-debit-card]');
PluginManager.register('PayonePaymentPayolutionInvoicing', PayonePaymentPayolutionInvoicing, '[data-is-payone-payolution-invoicing]');
PluginManager.register('PayonePaymentPayolutionInstallment', PayonePaymentPayolutionInstallment, '[data-is-payone-payolution-installment]');

if (module.hot) {
    module.hot.accept();
}