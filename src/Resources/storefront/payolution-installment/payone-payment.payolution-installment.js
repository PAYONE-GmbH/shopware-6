/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';
import PseudoModalUtil from 'src/script/utility/modal-extension/pseudo-modal.util';
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

        if (form) {
            form.addEventListener('submit', this._handleOrderSubmit.bind(this));
        }
    }

    _handleOrderSubmit(event) {
        if (!this.orderFormDisabled) {
            return;
        }

        this._validateConstentCheckbox(event);
        this._preCheck();
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

    _preCheck() {
        this._client.abort();
        this._client.get(this._getPreCheckUrl(), [], content => this._openModal(content));
    }

    _getPreCheckUrl() {
        const configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-precheck-url');
    }

    _validateConstentCheckbox(event) {
        const checkbox = document.getElementById('payolutionConsent');

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

    _getModal(event) {
        event.preventDefault();

        PageLoadingIndicatorUtil.create();

        const data = this._getRequestData();

        this._client.abort();
        this._client.post(this._getManageMandateUrl(), JSON.stringify(data), content => this._openModal(content));
    }

    _submitForm() {
        this.orderFormDisabled = false;

        document
            .getElementById('confirmOrderForm')
            .submit();
    }

    _openModal(response) {
        response = JSON.parse(response);

        if (response.error) {
            const errorOutput = document.getElementById('errorOutput');

            errorOutput.innerHTML = response.error;
            errorOutput.style.display = 'block';

            PageLoadingIndicatorUtil.remove();

            return;
        }

        if (response.mandate.Status === 'active') {
            this._submitForm();

            return;
        }

        const pseudoModal = new PseudoModalUtil(response.modal_content);

        pseudoModal.open(this._onOpen.bind(this, pseudoModal));
    }

    _onOpen(pseudoModal) {
        const modal = pseudoModal.getModal();

        modal.classList.add('payone-debit-mandate-modal');
        window.PluginManager.initializePlugins();

        this._registerEvents();

        PageLoadingIndicatorUtil.remove();
    }

    _getRequestData() {
        const iban = document.getElementById('iban');
        const bic = document.getElementById('bic');

        return {
            'iban': iban.value,
            'bic': bic.value,
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
