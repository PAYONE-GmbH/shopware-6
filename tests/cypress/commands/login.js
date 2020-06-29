Cypress.Commands.add('login', () => {
    cy.url().should('include', 'checkout/register');
    cy.get('.register-login-collapse-toogle').click();
    cy.get('#loginMail').type('demo@payone.demo');
    cy.get('#loginPassword').type('demo@payone.demo');
    cy.get('.login-submit button').should('contain.text', 'Login').click();
})
