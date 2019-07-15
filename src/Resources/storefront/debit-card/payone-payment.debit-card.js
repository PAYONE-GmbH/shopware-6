/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';
import PseudoModalUtil from 'src/script/utility/modal-extension/pseudo-modal.util';
import PageLoadingIndicatorUtil from 'src/script/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentDebitCard extends Plugin {
    static options = {
        editorModalClass: 'payone-debit-modal',
    };

    init() {
        this.orderFormDisabled = false;

        this._client = new HttpClient(window.accessKey, window.contextToken);
        this._registerEvents();
    }

    _registerEvents() {
        document
            .getElementById('confirmOrderForm')
            .addEventListener("submit", this._handleOrderSubmit.bind(this));
    }

    _handleOrderSubmit(event) {
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

    _openModal(response) {
        const pseudoModal = new PseudoModalUtil(response);

        PageLoadingIndicatorUtil.remove();
        pseudoModal.open();
    }

    _getRequestData() {
        let iban = document.getElementById('iban');
        let bic = document.getElementById('bic');

        return {
            'iban': iban.value,
            'bic': bic.value
        };
    }

    _getManageMandateUrl() {
        let configuration = document.getElementById('payone-configuration');

        return configuration.getAttribute('data-manage-mandate-url');
    }
}
