import PayonePaymentAmazon from './default'

export default class PayonePaymentAmazonPayExpressButton extends PayonePaymentAmazon {
    onLoad() {
        window.amazon.Pay.renderButton('#' + this.el.id, this.options);
    }
}
