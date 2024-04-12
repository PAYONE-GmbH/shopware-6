import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';

export default class PayonePaymentKlarna extends Plugin {
    static options = {
        clientToken: null,
        paymentMethodIdentifier: null,
        selectorContainer: null,
        selectorTokenInput: 'input[name="payoneKlarnaAuthorizationToken"]',
        selectorFormId: 'confirmOrderForm'
    };

    static orderForm = null;
    static confirmFormSubmit = null;

    init() {
        window.klarnaAsyncCallback = this._initKlarnaWidget.bind(this);

        const scriptTag = document.createElement("script");
        scriptTag.src = "https://x.klarnacdn.net/kp/lib/v1/api.js";
        document.body.appendChild(scriptTag);

        this.orderForm = document.getElementById(this.options.selectorFormId);
        this.confirmFormSubmit = this.orderForm.querySelector('button[type=submit]');
        this._registerEventListeners();
    }

    _initKlarnaWidget() {
        Klarna.Payments.init({
            client_token: this.options.clientToken
        });

        Klarna.Payments.load({
            container: this.options.selectorContainer,
            payment_method_category: this.options.paymentMethodIdentifier
        }, (res) => {
            (new ButtonLoadingIndicator(this.confirmFormSubmit)).remove();
        });
    }

    _registerEventListeners() {
        if (this.orderForm) {
            this.orderForm.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }
    }

    _handleOrderSubmit(event) {
        event.preventDefault();

        Klarna.Payments.authorize({
            payment_method_category: this.options.paymentMethodIdentifier
        }, {}, (res) => {
            if (res.approved && res.authorization_token) {
                document.querySelector(this.options.selectorTokenInput).value = res.authorization_token;
                this.orderForm.submit();
            } else if(res.show_form) {
                // user has cancelled the payment
                (new ButtonLoadingIndicator(this.confirmFormSubmit)).remove();
            } else if(!res.show_form) {
                // payment has been declined
                window.location.href = window.location.href;
            }
        });
    }
}
