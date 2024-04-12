describe('Alipay', () => {
    const PAYMENT_METHOD_ID = 'fef3c750f8e94a6abb7d0a8061ac9faf';

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
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID);
    }

    function processCheckout() {
        return cy.origin('r3.girogate.de', () => {
            cy.get('[name="result"]').select('succeeded');
            cy.get('#submitbutton').click();
        })
    }
});


