/* eslint-disable import/no-unresolved */

const Plugin = window.PluginBaseClass;

export default class PayonePaymentApplePay extends Plugin {
    static options = {
        countryCode: '',
        currencyCode: '',
        supportedNetworks: [],
        merchantCapabilities: ['supports3DS', 'supportsDebit', 'supportsCredit'],
        total: {
            label: '',
            type: 'final',
            amount: '0.01'
        },
    };

    static session;
    static client;
    static validateMerchantUrl;
    static processPaymentUrl;
    static orderForm;

    init() {
        if(this.options.supportedNetworks === null) {
            this.options.supportedNetworks = [];
        }

        this.validateMerchantUrl = this.el.dataset.validateMerchantUrl;
        this.processPaymentUrl = this.el.dataset.processPaymentUrl;
        this.orderForm = document.querySelector('#confirmOrderForm');

        this._registerEventHandler();
    }

    createSession() {
        try {
            this.session = new ApplePaySession(3, this.options);
        }
        catch (e) {
            this.handleErrorOnPayment();
            return;
        }

        this.session.addEventListener('validatemerchant', this.validateMerchant.bind(this));
        this.session.addEventListener('paymentauthorized', this.authorizePayment.bind(this));
    }

    performPayment() {
        this.session.begin();
    }

    validateMerchant(event) {
        const validationUrl = event.validationURL;

        fetch(this.validateMerchantUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ validationUrl: validationUrl })
        })
            .then(response => response.text())
            .then((response) => {
                let merchantSession = null;

                try {
                    merchantSession = JSON.parse(response);
                } catch (e) {
                    this.handleErrorOnPayment();
                    return;
                }

                if (!merchantSession || !merchantSession.merchantSessionIdentifier || !merchantSession.signature) {
                    this.handleErrorOnPayment();
                    return;
                }

                this.session.completeMerchantValidation(merchantSession);
            });
    }

    handleErrorOnPayment() {
        const errorContainer = document.querySelector('#payone-apple-pay-error');
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({block: 'start'});
    }

    authorizePayment(event) {
        let orderId = this.orderForm.querySelector('input[name=\'orderId\']').value;

        fetch(this.processPaymentUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ token: event.payment.token, orderId: orderId })
        })
            .then(response => response.text())
            .then((response) => {
                this.completePayment(response);
                this.orderForm.submit();
            });
    }

    completePayment(response) {
        let txid = '';
        let status = '';
        let userid = '';

        try {
            let responseData = JSON.parse(response);
            status = responseData.status;
            txid = responseData.txid;
            userid = responseData.userid;
        } catch (e) {
            this.orderForm.submit();
        }

        this.updateFormData(status, txid, userid, response);

        if(status === 'APPROVED' || status === 'PENDING') {
            this.session.completePayment({
                status: ApplePaySession.STATUS_SUCCESS,
                errors: [],
            });
        }

        this.orderForm.submit();
    }

    updateFormData(status, txid, userid, response) {
        this.orderForm.querySelector('input[name=\'status\']').value = status;
        this.orderForm.querySelector('input[name=\'txid\']').value = txid;
        this.orderForm.querySelector('input[name=\'userid\']').value = userid;
        this.orderForm.querySelector('input[name=\'response\']').value = response;
    }

    _handleApplePayButtonClick() {
        if (!this.orderForm.reportValidity()) {
            return;
        }

        this.createSession();
        this.performPayment();
    }

    _registerEventHandler() {
        this.el.addEventListener('click', this._handleApplePayButtonClick.bind(this));
    }
}
