const Plugin = window.PluginBaseClass;

export default class PayonePaymentPayPalV2 extends Plugin {
    static options = {
        sandbox: true,
        clientId: null,
        merchantId: null,
        currency: null,
        locale: null,
        showPayLaterButton: false,
        createCheckoutSessionUrl: null,
        onApproveRedirectUrl: null,
        onCancelRedirectUrl: null,
        onErrorRedirectUrl: null,
    };

    init() {
        if (!this.options.clientId || !this.options.merchantId || !this.options.currency || !this.options.locale || !this.options.createCheckoutSessionUrl || !this.options.onApproveRedirectUrl || !this.options.onCancelRedirectUrl || !this.options.onErrorRedirectUrl) {
            console.error('The PayPal v2 Express button could not be initialized because the required options are missing.');

            return;
        }

        this._loadScript();
    }

    _loadScript() {
        const queryString = new URLSearchParams({
            'client-id': this.options.clientId,
            'merchant-id': this.options.merchantId,
            'currency': this.options.currency,
            'intent': 'authorize',
            'locale': this.options.locale,
            'commit': 'false',
            'vault': 'false',
            'disable-funding': 'card,sepa,bancontact',
            ...(this.options.showPayLaterButton ? {
                'enable-funding': 'paylater',
            } : {}),
        }).toString();
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
            fetch(this.options.createCheckoutSessionUrl, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json",
                    // hilfreich in Shopware, damit kein HTML-Layout zurückkommt:
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then(async(response) => {
                    if (!response.ok) {
                        // Fehlertext sichern, bevor der Stream "verbraucht" ist
                        const errorText = await response.text().catch(() => "");
                        // wirf etwas Sinnvolles inkl. Status
                        throw new Error(errorText || `HTTP ${ response.status }`);
                    }
                    // wenn du JSON erwartest:
                    return response.json();
                })
                .then((data) => {
                    resolve(data.orderId);
                })
                .catch((err) => {
                    reject(err);
                });
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
