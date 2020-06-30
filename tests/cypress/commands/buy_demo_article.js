Cypress.Commands.add('buyDemoArticle', () => {
    cy.server();
    cy.route({
        method: 'POST',
        url: '*checkout/line-item/add*'
    }).as('addProduct');
    cy.route({
        method: 'GET',
        url: '*widgets/checkout/info*'
    }).as('ajaxCart');

    cy.visit('/');
    cy.contains('Add to shopping cart').first().click();
    cy.wait('@addProduct');
    cy.wait('@ajaxCart');
    cy.get('.cart-item-quantity-container > .custom-select').select('3').should('have.value', '3');
    cy.wait('@ajaxCart');
    cy.contains('Proceed to checkout').click();
})
