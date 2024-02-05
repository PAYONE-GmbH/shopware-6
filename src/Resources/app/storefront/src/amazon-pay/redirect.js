import Parent from './express'
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class PayonePaymentAmazonPayRedirect extends Parent {

    init() {
        ElementLoadingIndicatorUtil.create(document.querySelector('.content-main'));
        super.init();
    }

    onLoad() {
        super.onLoad();
        document.querySelector('#' + this.el.id).dispatchEvent(new CustomEvent('click'))
    }
}

