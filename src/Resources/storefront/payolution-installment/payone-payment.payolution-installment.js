/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/script/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentPayolutionInstallment extends Plugin {
    init() {
        this.orderFormDisabled = true;

        this._client = new HttpClient(window.accessKey, window.contextToken);

        this._disableSubmitButton();
        this._registerEventListeners();
    }

    _registerEventListeners() {
        const form = document.getElementById('confirmOrderForm');
        const buttonPreCheck = document.getElementById('checkInstallmentButton');

        if (form) {
            form.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }

        if (buttonPreCheck) {
            buttonPreCheck.addEventListener('click', this._handlePreCheckButtonClick.bind(this));
        }
    }

    _handlePreCheckButtonClick(event) {
        this._validateConstentCheckbox(event);
        this._validateBirthdayInput(event);

        if (event.defaultPrevented) {
            return;
        }

        PageLoadingIndicatorUtil.create();

        const data = JSON.stringify(this._getRequestData());

        this._client.abort();
        this._client.post(this._getPreCheckUrl(), data, response => this._handlePreCheckCallback(response));
    }

    _handlePreCheckCallback(response) {
        PageLoadingIndicatorUtil.remove();

        if (response.status !== 'OK') {
            return;
        }

        // TODO: save workorder id to dom (and use during preAuth)
        // TODO: save carthash to dom (and validate inside the paymenthandler)
        // TODO: if a payment plan is selected, activate order submit button
    }

    _handleOrderSubmit(event) {
        if (!this.orderFormDisabled) {
            return;
        }

        this._validateConstentCheckbox(event);
        this._validateBirthdayInput(event);

        if (event.defaultPrevented) {
            return;
        }


        // TODO: validate that a payment plan was selected
    }

    _disableSubmitButton() {
        this.orderFormDisabled = true;

        const button = document.getElementById('confirmFormSubmit');

        if (button) {
            button.setAttribute('disabled', 'disabled');
        }
    }

    _activateSubmitButton() {
        this.orderFormDisabled = false;

        const button = document.getElementById('confirmFormSubmit');

        if (button) {
            button.removeAttribute('disabled');
        }
    }

    _getPreCheckUrl() {
        const configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-precheck-url');
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

    _validateBirthdayInput(event) {
        const input = document.getElementById('payolutionBirthday');

        if (input.value) {
            input.classList.remove('is-invalid');

            return;
        }

        input.scrollIntoView({
            block: 'start',
            behavior: 'smooth',
        });

        input.classList.add('is-invalid');

        event.preventDefault();
    }

    _getModal(event) {
        event.preventDefault();

        PageLoadingIndicatorUtil.create();

        const data = this._getRequestData();

        this._client.abort();
        this._client.post(this._getManageMandateUrl(), JSON.stringify(data), content => this._openModal(content));
    }

    _submitForm() {
        this._activateSubmitButton();

        const form = document.getElementById('confirmOrderForm');

        if (form) {
            form.submit();
        }
    }

    _getRequestData() {
        const birthday = document.getElementById('payolutionBirthday');

        return {
            'payolutionBirthday': birthday.value,
        };
    }

    _getManageMandateUrl() {
        const configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-manage-mandate-url');
    }

    _registerEvents() {
        document
            .getElementById('mandateSubmit')
            .addEventListener('click', this._onMandateSubmit.bind(this));
    }

    _onMandateSubmit() {
        const checkbox = document.getElementById('accept-mandate');

        if (checkbox.checked) {
            this._submitForm();
        }
    }
}
