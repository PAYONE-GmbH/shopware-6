describe('PayPal Test', function () {
    it('Buy with PayPal', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Payone PayPal');

        cy.get('#tos').check({force: true});
        cy.get('#confirmFormSubmit').click({force: true});

        cy.url().should('include', 'paypal.com');
    })
})
