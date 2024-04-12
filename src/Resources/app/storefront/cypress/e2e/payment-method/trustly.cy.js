import {CountryCode, IBAN} from 'ibankit';

describe('Trustly', () => {
    const PAYMENT_METHOD_ID = '741f1deec67d4012bd3ccce265b2e15e';

    it('complete payment', () => init().then(() => cy.checkoutConfirmAndComplete(initValidData, processCheckout)));

    it('cancel payment & complete after cancel', () => {
        init()
            .then(() => cy.checkoutConfirmAndCancel(
                () => {
                    cy.get('#iban').type(IBAN.random(CountryCode.DE).toString());
                },
                () => cy.origin('r3.girogate.de', () => {
                    cy.get('[name="result"]').select('failed_user_abort');
                    cy.get('#submitbutton').click();
                })
            ))
            .then(() => cy.checkoutConfirmAfterCancelAndComplete(initValidData, processCheckout))
    });

    it('fails with invalid iban', () => {
        init().then(() => {
            return cy.checkoutConfirmFailValidation(
                () => cy.get('#iban').type('invalid-iban')
            ).then(() => cy.checkoutConfirmAndComplete(initValidData, processCheckout));
        });
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID);
    }

    function initValidData() {
        cy.get('#iban').clear().type(IBAN.random(CountryCode.DE).toString());
    }

    function processCheckout() {
        return cy.origin('r3.girogate.de', () => {
            cy.get('[name="result"]').select('succeeded');
            cy.get('#submitbutton').click();
        })
    }
});


