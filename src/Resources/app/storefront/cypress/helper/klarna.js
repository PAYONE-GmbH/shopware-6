export const customerData = {
    firstName: 'Mock',
    lastName: 'Mock',
    street: 'Neue Schönhauser Str. 2',
    zipcode: 10178,
    city: 'Berlin',
    mail: 'customer@email.de'
}

export function executeKlarnaCheckout(doneCallback) {
    return cy.checkoutConfirmAndComplete(() => {
        cy.window().then((window) => {
            cy.stub(window, 'open').as('winOpen')
        });

        return cy.get('#payone-payment--klarna__container iframe').then(($el) => $el.contents().get('#root'));
    }, () => {
        return cy.get('@winOpen').should('be.called').then(() => doneCallback());
    });
}

export function executeKlarnaCheckoutCancel(doneCallback) {
    cy.get('#tos').check();
    cy.get('#payone-payment--klarna__container iframe').then(($el) => $el.contents().get('#root'));

    cy.window().then((window) => {
        cy.stub(window, 'open').as('winOpen')
    });

    cy.get('#confirmFormSubmit').click();

    cy.get('@winOpen').should('be.called');
    cy.visit('checkout/confirm');
    cy.get('#tos').check();

    return executeKlarnaCheckout(doneCallback);
}
