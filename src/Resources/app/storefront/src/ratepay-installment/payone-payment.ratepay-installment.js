const Plugin = window.PluginBaseClass;

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
        this.ratepayRateInput = document.querySelector(this.options.ratepayRateInputSelector);
        this.calculateInstallmentBtn = document.querySelector(this.options.calculateInstallmentBtnSelector);
        this.ratepayRuntimeInput = document.querySelector(this.options.ratepayRuntimeInputSelector);
        this.ratepayIbanContainer = document.querySelector(this.options.ratepayIbanContainerSelector);
        this.ratepayIbanInput = document.querySelector(this.options.ratepayIbanInputSelector);

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

        document.querySelector(this.options.ratepayIbanContainerSelector).addEventListener('shown.bs.collapse', () => (this.ratepayIbanInput.required = true));
        document.querySelector(this.options.ratepayIbanContainerSelector).addEventListener('hidden.bs.collapse', () => (this.ratepayIbanInput.required = false));
    }

    _handleCalculateInstallmentButtonClick() {
        const value = this.ratepayRateInput.value;

        this._sendRequest('rate', value);
    }

    _handleInstallmentRuntimeChange() {
        const value = document.querySelector(this.options.ratepayRuntimeInputSelector).value;

        this._sendRequest('time', value);
    }

    _sendRequest(type, value) {
        let requestData = {
            ratepayInstallmentType: type,
            ratepayInstallmentValue: value
        };

        const data = JSON.stringify(requestData);

        fetch("/payone/ratepay/installment/calculation", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: data
        })
            .then(response => response.text())
            .then((response) => {
                this._handleCalculationCallback(response);
            });
    }

    _handleCalculationCallback(response) {
        this._replaceCalculationContent(response);
    }

    _replaceCalculationContent(response){
        const ratepayInstallmentPlanContainer = document.querySelector(this.options.ratepayInstallmentPlanContainerSelector);

        ratepayInstallmentPlanContainer.innerHTML = response;

        this.ratepayInstallmentTable = document.querySelector(this.options.ratepayInstallmentTableSelector);

        this.ratepayRuntimeInput.value = this.ratepayInstallmentTable.dataset.ratepayNumberOfRates;
        this.ratepayRateInput.value = this.ratepayInstallmentTable.dataset.ratepayRate;
    }
}
