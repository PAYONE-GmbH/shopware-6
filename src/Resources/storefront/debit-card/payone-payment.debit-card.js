/* eslint-disable import/no-unresolved */

import Plugin from 'src/script/plugin-system/plugin.class';
import HttpClient from 'src/script/service/http-client.service';
import PageLoadingIndicatorUtil from 'src/script/utility/loading-indicator/page-loading-indicator.util';

export default class PayonePaymentDebitCard extends Plugin {
    static options = {
        url: window.router['frontend.payone.manage-mandate'],
        editorModalClass: 'payone-debit-modal',
    };

    init() {
        this._client = new HttpClient(window.accessKey, window.contextToken);
        this._registerEvents();
    }

    _registerEvents() {
        document
            .getElementById('confirmOrderForm')
            .addEventListener("submit", this._handleOrderSubmit.bind(this));
    }

    _handleOrderSubmit(event) {

    }

    _getModal(event) {
        event.preventDefault();

        PageLoadingIndicatorUtil.create();

        const data = this._getRequestData();

        this._client.abort();
        this._client.post(this.options.url, JSON.stringify(data), content => this._openModal(content));
    }

    _onOpen(pseudoModal) {
        const modal = pseudoModal.getModal();

        modal.classList.add(this.options.editorModalClass);
        window.PluginManager.initializePlugins();
    }

    _openModal(response) {
        const pseudoModal = new PseudoModalUtil(response);

        PageLoadingIndicatorUtil.remove();
        pseudoModal.open(this._onOpen.bind(this, pseudoModal));
    }

    _getRequestData() {
        return {
            'iban': 'test',
            'bic': 'test'
        };
    }
}
