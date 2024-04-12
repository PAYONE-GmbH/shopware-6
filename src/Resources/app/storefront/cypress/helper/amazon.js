export function registerCommands() {
    cy.origin('payments.amazon.de', () => {
        Cypress.Commands.add('waitForAmazonSpinner', (spinnerSelector) => {
            cy.get(spinnerSelector, {timeout: 10000}).should('be.visible');
            cy.get(spinnerSelector, {timeout: 10000}).should(($el) => $el.length === 0 || !$el.is('visible'));
        });
        Cypress.Commands.add('waitForAmazonLogin', () => {
            cy.waitForAmazonSpinner('.a-spinner-wrapper');
            cy.wait(2000);
        });
    });
    cy.origin('www.amazon.de', () => {
        Cypress.Commands.add('amazonLogin', () => {
            cy.get('[name="email"]').type('frederik.rommel+testbestellung@webidea24.de');
            cy.get('[name="password"]').type('frederik.rommel+testbestellung@webidea24.de');
            cy.get('#signInSubmit').click();
        });
    });
}
export function processCheckout() {
    cy.origin('www.amazon.de', () => {
        cy.amazonLogin();
    });
    cy.origin('payments.amazon.de', () => {
        cy.waitForAmazonLogin();
        cy.get('[data-action="continue-checkout"] [type="submit"]').click();
        cy.waitForAmazonSpinner('.a-spinner');
    });

    return Promise.resolve();
}
