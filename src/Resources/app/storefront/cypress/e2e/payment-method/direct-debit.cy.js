import {CountryCode, IBAN} from 'ibankit';

describe('Direct Debit', () => {
    const PAYMENT_METHOD_ID = '1b017bef157b4222b734659361d996fd';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment()
        });
    });

    it('fails with invalid iban', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            cy.get('#tos').check();
            cy.get('#confirmFormSubmit').click();
            cy.get('#errorOutput');
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#iban').type(IBAN.random(CountryCode.DE).toString());
            cy.get('#accountOwner').type('Max Mustermann');
        }, () => {
            cy.wait(4000); // wait for register new event listeners TODO verify why this takes so long.
            cy.get('#accept-mandate').check();
            cy.get('#mandateSubmit').click();
            return Promise.resolve();
        });
    }

})
