/* eslint-disable import/no-unresolved */

import Plugin from 'src/plugin-system/plugin.class';
import StoreApiClient from 'src/service/store-api-client.service';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentDebitCard extends Plugin {
    static options = {
        editorModalClass: 'payone-debit-modal'
    };

    init() {
        this.orderFormDisabled = true;

        this._client = new StoreApiClient();

        document
            .getElementById('confirmOrderForm')
            .addEventListener('submit', this._handleOrderSubmit.bind(this));
    }

    _handleOrderSubmit(event) {
        const errorOutput = document.getElementById('errorOutput');

        errorOutput.style.display = 'none';

        if (!this.orderFormDisabled) {
            return;
        }

        event.preventDefault();

        this._getModal(event);
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
        const csrfToken = document.getElementById('payoneCsrfTokenDebitManageMandate');
        const iban = document.getElementById('iban');
        const bic = document.getElementById('bic');

        return {
            '_csrf_token': csrfToken.value,
            'iban': iban.value,
            'bic': bic.value
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
