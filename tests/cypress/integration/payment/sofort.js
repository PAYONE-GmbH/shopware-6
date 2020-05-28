describe('Sofort Test', function () {
    it('Buy with Sofort', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Payone Sofort');

        cy.get('#tos').check({force: true});
        cy.get('#confirmFormSubmit').click({force: true});

        // Needs some cookies
        // cy.url().should('include', 'www.sofort.com/payment');
    })
})
