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
        const buttonCalculation = document.getElementById('checkInstallmentButton');

        if (form) {
            form.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }

        if (buttonCalculation) {
            buttonCalculation.addEventListener('click', this._handleCalculationButtonClick.bind(this));
        }
    }

    _handleCalculationButtonClick(event) {
        this._hideErrorBox();
        this._validateConstentCheckbox(event);
        this._validateBirthdayInput(event);

        if (event.defaultPrevented) {
            return;
        }

        PageLoadingIndicatorUtil.create();

        const data = JSON.stringify(this._getRequestData());

        this._client.abort();
        this._client.post(this._getCalculationUrl(), data, response => this._handleCalculationCallback(response));
    }

    _handleCalculationCallback(response) {
        response = JSON.parse(response);

        PageLoadingIndicatorUtil.remove();

        if (response.status !== 'OK') {
            this._showErrorBox();

            return;
        }

        const workorder = document.getElementById('payoneWorkOrder');
        const carthash = document.getElementById('payoneCartHash');

        workorder.value = response.workorderid;
        carthash.value = response.carthash;

        this._displayInstallmentSelection(response);
        this._displayInstallmentSelection(response);
        this._enableSecondStep();
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

    _enableSecondStep() {
        const elements = document.querySelectorAll('.payolution-installment .hidden');

        elements.forEach(function(element) {
            element.classList.remove('hidden')
        });
    }

    _displayInstallmentSelection(response) {
        const container = document.getElementById('installmentSelection');

        if (!container) {
            return;
        }

        container.innerHTML = response.installmentSelection;
    }

    _displayCalculationOverview(response) {
        const container = document.getElementById('calculationOverview');

        if (!container) {
            return;
        }

        container.innerHTML = response.calculationOverview;
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

    _getCalculationUrl() {
        const configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-calculation-url');
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

    _submitForm() {
        this._activateSubmitButton();

        const form = document.getElementById('confirmOrderForm');

        if (form) {
            form.submit();
        }
    }

    _getRequestData() {
        const birthday = document.getElementById('payolutionBirthday');
        const workorder = document.getElementById('payoneWorkOrder');
        const carthash = document.getElementById('payoneCartHash');

        return {
            'payolutionBirthday': birthday.value,
            'workorder': workorder.value,
            'carthash': carthash.value,
        };
    }
}
