import Plugin from 'src/plugin-system/plugin.class';

class PayonePaymentAmazonPayExpressButton extends Plugin {
    init() {
        if (!('amazon' in window)) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://static-eu.payments-amazon.com/checkout.js';
            script.onload = this._initButton.bind(this);
            document.head.appendChild(script);
        } else {
            this._initButton()
        }
    }

    _initButton() {
        amazon.Pay.renderButton('#' + this.el.id, this.options);
    }
}

PluginManager.register('PayonePaymentAmazonPayExpressButton', PayonePaymentAmazonPayExpressButton, '[data-payone-payment-amazon-pay-express-button]');
