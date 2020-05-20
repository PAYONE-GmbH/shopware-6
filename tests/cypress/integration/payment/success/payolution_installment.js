describe('Payolution installment Test - Success', function () {
    it('Buy with Pay Later Installment', function () {
        cy.server();
        cy.route({
            method: 'POST',
            url: '*payone/installment/calculation*'
        }).as('installmentCalculation');

        cy.buyDemoArticle();
        cy.register();
        cy.selectPaymentMethod('Pay Later Installment');

        cy.get('#payolutionBirthday').type('1990-01-01');
        cy.get('#payolutionConsent').check({force: true});
        cy.get('#checkInstallmentButton').click();
        cy.wait('@installmentCalculation');
        cy.get('#payolutionAccountOwner').type('Payone Testkäufer');
        cy.get('#payolutionIban').type('DE00123456782599100012');
        cy.get('#payolutionBic').type('TESTTEST');

        cy.finishCheckout()
    })
})
