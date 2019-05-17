import { Application } from 'src/core/shopware';
import ApiService from 'src/core/service/api.service';

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

