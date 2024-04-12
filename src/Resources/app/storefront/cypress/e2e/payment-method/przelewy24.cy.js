describe('Przelewy24', () => {
    const PAYMENT_METHOD_ID = '6068e01cef8b4c9698956c6cca648d50';

    it('complete payment', () => init().then(() => processCheckout));

    it('cancel payment & complete after cancel', () => {
        init().then(() =>
            cy.checkoutConfirmAndCancel(
                undefined,
                () => cy.origin('r3.girogate.de', () => {
                    cy.get('[name="result"]').select('failed_user_abort');
                    cy.get('#submitbutton').click();
                })
            )).then(() => processCheckout);
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'PL',
        }, {currencyIso: 'PLN'});
    }

    function processCheckout() {
        return cy.checkoutConfirmAndComplete(
            undefined,
            () => cy.origin('r3.girogate.de', () => {
                cy.get('[name="result"]').select('succeeded');
                cy.get('#submitbutton').click();
            })
        );
    }
});


