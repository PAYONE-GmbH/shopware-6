import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class PayonePaymentKlarna extends Plugin {
    static options = {
        clientToken: null,
        paymentMethodCategory: null,
        selectorContainer: null,
        selectorTokenInput: 'input[name="payoneKlarnaAuthorizationToken"]',
        selectorFormId: 'confirmOrderForm'
    };
    static orderForm;

    init() {
        window.klarnaAsyncCallback = this.initKlarnaWidget.bind(this);

        const scriptTag = document.createElement("script");
        scriptTag.src = "https://x.klarnacdn.net/kp/lib/v1/api.js";
        document.body.appendChild(scriptTag);

        this.orderForm = document.getElementById(this.options.selectorFormId);
        this._registerEventListeners();
    }

    initKlarnaWidget() {
        Klarna.Payments.init({
            client_token: this.options.clientToken
        })

        Klarna.Payments.load({
            container: this.options.selectorContainer,
            payment_method_category: this.options.paymentMethodCategory
        }, function (res) {
            console.debug(res);
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
    }

    _handleOrderSubmit(event) {
        event.preventDefault();

        Klarna.Payments.authorize({
            payment_method_category: this.options.paymentMethodCategory
        }, {}, (res) => {
            console.log(res);
            if (res.approved && res.authorization_token) {
                document.querySelector(this.options.selectorTokenInput).value = res.authorization_token;
                this.orderForm.submit();
            }
        });
    }
}
