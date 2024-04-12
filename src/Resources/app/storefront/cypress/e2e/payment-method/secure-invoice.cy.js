describe('Secure Invoice', () => {
    const PAYMENT_METHOD_ID = '4e8a9d3d3c6e428887573856b38c9003';

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
        });
    }

})
