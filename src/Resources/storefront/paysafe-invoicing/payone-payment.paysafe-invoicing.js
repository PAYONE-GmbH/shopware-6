/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';

export default class PayonePaymentPaysafeInvoicing extends Plugin {
    init() {
        this.orderFormDisabled = true;

        document
            .getElementById('confirmOrderForm')
            .addEventListener('submit', this._handleOrderSubmit.bind(this));
    }

    _handleOrderSubmit(event) {
        const checkbox = document.getElementById('paysafeInvoicingConsent');

        if (checkbox.checked) {
            return;
        }

        checkbox.scrollIntoView({
            block: 'start',
            behavior: 'smooth',
        });

        checkbox.classList.add('is-invalid');

        event.preventDefault();
    }
}
