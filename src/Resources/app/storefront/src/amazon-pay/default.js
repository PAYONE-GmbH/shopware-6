const Plugin = window.PluginBaseClass;

export default class PayonePaymentAmazon extends Plugin {
    init() {
        if (!('amazon' in window)) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = 'https://static-eu.payments-amazon.com/checkout.js';
            script.onload = this.onLoad.bind(this);
            document.head.appendChild(script);
        } else {
            this.onLoad()
        }
    }
    onLoad() {}
}
