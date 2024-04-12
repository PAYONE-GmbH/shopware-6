describe('Bancontact', () => {
    const PAYMENT_METHOD_ID = '32ecec740c7142c9bf51d00ea894ffad';

    it('complete payment', () => init().then(() => cy.checkoutConfirmAndComplete(undefined, processCheckout)));

    it('cancel payment & complete after cancel', () => {
        init()
            .then(() => cy.checkoutConfirmAndCancel(
                undefined,
                () => cy.origin('r3.girogate.de', () => {
                    cy.get('[name="result"]').select('failed_user_abort');
                    cy.get('#submitbutton').click();
                })
            ))
            // .then(() => cy.checkoutConfirmAfterCancelAndComplete(undefined, processCheckout)) // after-order is not enabled. Payment method is not available
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {countryIso: 'BE'});
    }

    function processCheckout() {
        return cy.origin('r3.girogate.de', () => {
            cy.get('[name="result"]').select('succeeded');
            cy.get('#submitbutton').click();
        })
    }
});


