const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class PayonePaymentSettingsService extends ApiService {
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

    getStateMachineTransitionActions() {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/get-state-machine-transition-actions`,
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

}

Application.addServiceProvider('PayonePaymentSettingsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new PayonePaymentSettingsService(initContainer.httpClient, container.loginService);
});

