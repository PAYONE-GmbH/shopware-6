describe('Pay Later Invoice Test - Success', function () {
    it('Buy with Pay Later Invoice', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Pay Later Invoice');

        cy.get('#payolutionBirthday').type('1990-01-01');
        cy.get('#payolutionConsent').check({force: true});

        cy.finishCheckout()
    })
})
