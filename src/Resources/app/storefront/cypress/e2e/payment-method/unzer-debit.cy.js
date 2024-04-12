import {BIC, CountryCode, IBAN} from "ibankit";

describe('Unzer Debit', () => {
    const PAYMENT_METHOD_ID = '700954775fad4a8f92463b3d629c8ad5';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.payolutionDebitCompanyName': 'Test GmbH'
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return processPayment()
        });
    });

    // TODO: missing validation in frontend. Nothing happens if the validation fails.

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#payolutionAccountHolder').type('Max Mustermann');
            cy.get('#payolutionIban').type(IBAN.random(CountryCode.DE).toString());
            cy.get('#payolutionBic').type('DEUTDEFF500');

            cy.get('#payolutionBirthday').type('1990-01-01');
            cy.get('#payolutionConsent').check();
            cy.get('#payolutionMandate').check();
        });
    }
})
