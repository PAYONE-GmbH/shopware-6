describe('WeChat', () => {
    const PAYMENT_METHOD_ID = 'e9647d765b284cea9c4c0d68005665b7';

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
            //.then(() => cy.checkoutConfirmAfterCancelAndComplete(undefined, processCheckout)) // after-order is not enabled, so the payment method is not available after cancel
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID);
    }

    function processCheckout() {
        return cy.origin('r3.girogate.de', () => {
            cy.get('[name="result"]').select('succeeded');
            cy.get('#submitbutton').click();
        })
    }
});


