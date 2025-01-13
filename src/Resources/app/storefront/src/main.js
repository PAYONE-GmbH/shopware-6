import PayonePaymentCreditCard from './credit-card/payone-payment.credit-card';
import PayonePaymentDebitCard from './debit-card/payone-payment.debit-card';
import PayonePaymentPayolutionInvoicing from './payolution-invoicing/payone-payment.payolution-invoicing';
import PayonePaymentPayolutionInstallment from './payolution-installment/payone-payment.payolution-installment';
import PayonePaymentApplePay from "./apple-pay/payone-payment.apple-pay";
import PayonePaymentRatepayInstallment from "./ratepay-installment/payone-payment.ratepay-installment";
import PayonePaymentKlarna from "./klarna/payone-payment.klarna";
import PayonePaymentPayPalV2 from "./paypal-v2/payone-payment.paypal-v2";
import "./amazon-pay";

const PluginManager = window.PluginManager;

PluginManager.register('PayonePaymentCreditCard', PayonePaymentCreditCard, '[data-is-payone-credit-card]');
PluginManager.register('PayonePaymentDebitCard', PayonePaymentDebitCard, '[data-is-payone-debit-card]');
PluginManager.register('PayonePaymentPayolutionInvoicing', PayonePaymentPayolutionInvoicing, '[data-is-payone-payolution-invoicing]');
PluginManager.register('PayonePaymentPayolutionInstallment', PayonePaymentPayolutionInstallment, '[data-is-payone-payolution-installment]');
PluginManager.register('PayonePaymentApplePay', PayonePaymentApplePay, '[data-payone-payment-apple-pay-options]');
PluginManager.register('PayonePaymentRatepayInstallment', PayonePaymentRatepayInstallment, '[data-is-payone-ratepay-installment]');
PluginManager.register('PayonePaymentKlarna', PayonePaymentKlarna, '[data-payone-payment-klarna]');
PluginManager.register('PayonePaymentPayPalV2', PayonePaymentPayPalV2, '[data-payone-payment-pay-pal-v2-options]');

if (module.hot) {
    module.hot.accept();
}
