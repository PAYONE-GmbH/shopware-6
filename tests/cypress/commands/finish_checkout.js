Cypress.Commands.add('finishCheckout', () => {
    cy.get('#tos').check({force: true});
    cy.get('#confirmFormSubmit').click({force: true});
    cy.url().should('include', 'checkout/finish')
})
