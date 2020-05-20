describe('Sofort Test - Success', function () {
    it('Buy with Sofort', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Payone Sofort');

        cy.get('#tos').check({force: true});
        cy.get('#confirmFormSubmit').click({force: true});

        cy.url().should('include', 'www.sofort.com/payment');
        // Needs some cookies
    })
})
