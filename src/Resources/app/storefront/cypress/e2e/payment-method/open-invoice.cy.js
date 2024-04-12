describe('Secure Invoice', () => {
    const PAYMENT_METHOD_ID = '9024aa5a502b4544a745b6b64b486e21';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmAndComplete();
        });
    });
})
