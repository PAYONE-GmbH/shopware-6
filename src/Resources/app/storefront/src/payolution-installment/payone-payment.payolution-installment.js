/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

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

        this._validateField(event, 'payolutionConsent');
        this._validateInput(event, 'payolutionBirthday');

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
        this._displayCalculationOverview(response);
        this._registerSelectionEventListeners();
        this._enableSecondStep();
        this._activateSubmitButton();
        this._hideCheckInstallmentButton();
    }

    _hideCheckInstallmentButton() {
        const buttonCalculation = document.getElementById('checkInstallmentButton');

        if (buttonCalculation) {
            buttonCalculation.classList.add('hidden');
        }
    }

    _registerSelectionEventListeners() {
        const select = document.getElementById('payolutionInstallmentDuration');

        select.addEventListener ('change', function (event) {
            const duration = event.target.value;
            const elements = document.querySelectorAll('.installmentDetail');

            elements.forEach(function(element) {
                if (element.dataset.duration === duration) {
                    element.hidden = false;
                } else {
                    element.hidden = 'hidden';
                }
            });
        });
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
        this._validateField(event, 'payolutionConsent');
        this._validateInput(event, 'payolutionBirthday');
        this._validateInput(event, 'payolutionAccountOwner');
        this._validateInput(event, 'payolutionIban');
        this._validateInput(event, 'payolutionBic');
        this._validateInput(event, 'payolutionInstallmentDuration');
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

    _validateField(event, field) {
        const checkbox = document.getElementById(field);

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

    _validateInput(event, field) {
        const input = document.getElementById(field);

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

    _getRequestData() {
        const csrfToken = document.getElementById('payoneCsrfTokenPayolutionInstallmentCalculation');
        const birthday = document.getElementById('payolutionBirthday');
        const workorder = document.getElementById('payoneWorkOrder');
        const carthash = document.getElementById('payoneCartHash');

        return {
            '_csrf_token': csrfToken.value,
            'payolutionBirthday': birthday.value,
            'workorder': workorder.value,
            'carthash': carthash.value,
        };
    }
}
