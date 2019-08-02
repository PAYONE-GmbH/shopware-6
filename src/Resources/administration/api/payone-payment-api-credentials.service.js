import { Application } from 'src/core/shopware';
import ApiService from 'src/core/service/api.service';

class PayonePaymentApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'payone_payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(credentials) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                {
                    credentials: credentials,
                },
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('PayonePaymentApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new PayonePaymentApiCredentialsService(initContainer.httpClient, container.loginService);
});

