import {CountryCode, IBAN} from "ibankit";

describe('Ratepay Direct Debit', () => {
    const PAYMENT_METHOD_ID = '48f2034b3c62480a8554781cf9cac574';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.ratepayDebitProfiles': [{ shopId: '88880103', 'currency': 'EUR'}]
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment();
        });
    });

    it('fails with invalid birthday & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#ratepayBirthday').type('2050-01-01');
                    cy.get('#payonePhone').type('012345789');
                    cy.get('#ratepayIban').type(IBAN.random(CountryCode.DE).toString());
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    it('fails with invalid iban & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#ratepayBirthday').type('1990-01-01');
                    cy.get('#payonePhone').type('012345789');
                    cy.get('#ratepayIban').type('invalid-iban');
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#ratepayBirthday').clear().type('1990-01-01');
            cy.get('#payonePhone').clear().type('012345789');
            cy.get('#ratepayIban').clear().type(IBAN.random(CountryCode.DE).toString());
        });
    }
})
