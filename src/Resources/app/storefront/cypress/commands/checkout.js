Cypress.Commands.add('preparePaymentCheckout', (paymentMethodId, userData = {}, context = {}) => {
    return cy.addProductToCart()
        .then(() => cy.checkoutRegister(userData))
        .then(() => cy.allowCurrencyForSalesChannel(context.currencyIso ??  'EUR'))
        .then(() => cy.selectPaymentMethod(paymentMethodId));
});

Cypress.Commands.add('selectPaymentMethod', (paymentMethodUuid) => {
    cy.allowPaymentMethod(paymentMethodUuid).then(() => {
        // navigate to checkout-confirm and select payment method
        cy.visit('/checkout/confirm');
        cy.get('[type="radio"][name="paymentMethodId"]').check(paymentMethodUuid, {
            force: true, // we will force selecting the value, because shopware may hide the option behind "show more"
        });
    });
});

function testFinishPage(asyncProcess) {
    if (typeof asyncProcess === 'function') {
        return asyncProcess().then(() => {
            cy.url({
                timeout: 20000 // higher timeout is for a few payment methods which does have longer background tasks
            }).should('contain', '/checkout/finish')
        })
    }
    cy.url().should('contain', '/checkout/finish');
}

Cypress.Commands.add('checkoutConfirmAndComplete', (fillDataTests, asyncProcess) => {
    cy.url().should('match', /((checkout\/(confirm|order))|account\/order)/); // account/order is only a fal
    typeof fillDataTests === 'function' ? fillDataTests() : null;
    cy.get('#tos').check();
    cy.get('#confirmFormSubmit').click();

    return testFinishPage(asyncProcess);
});

Cypress.Commands.add('checkoutConfirmAfterCancelAndComplete', (fillDataTests, asyncProcess) => {
    cy.url().should('contain', 'account/order/edit');
    typeof fillDataTests === 'function' ? fillDataTests() : null;
    cy.get('#confirmOrderForm button[type=submit]').click();

    return testFinishPage(asyncProcess);
});

Cypress.Commands.add('checkoutConfirmAndCancel', (fillDataTests, asyncProcess) => {
    cy.url().should('contain', '/checkout/confirm');
    cy.get('#tos').check();
    typeof fillDataTests === 'function' ? fillDataTests() : null;
    cy.get('#confirmFormSubmit').click();

    return asyncProcess().then(() => {
        cy.url().should('contain', '/account/order/edit')
    });
});

Cypress.Commands.add('checkoutConfirmFailValidation', (fillDataTests, testForErrors) => {
    cy.url().should('contain', 'checkout/confirm');
    typeof fillDataTests === 'function' ? fillDataTests() : null;
    cy.get('#tos').check();
    cy.get('#confirmFormSubmit').click();

    cy.url().should('match', /checkout\/(confirm|order)/);
    cy.get('.alert-danger[role=alert]').get('.alert-content').should('not.contain.text', 'error.VIOLATION::');

    typeof testForErrors === 'function' ? testForErrors() : null;
});
