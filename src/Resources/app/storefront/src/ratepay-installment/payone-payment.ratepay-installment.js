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
        ratepayIbanInputSelector: '#ratepayIban',
        ratepayInstallmentTableSelector: '#ratepayInstallmentTable'
    }

    init() {
        this._client = new HttpClient();

        this.ratepayRateInput = DomAccess.querySelector(document, this.options.ratepayRateInputSelector);
        this.calculateInstallmentBtn = DomAccess.querySelector(document, this.options.calculateInstallmentBtnSelector);
        this.ratepayRuntimeInput = DomAccess.querySelector(document, this.options.ratepayRuntimeInputSelector);
        this.ratepayIbanContainer = DomAccess.querySelector(document, this.options.ratepayIbanContainerSelector);
        this.ratepayIbanInput = DomAccess.querySelector(document, this.options.ratepayIbanInputSelector);

        this._registerEventListeners();

        this._handleInstallmentRuntimeChange();
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
        const value = this.ratepayRateInput.value;

        this._sendRequest('rate', value);
    }

    _handleInstallmentRuntimeChange() {
        const value = DomAccess.querySelector(document, this.options.ratepayRuntimeInputSelector).value;

        this._sendRequest('time', value);
    }

    _sendRequest(type, value) {
        let requestData = {
            ratepayInstallmentType: type,
            ratepayInstallmentValue: value
        };

        const data = JSON.stringify(requestData);

        this._client.abort();
        this._client.post("/payone/ratepay/installment/calculation", data, response => this._handleCalculationCallback(response));
    }

    _handleCalculationCallback(response) {
        this._replaceCalculationContent(response);
    }

    _replaceCalculationContent(response){
        const ratepayInstallmentPlanContainer = DomAccess.querySelector(document, this.options.ratepayInstallmentPlanContainerSelector);

        ratepayInstallmentPlanContainer.innerHTML = response;

        this.ratepayInstallmentTable = DomAccess.querySelector(document, this.options.ratepayInstallmentTableSelector);

        this.ratepayRuntimeInput.value = this.ratepayInstallmentTable.dataset.ratepayNumberOfRates;
        this.ratepayRateInput.value = this.ratepayInstallmentTable.dataset.ratepayRate;
    }

    _handleOpenedIbanContainer() {
        this.ratepayIbanInput.required = true;
    }

    _handleClosedIbanContainer() {
        this.ratepayIbanInput.required = false;
    }
}
