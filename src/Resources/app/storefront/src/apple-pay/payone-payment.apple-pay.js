/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import StoreApiClient from "src/service/store-api-client.service";

export default class PayonePaymentApplePay extends Plugin {
    static options = {
        countryCode: '',
        currencyCode: '',
        supportedNetworks: ['visa', 'masterCard'],
        merchantCapabilities: ['supports3DS'],
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

    init() {
        this.client = new StoreApiClient();
        this.validateMerchantUrl = this.el.dataset.validateMerchantUrl;
        this.processPaymentUrl = this.el.dataset.processPaymentUrl;

        this._registerEventHandler();
    }

    createSession() {
        //TODO: remove
        this.options.total.amount = 0.01;
        this.session = new ApplePaySession(3, this.options);

        this.session.addEventListener('validatemerchant', this.validateMerchant.bind(this));
        this.session.addEventListener('paymentauthorized', this.authorizePayment.bind(this));
    }

    performPayment() {
        this.session.begin();
    }

    validateMerchant(event) {
        const validationUrl = event.validationURL;

        this.client.abort();
        this.client.post(this.validateMerchantUrl, JSON.stringify({ validationUrl: validationUrl }), (response) => {
            let merchantSession = null;

            try {
                merchantSession = JSON.parse(response);
            } catch (e) {
                this.handleErrorOnPayment();
                return;
            }

            if(!merchantSession || !merchantSession.merchantSessionIdentifier || !merchantSession.signature) {
                this.handleErrorOnPayment();
                return;
            }

            this.session.completeMerchantValidation(merchantSession);
        })
    }

    handleErrorOnPayment() {
        //TODO: show error message
    }

    authorizePayment(event) {
        console.log('authorize');
        console.log(event);
        //TODO: implement authorization request
        //TODO: store response data to form
        //TODO: update transaction with data
        //TODO: session complete payment
    }

    _handleApplePayButtonClick() {
        const form = DomAccess.querySelector(document, '#confirmOrderForm');
        if(form.reportValidity() === false) {
            return;
        }

        this.createSession();
        this.performPayment();
    }

    _registerEventHandler() {
        this.el.addEventListener('click', this._handleApplePayButtonClick.bind(this));
    }
}
