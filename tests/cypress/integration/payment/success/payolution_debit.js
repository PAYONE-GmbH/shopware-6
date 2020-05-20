describe('Pay Later Debit Test - Success', function () {
    it('Buy with Pay Later Debit', function () {
        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Pay Later Debit');

        cy.get('#payolutionAccountHolder').type('Payone Testkäufer');
        cy.get('#payolutionIban').type('DE00123456782599100010');
        cy.get('#payolutionBic').type('TESTTEST');
        cy.get('#payolutionBirthday').type('1990-01-01');
        cy.get('#payolutionConsent').check({force: true});
        cy.get('#payolutionMandate').check({force: true});

        cy.finishCheckout()
    })
})
