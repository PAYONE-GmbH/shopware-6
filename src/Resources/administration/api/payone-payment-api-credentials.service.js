import { Application } from 'src/core/shopware';
import ApiService from 'src/core/service/api.service';

class PayonePaymentApiCredentialsService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'payone_payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateApiCredentials(merchantId, accountId, portalId, portalKey, transactionMode) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/validate-api-credentials`,
                {
                    params: { merchantId, accountId, portalId, portalKey, transactionMode },
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

