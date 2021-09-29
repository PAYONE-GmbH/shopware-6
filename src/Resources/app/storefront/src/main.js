import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';
import PayonePaymentDebitCard from './debit-card/payone-payment.debit-card';
import PayonePaymentPayolutionInvoicing from './payolution-invoicing/payone-payment.payolution-invoicing';
import PayonePaymentPayolutionInstallment from './payolution-installment/payone-payment.payolution-installment';
import PayonePaymentApplePay from "./apple-pay/payone-payment.apple-pay";

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');
PluginManager.register('PayonePaymentDebitCard', PayonePaymentDebitCard, '[data-is-payone-debit-card]');
PluginManager.register('PayonePaymentPayolutionInvoicing', PayonePaymentPayolutionInvoicing, '[data-is-payone-payolution-invoicing]');
PluginManager.register('PayonePaymentPayolutionInstallment', PayonePaymentPayolutionInstallment, '[data-is-payone-payolution-installment]');
PluginManager.register('PayonePaymentApplePay', PayonePaymentApplePay, '[data-payone-payment-apple-pay-options]');

if (module.hot) {
    module.hot.accept();
}
