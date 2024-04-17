describe('Unzer Invoice', () => {
    const PAYMENT_METHOD_ID = '0407fd0a5c4b4d2bafc88379efe8cf8d';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.payolutionInvoicingCompanyName': 'Test GmbH'
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment()
        });
    });

    // TODO: missing validation in frontend. Nothing happens if the validation fails.

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#payoneBirthday').type('1990-01-01');
            cy.get('#payolutionConsent').check();
        });
    }
})
