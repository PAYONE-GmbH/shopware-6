/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

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

        this._httpClient = new HttpClient();
        this.validateMerchantUrl = this.el.dataset.validateMerchantUrl;
        this.processPaymentUrl = this.el.dataset.processPaymentUrl;
        this.orderForm = DomAccess.querySelector(document, '#confirmOrderForm');

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

        this._httpClient.abort();
        this._httpClient.post(this.validateMerchantUrl, JSON.stringify({validationUrl: validationUrl}), (response) => {
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
        })
    }

    handleErrorOnPayment() {
        const errorContainer = DomAccess.querySelector(document, '#payone-apple-pay-error');
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({block: 'start'});
    }

    authorizePayment(event) {
        let orderId = DomAccess.querySelector(this.orderForm, 'input[name=\'orderId\']').value;

        this._httpClient.abort();
        this._httpClient.post(this.processPaymentUrl, JSON.stringify({token: event.payment.token, orderId: orderId}), (response) => {
            this.completePayment(response);
            this.orderForm.submit();
        })
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
        DomAccess.querySelector(this.orderForm, 'input[name=\'status\']').value = status;
        DomAccess.querySelector(this.orderForm, 'input[name=\'txid\']').value = txid;
        DomAccess.querySelector(this.orderForm, 'input[name=\'userid\']').value = userid;
        DomAccess.querySelector(this.orderForm, 'input[name=\'response\']').value = response;
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
