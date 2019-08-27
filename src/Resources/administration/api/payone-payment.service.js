const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class PayonePaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'payone') {
        super(httpClient, loginService, apiEndpoint);
    }

    capturePayment(transaction) {
        const apiRoute = `_action/${this.getApiBasePath()}/capture-payment`;

        return this.httpClient.post(
            apiRoute,
            {
                transaction: transaction
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    refundPayment(transaction) {
        const apiRoute = `_action/${this.getApiBasePath()}/refund-payment`;

        return this.httpClient.post(
            apiRoute,
            {
                transaction: transaction
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

Application.addServiceProvider('PayonePaymentService', (container) => {
    const initContainer = Application.getContainer('init');

    return new PayonePaymentService(initContainer.httpClient, container.loginService);
});

