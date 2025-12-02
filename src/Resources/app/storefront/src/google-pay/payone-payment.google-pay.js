import Plugin from 'src/plugin-system/plugin.class';

export default class PayoneGooglePayPlugin extends Plugin {

    static scriptLoaded = false;
    static scriptPromise = null;
    static paymentsClient = null;

    init() {
        this.config = this._parseConfig();
        if (!this.config) {
            return;
        }

        this.buttonContainer = this.el.querySelector('[data-payone-google-pay-button]');
        this.tokenField = this.el.querySelector('[data-google-pay-token]');
        this.orderForm = document.getElementById('confirmOrderForm');

        if (!this.buttonContainer || !this.tokenField || !this.orderForm) {
            console.warn('PayoneGooglePay: missing DOM elements');

            return;
        }

        this.merchantInfo = this._generateMerchantInfo();

        this._loadGooglePaySdk().then(() => {
            this._onSdkReady();
        }).catch(console.error);
    }

    _parseConfig() {
        try {
            return JSON.parse(this.el.dataset.payoneGooglePayOptions || '{}');
        } catch (err) {
            console.error('PayoneGooglePay: config parse error', err);

            return null;
        }
    }

    _generateMerchantInfo() {
        let merchantInfo = {};

        if (null !== this.config.googlePayMerchantId) {
            merchantInfo.merchantId = this.config.googlePayMerchantId;
        }

        if (null !== this.config.googlePayMerchantName) {
            merchantInfo.merchantName = this.config.googlePayMerchantName;
        }

        return merchantInfo;
    }

    /**
     * Loads the Google Pay script only once
     */
    _loadGooglePaySdk() {
        if (PayoneGooglePayPlugin.scriptPromise) {
            return PayoneGooglePayPlugin.scriptPromise;
        }

        PayoneGooglePayPlugin.scriptPromise = new Promise((resolve, reject) => {
            // Script already present?
            const existing = document.querySelector('script[src="https://pay.google.com/gp/p/js/pay.js"]');

            if (existing && PayoneGooglePayPlugin.scriptLoaded) {
                return resolve();
            }

            const script = existing || document.createElement('script');
            script.src = 'https://pay.google.com/gp/p/js/pay.js';
            script.async = true;

            script.onload = () => {
                PayoneGooglePayPlugin.scriptLoaded = true;
                resolve();
            };

            script.onerror = reject;

            if (!existing) document.head.appendChild(script);
        });

        return PayoneGooglePayPlugin.scriptPromise;
    }

    /**
     * Returns a singleton Google Payments client
     */
    _getPaymentsClient() {
        if (!PayoneGooglePayPlugin.paymentsClient) {
            PayoneGooglePayPlugin.paymentsClient = new google.payments.api.PaymentsClient({
                environment: this.config.environment,
                merchantInfo: this.merchantInfo,
            });
        }

        return PayoneGooglePayPlugin.paymentsClient;
    }

    _deepCopy(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    _onSdkReady() {
        const parameters = { gateway: 'payonegmbh' };

        if (null !== this.config.googlePayMerchantId) {
            parameters.gatewayMerchantId = this.config.googlePayMerchantId;
        }

        const baseRequest = Object.freeze({
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: [{
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    allowedCardNetworks: this.config.allowedCardNetworks || [],
                },
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters,
                },
            }],
            merchantInfo: this.merchantInfo,
        });

        this.baseRequest = baseRequest;

        const client = this._getPaymentsClient();
        client.isReadyToPay(this._deepCopy(baseRequest))
            .then((res) => {
                if (res.result) {
                    this._renderButton(client);
                }
            })
            .catch(console.error);
    }

    _renderButton(client) {
        const button = client.createButton({
            onClick: this._onClick.bind(this)
        });

        this.buttonContainer.innerHTML = '';
        this.buttonContainer.appendChild(button);
    }

    _onClick() {
        if (!this.orderForm.reportValidity()) {
            return;
        }

        const client = this._getPaymentsClient();

        const request = {
            ...this._deepCopy(this.baseRequest),
            transactionInfo: {
                countryCode: this.config.countryCode,
                currencyCode: this.config.currencyCode,
                totalPriceStatus: 'FINAL',
                totalPrice: this.config.totalPrice.toString(),
            }
        };

        client.loadPaymentData(request)
            .then((res) => {
                const token = res.paymentMethodData.tokenizationData.token;
                this.tokenField.value = btoa(token);
                this.orderForm.submit();
            })
            .catch(console.error);
    }
}
