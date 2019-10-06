/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';

export default class PayonePaymentPayolutionInvoicing extends Plugin {
    init() {
        this._registerEventListeners();
    }

    _registerEventListeners() {
        const form = document.getElementById('confirmOrderForm');

        if (form) {
            form.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }
    }

    _handleOrderSubmit(event) {
        this._validateConstentCheckbox(event);
    }

    _validateConstentCheckbox(event) {
        const checkbox = document.getElementById('payolutionConsent');

        if (checkbox.checked) {
            checkbox.classList.remove('is-invalid');

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
