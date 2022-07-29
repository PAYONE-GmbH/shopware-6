import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from "src/service/http-client.service";


export default class PayonePaymentRatepayInstallment extends Plugin {
    static options = {
        ratepayRateInputSelector: '#ratepayRate',
        ratepayRuntimeInputSelector: '#ratepayRuntime',
        calculateInstallmentBtnSelector: '#payone-ratepay-calculate-installment-btn',
        ratepayInstallmentPlanContainerSelector: '#payone-ratepay-installment-plan',
        ratepayIbanContainerSelector: '#ratepayIbanContainer',
        ratepayIbanInputSelector: '#ratepayIban'
    }

    init() {
        this.csrfToken = document.getElementById('payoneCsrfTokenRatepayInstallmentCalculation');

        this._client = new HttpClient();

        this.calculateInstallmentBtn = DomAccess.querySelector(document, this.options.calculateInstallmentBtnSelector);
        this.ratepayRuntimeInput = DomAccess.querySelector(document, this.options.ratepayRuntimeInputSelector);
        this.ratepayIbanContainer = DomAccess.querySelector(document, this.options.ratepayIbanContainerSelector);
        this.ratepayIbanInput = DomAccess.querySelector(document, this.options.ratepayIbanInputSelector);

        this._registerEventListeners();
    }

    _registerEventListeners() {
        if (this.calculateInstallmentBtn) {
            this.calculateInstallmentBtn.addEventListener('click', this._handleCalculateInstallmentButtonClick.bind(this));
        }

        if (this.ratepayRuntimeInput) {
            this.ratepayRuntimeInput.addEventListener('change', this._handleInstallmentRuntimeChange.bind(this));
        }

        $(this.options.ratepayIbanContainerSelector).on('shown.bs.collapse', this._handleOpenedIbanContainer.bind(this));
        $(this.options.ratepayIbanContainerSelector).on('hidden.bs.collapse', this._handleClosedIbanContainer.bind(this));
    }

    _handleCalculateInstallmentButtonClick() {
        const value = DomAccess.querySelector(document, this.options.ratepayRateInputSelector).value;

        this._sendRequest('rate', value);
    }

    _handleInstallmentRuntimeChange() {
        const value = DomAccess.querySelector(document, this.options.ratepayRuntimeInputSelector).value;

        this._sendRequest('time', value);
    }

    _sendRequest(type, value) {
        let requestData = {
            '_csrf_token': this.csrfToken.value
        }

        if(type === 'time') {
            requestData.ratepayInstallmentType = 'time';
            requestData.ratepayInstallmentMonth = value;
        }

        if(type === 'rate') {
            requestData.ratepayInstallmentType = 'rate';
            requestData.ratepayInstallmentRate = value;
        }

        const data = JSON.stringify(requestData);

        this._client.abort();
        this._client.post("/payone/ratepay/installment/calculation", data, response => this._handleCalculationCallback(response));
    }

    _handleCalculationCallback(response) {
        const ratepayInstallmentPlanContainer = DomAccess.querySelector(document, this.options.ratepayInstallmentPlanContainerSelector);

        ratepayInstallmentPlanContainer.innerHTML = response;
    }

    _handleOpenedIbanContainer() {
        this.ratepayIbanInput.required = true;
    }

    _handleClosedIbanContainer() {
        this.ratepayIbanInput.required = false;
    }
}
