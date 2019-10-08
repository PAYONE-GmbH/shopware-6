/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/script/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentPayolutionInvoicing extends Plugin {
    init() {
        this.orderFormDisabled = true;

        this._client = new HttpClient(window.accessKey, window.contextToken);

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

        this._validateConstentCheckbox(event);

        if (event.defaultPrevented) {
            return;
        }

        this._validatePaymentAcceptance();

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
        const birthday = document.getElementById('payolutionBirthday');

        return {
            'payolutionBirthday': birthday.value,
        };
    }
}
