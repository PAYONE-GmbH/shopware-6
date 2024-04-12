import {CountryCode, IBAN} from 'ibankit';

describe('Secured Installment', () => {
    const PAYMENT_METHOD_ID = '9c4d04f6ad4b4a2787e3812c56b6153b';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.securedInstallmentPortalId': Cypress.env('paymentMethodSecuredPortalId'),
        'PayonePayment.settings.securedInstallmentPortalKey': Cypress.env('paymentMethodSecuredPortalKey'),
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(processPayment);
    });

    it('fails with invalid birthday & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#securedInstallmentIban').clear().type(IBAN.random(CountryCode.DE).toString());
                    cy.get('#securedInstallmentBirthday').clear().type('2025-01-01');
                    cy.get('#payonePhone').clear().type('012345789');
                    cy.get('[name=securedInstallmentOptionId]').first().click();
                }
            ).then(processPayment);
        });
    });

    it('fails with invalid iban & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#securedInstallmentIban').clear().type('test-test');
                    cy.get('#securedInstallmentBirthday').clear().type('1990-01-01');
                    cy.get('#payonePhone').clear().type('012345789');
                    cy.get('[name=securedInstallmentOptionId]').first().click();
                }
            ).then(processPayment);
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#securedInstallmentIban').clear().type(IBAN.random(CountryCode.DE).toString());
            cy.get('#securedInstallmentBirthday').clear().type('1990-01-01');
            cy.get('#payonePhone').clear().type('012345789');
            cy.get('[name=securedInstallmentOptionId]').first().click();
        });
    }

})
