import {CountryCode, IBAN} from "ibankit";

describe('Unzer Installment', () => {
    const PAYMENT_METHOD_ID = '569b46970ad2458ca8f17f1ebb754137';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.payolutionInstallmentCompanyName': 'Test GmbH'
    }));

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(processPayment);
    });

    it('test empty fields & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            fillCheckoutWithCorrectData();
            cy.get('#tos').check();

            const fieldIds = ['payolutionAccountOwner', 'payolutionIban', 'payolutionBic'];
            fieldIds.forEach((fieldId) => {
                cy.get('#' + fieldId).invoke('val').then(prevVal => {
                    cy.get('#' + fieldId).clear();
                    cy.get('#confirmFormSubmit').click();
                    cy.wait(2000);
                    cy.url().should('not.contain', '/checkout/finish');

                    // set value back to initial value to make sure we can check out at the end.
                    cy.get('#' + fieldId).invoke('val', prevVal);
                });
            });
        });
        cy.checkoutConfirmAndComplete();
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            fillCheckoutWithCorrectData();

            cy.get('#payolutionInstallmentDuration').children().each(($option) => {
                cy.get('#payolutionInstallmentDuration').select($option.val()).invoke('val').then(selectedValue => {
                    cy.get('.installmentDetail[data-duration=' + selectedValue + ']').should('have.length', 2).should('be.visible');
                    cy.get('.installmentDetail:not([data-duration=' + selectedValue + '])').should('not.be.visible');
                });
            })
        });
    }

    function fillCheckoutWithCorrectData() {
        cy.intercept('payone/installment/calculation').as('calcRequest');
        cy.get('#payolutionBirthday').type('1990-01-01');
        cy.get('#payolutionConsent').check();
        cy.get('#checkInstallmentButton').click();
        cy.wait('@calcRequest');

        cy.get('#payolutionAccountOwner').type('Max Mustermann');
        cy.get('#payolutionIban').type(IBAN.random(CountryCode.DE).toString());
        cy.get('#payolutionBic').type('DEUTDEFF500');
    }
})
