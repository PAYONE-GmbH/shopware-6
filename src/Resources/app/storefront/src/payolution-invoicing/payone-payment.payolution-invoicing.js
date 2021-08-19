/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from "src/service/http-client.service";
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentPayolutionInvoicing extends Plugin {
    init() {
        this.orderFormDisabled = true;

        this._client = new HttpClient();

        this._registerEventListeners();
    }

    _registerEventListeners() {
        const form = document.getElementById('confirmOrderForm');

        if (form) {
            form.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }
    }

    _handleOrderSubmit(event) {
        this._hideErrorBox();

        if (!this.orderFormDisabled) {
            return;
        }

        this._validateField(event, 'payolutionConsent');
        this._validateInput(event, 'payolutionBirthday');

        if (event.defaultPrevented) {
            return;
        }

        this._validatePaymentAcceptance();

        event.preventDefault();
    }

    _validateField(event, field) {
        const checkbox = document.getElementById(field);

        if (checkbox.checked) {
            checkbox.classList.remove('is-invalid');

            return;
        }

        checkbox.scrollIntoView({
            block: 'start',
            behavior: 'smooth'
        });

        checkbox.classList.add('is-invalid');

        event.preventDefault();
    }

    _validateInput(event, field) {
        const input = document.getElementById(field);

        if (input.value) {
            input.classList.remove('is-invalid');

            return;
        }

        input.scrollIntoView({
            block: 'start',
            behavior: 'smooth'
        });

        input.classList.add('is-invalid');

        event.preventDefault();
    }

    _validatePaymentAcceptance() {
        const data = JSON.stringify(this._getRequestData());

        PageLoadingIndicatorUtil.create();

        this._client.abort();
        this._client.post(this._getValidateUrl(), data, response => this._handleValidateResponse(response));
    }

    _handleValidateResponse(response) {
        response = JSON.parse(response);

        PageLoadingIndicatorUtil.remove();

        if (response.status !== 'OK') {
            this._showErrorBox();
        } else {
            const workorder = document.getElementById('payoneWorkOrder');

            if (workorder) {
                workorder.value = response.workorderid;
            }

            this._submitForm();
        }
    }

    _getValidateUrl() {
        const configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-validate-url');
    }

    _showErrorBox() {
        const container = document.getElementById('payolutionErrorContainer');

        if (container) {
            container.hidden = false;
        }
    }

    _hideErrorBox() {
        const container = document.getElementById('payolutionErrorContainer');

        if (container) {
            container.hidden = true;
        }
    }

    _submitForm() {
        this.orderFormDisabled = false;

        document
            .getElementById('confirmOrderForm')
            .submit();
    }

    _getRequestData() {
        const csrfToken = document.getElementById('payoneCsrfTokenPayolutionInvoiceValidation');
        const birthday = document.getElementById('payolutionBirthday');

        return {
            '_csrf_token': csrfToken.value,
            'payolutionBirthday': birthday.value
        };
    }
}
