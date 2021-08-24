/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';

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

    init() {
        //TODO: loading indicator, remove on error
        this._registerEventHandler();
    }

    createSession() {
        //TODO: remove
        this.options.total.amount = 0.01;
        this.session = new ApplePaySession(3, this.options);
    }

    performPayment() {
        this.session.begin();
        this.session.addEventListener('validatemerchant', this.validateMerchant.bind(this));
        console.log(this.session);
    }

    validateMerchant(event) {
    console.log('validate merchant');
    console.log(event);
        //TODO: implement merchant validation
    }

    _handleApplePayButtonClick() {
        const form = DomAccess.querySelector(document, '#confirmOrderForm');
        if(form.reportValidity() === false) {
            return;
        }

        this.createSession();
        this.performPayment();
        this.validateMerchant();
    }

    _registerEventHandler() {
        this.el.addEventListener('click', this._handleApplePayButtonClick.bind(this));
    }
}
