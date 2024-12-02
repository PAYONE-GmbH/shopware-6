import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";

export default class PayonePaymentPayPalV2 extends Plugin {
    static options = {
        sandbox: true,
        clientId: '',
        merchantId: '',
        currency: '',
        locale: '',
        showPayLaterButton: false,
        createCheckoutSessionUrl: '',
        onApproveRedirectUrl: '',
        onCancelRedirectUrl: '',
        onErrorRedirectUrl: '',
    };

    init() {
        this._client = new HttpClient();

        this._loadScript();
    }

    _loadScript() {
        const queryString = `client-id=${this.options.clientId}&merchant-id=${this.options.merchantId}&currency=${this.options.currency}&intent=authorize&locale=${this.options.locale}&commit=true&vault=false&disable-funding=card,sepa,bancontact${this.options.showPayLaterButton ? '&enable-funding=paylater' : ''}`;
        const scriptTag = document.createElement('script');

        scriptTag.onload = () => this._renderButtons();

        if (this.options.sandbox) {
            scriptTag.src = `https://sandbox.paypal.com/sdk/js?${queryString}`;
        } else {
            scriptTag.src = `https://www.paypal.com/sdk/js?${queryString}`;
        }

        document.head.append(scriptTag);
    }

    _renderButtons() {
        paypal.Buttons({

            style: {
                layout: 'vertical',
                color: 'gold',
                shape: 'rect',
                label: 'checkout',
                height: 40,
            },

            createOrder: this._createOrder.bind(this),

            onApprove: this._onApprove.bind(this),

            onCancel: this._onCancel.bind(this),

            onError: this._onError.bind(this),

        }).render('#payone-payment-paypal-v2-button-container');
    }

    _createOrder() {
        return new Promise((resolve, reject) => {
            this._client.get(
                this.options.createCheckoutSessionUrl,
                (responseText, request) => {
                    if (request.status >= 400) {
                        reject(responseText);
                    }

                    try {
                        const response = JSON.parse(responseText);
                        resolve(response.orderId);
                    } catch (error) {
                        reject(error);
                    }
                },
            );
        });
    }

    _onApprove() {
        window.location.href = this.options.onApproveRedirectUrl;
    }

    _onCancel() {
        window.location.href = this.options.onCancelRedirectUrl;
    }

    _onError() {
        window.location.href = this.options.onErrorRedirectUrl;
    }
}
