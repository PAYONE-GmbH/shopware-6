Cypress.Commands.add('selectPaymentMethod', (name) => {
    cy.url().should('include', '/checkout/confirm')
    cy.get('*[data-target="#confirmPaymentModal"]').should('contain.text','Change payment').click();
    cy.wait(5000);
    cy.contains(name).click()
    cy.get('#confirmPaymentForm *[type="submit"]').should('contain.text', 'Save').click()
})
