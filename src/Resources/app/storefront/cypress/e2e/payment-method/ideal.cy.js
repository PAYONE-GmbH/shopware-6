describe('iDEAL', () => {
    const PAYMENT_METHOD_ID = '3f567ad46f1947e3960b66ed3af537aa';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'NL',
        }).then(() => {
            return cy.checkoutConfirmAndComplete(() => {
                cy.get('#idealBankGroup').select(3);
            });
        });
    });

})
