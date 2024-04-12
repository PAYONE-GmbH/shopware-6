describe('Secured Invoice', () => {
    const PAYMENT_METHOD_ID = '4ca01ac1471c4da5b76faeaa42524cc3';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.securedInvoicePortalId': Cypress.env('paymentMethodSecuredPortalId'),
        'PayonePayment.settings.securedInvoicePortalKey': Cypress.env('paymentMethodSecuredPortalKey'),
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment()
        });
    });

    it('fails with invalid birthday & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#payoneInvoiceBirthday').type('2050-01-01');
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#payoneInvoiceBirthday').type('1990-01-01')
            cy.get('#payonePhone').type('012345789')
        });
    }

})
