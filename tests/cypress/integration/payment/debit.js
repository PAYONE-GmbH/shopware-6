describe('SEPA Lastschrift Test', function () {
    it('Buy with SEPA Lastschrift', function () {
        cy.server();
        cy.route({
            method: 'POST',
            url: '*payone/debit/manage-mandate*'
        }).as('manageMandate');

        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Payone SEPA')

        cy.get('#accountOwner').type('Payone Testkäufer');
        cy.get('#iban').type('DE00123456782599100010');
        cy.get('#bic').type('TESTTEST');

        cy.get('#tos').check({force: true});
        cy.get('#confirmFormSubmit').click({force: true});

        cy.wait('@manageMandate').then((xhr) => {
            cy.wait(5000);
            if(undefined !== xhr.responseBody.modal_content) {
                cy.get('#accept-mandate').check({force: true});
                cy.get('#mandateSubmit').click();
            }
            cy.url().should('include', 'checkout/finish')
        });
    })
})
