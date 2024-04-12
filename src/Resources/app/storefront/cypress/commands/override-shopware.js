
function getAuthCookieValue() {
    return cy.getCookie('bearerAuth', {log: false}).then((cookie) => {
        return cookie ? JSON.parse(decodeURIComponent(cookie.value)) : null;
    });
}

Cypress.Commands.overwrite('getBearerAuth', () => {
    return validateAdminAuthenticationCookie().then((res) => {
        return !res ? cy.authenticate() : getAuthCookieValue();
    })
});

Cypress.Commands.overwrite('authenticate', () => {
    return cy.request(
        'POST',
        '/api/oauth/token',
        {
            grant_type: Cypress.env('grant') ? Cypress.env('grant') : 'password',
            client_id: Cypress.env('client_id') ? Cypress.env('client_id') : 'administration',
            scopes: Cypress.env('scope') ? Cypress.env('scope') : 'write',
            username: Cypress.env('username') || Cypress.env('user') || 'admin',
            password: Cypress.env('password') || Cypress.env('pass') || 'shopware'
        }
    ).then((responseData) => {
        let result = responseData.body;
        result.access = result.access_token;
        result.refresh = result.refresh_token;
        result.expiry = Math.round(+new Date() / 1000) + result.expires_in;

        return cy.setCookie(
            'bearerAuth',
            JSON.stringify(result),
            {
                path: Cypress.env('admin'),
                sameSite: "strict",
                log: false
            }
        ).then(() => getAuthCookieValue());
    });
});

function validateAdminAuthenticationCookie() {
    return getAuthCookieValue().then(cookieValue => {
        if (!cookieValue || cookieValue.expiry <= ((new Date().getTime()) / 1000)) {
            cy.clearCookie('bearerAuth');
            return false;
        }

        return cy.request({
            method: 'GET',
            url: '/api/_info/version',
            failOnStatusCode: true,
            headers: {
                Authorization: `Bearer ${cookieValue.access}`
            },
        }).then(() => true);
    });
}
