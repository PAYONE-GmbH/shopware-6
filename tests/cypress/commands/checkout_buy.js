Cypress.Commands.add('checkoutBuy', () => {
    cy.get('#sAGB').check()
    cy.get('.main--actions button').click()
    cy.wait(5000)
    cy.url().should('include', 'checkout/finish')
})
