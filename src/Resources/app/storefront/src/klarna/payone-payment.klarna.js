import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util';

export default class PayonePaymentKlarna extends Plugin {
    static options = {
        clientToken: null,
        paymentMethodCategory: null,

        selectorContainer: null,
        selectorTokenInput: null,
        selectorFormId: null,
        selectorConfirmKlarnaUsageContainerId: null,
        selectorConfirmKlarnaUsageId: null
    };

    static orderForm = null;
    static confirmFormSubmit = null;
    static confirmKlarnaUsageContainer = null;
    static confirmKlarnaUsageButton = null;
    static klarnaUsageConfirmed = false;
    static sessionStruct = null;

    init() {
        this.orderForm = document.getElementById(this.options.selectorFormId);
        this.confirmFormSubmit = document.getElementById('confirmFormSubmit');
        this.confirmFormSubmit = this.confirmFormSubmit ? this.confirmFormSubmit : this.orderForm.querySelector('[type=submit]'); // fallback for update-payment page
        this.confirmFormSubmit.disabled = true;

        this.confirmKlarnaUsageContainer = document.getElementById(this.options.selectorConfirmKlarnaUsageContainerId);
        this.confirmKlarnaUsageButton = document.getElementById(this.options.selectorConfirmKlarnaUsageId);

        this._registerEventListeners();
    }

    _confirmKlarnaUsage() {
        this.klarnaUsageConfirmed = true;
        this.confirmKlarnaUsageContainer.remove();
        ElementLoadingIndicatorUtil.create(this.el);

        const client = new HttpClient(window.accessKey, window.contextToken);
        let url = '/payone/klarna/create-session';

        let data = {};
        if (window.csrf.enabled && window.csrf.mode === 'twig') {
            data['_csrf_token'] = this.options.csrfToken;
        }

        let locationMatch = window.location.href.match(/account\/order\/edit\/([A-Za-z0-9]+)/);
        if (locationMatch && locationMatch.length === 2) {
            // payment get updated
            data['orderId'] = locationMatch[1];
        }

        client.post(url, JSON.stringify(data), (response) => {
            this.sessionStruct = JSON.parse(response);

            if (this.sessionStruct.status) {
                document.getElementById('payoneCartHash').value = this.sessionStruct.cartHash;
                document.getElementById('payoneWorkOrder').value = this.sessionStruct.workOrderId;
                window.klarnaAsyncCallback = this._initKlarnaWidget.bind(this);

                const scriptTag = document.createElement("script");
                scriptTag.src = "https://x.klarnacdn.net/kp/lib/v1/api.js";
                document.body.appendChild(scriptTag);
            } else {
                ElementLoadingIndicatorUtil.remove(this.el);
                this.el.classList.add('has-error');
            }
        });
    }

    _initKlarnaWidget() {
        Klarna.Payments.init({
            client_token: this.sessionStruct.clientToken
        });

        Klarna.Payments.load({
            container: this.options.selectorContainer,
            payment_method_category: this.sessionStruct.paymentMethodIdentifier
        }, (res) => {
            this.confirmFormSubmit.disabled = false;
            ElementLoadingIndicatorUtil.remove(this.el);
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

        if (status === 'APPROVED' || status === 'PENDING') {
            this.session.completePayment({
                status: ApplePaySession.STATUS_SUCCESS,
                errors: [],
            });
        }

        this.orderForm.submit();
    }

    _registerEventListeners() {
        if (this.orderForm) {
            this.orderForm.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }

        if (this.confirmKlarnaUsageButton) {
            this.confirmKlarnaUsageButton.addEventListener('click', this._confirmKlarnaUsage.bind(this));
        }
    }

    _handleOrderSubmit(event) {
        event.preventDefault();

        if (!this.klarnaUsageConfirmed || !this.sessionStruct) {
            return;
        }

        Klarna.Payments.authorize({
            payment_method_category: this.sessionStruct.paymentMethodIdentifier
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
