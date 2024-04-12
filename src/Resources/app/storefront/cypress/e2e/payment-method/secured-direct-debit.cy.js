import {CountryCode, IBAN} from 'ibankit';

describe('Secured Direct Debit', () => {
    const PAYMENT_METHOD_ID = '72c4c88b918441848e20081de67a16c4';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.securedDirectDebitPortalId': Cypress.env('paymentMethodSecuredPortalId'),
        'PayonePayment.settings.securedDirectDebitPortalKey': Cypress.env('paymentMethodSecuredPortalKey'),
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment()
        });
    });

    it('fails with invalid iban & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#securedDirectDebitIban').type('invalid-iban');
                    cy.get('#securedDirectDebitBirthday').type('1990-01-01');
                    cy.get('#payonePhone').type('012345789');
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    it('fails with invalid birthday & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#securedDirectDebitIban').type(IBAN.random(CountryCode.DE).toString());
                    cy.get('#securedDirectDebitBirthday').type('2050-01-01');
                    cy.get('#payonePhone').type('012345789');
                }
            ).then(() => {
                return processPayment();
            });
        });
    });

    // we won't test invalid phone number, because PAYONE (module) does not validate the phone number.

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#securedDirectDebitIban').type(IBAN.random(CountryCode.DE).toString());
            cy.get('#securedDirectDebitBirthday').type('1990-01-01');
            cy.get('#payonePhone').type('012345789');
        });
    }

})
