describe('Ratepay Invoice', () => {
    const PAYMENT_METHOD_ID = '240dcc8bf5fc409c9dcf840698c082aa';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.ratepayInvoicingProfiles': [{ shopId: '88880103', 'currency': 'EUR'}]
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
                    cy.get('#ratepayBirthday').type('2050-01-01')
                    cy.get('#payonePhone').type('012345789')
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#ratepayBirthday').type('1990-01-01');
            cy.get('#payonePhone').type('012345789')
        });
    }
})
