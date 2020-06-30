describe('Pay Later Invoice Test', function () {
    it('Buy with Pay Later Invoice', function () {
        cy.server();
        cy.route({
            method: 'POST',
            url: '*payone/debit/manage-mandate*'
        }).as('calculateInvoice');
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Pay Later Invoice');

        cy.get('#payolutionBirthday').type('1990-01-01');
        cy.get('#payolutionConsent').check({force: true});

        cy.finishCheckout()
    })
})
