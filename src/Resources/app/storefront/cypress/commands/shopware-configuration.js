Cypress.Commands.add('setShopwareConfig', (config) => {
    return cy.requestAdminApi('POST', 'api/_action/system-config/batch', {
        data: {
            'null': config
        }
    })
});
