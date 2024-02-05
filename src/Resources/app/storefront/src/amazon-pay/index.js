import PayonePaymentAmazonPayExpressButton from "./express";
import PayonePaymentAmazonPayRedirect from "./redirect";

PluginManager.register('PayonePaymentAmazonPayExpressButton', PayonePaymentAmazonPayExpressButton, '[data-payone-payment-amazon-pay-express-button]');
PluginManager.register('PayonePaymentAmazonPayRedirect', PayonePaymentAmazonPayRedirect, '[data-payone-payment-amazon-pay-redirect]');
