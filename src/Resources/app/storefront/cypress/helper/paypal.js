export function processPayment() {
    return cy.origin('www.sandbox.paypal.com', () => {
        cy.wait(1200)
        cy.get('#email').clear().type(Cypress.env('paypalMail'));
        cy.get('#email').type('{enter}');
        cy.get('#password').type(Cypress.env('paypalPassword'), {force: true});
        cy.get('#btnLogin').click({force: true});
        cy.wait(3000)
        cy.get('#payment-submit-btn').click();
    })
}
