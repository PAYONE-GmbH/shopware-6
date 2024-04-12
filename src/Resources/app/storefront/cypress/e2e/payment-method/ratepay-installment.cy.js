import {CountryCode, IBAN} from "ibankit";

describe('Ratepay Installment', () => {
    const PAYMENT_METHOD_ID = '0af0f201fd164ca9ae72313c70201d18';

    beforeEach(() => {
        cy.setShopwareConfig({
            'PayonePayment.settings.ratepayInstallmentProfiles': [{shopId: '88880103', 'currency': 'EUR'}]
        })
    });

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(processPayment);
    });

    it('fails with invalid birthday & complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmFailValidation(
                () => {
                    cy.get('#ratepayBirthday').type('2050-01-01');
                    cy.get('#payonePhone').type('012345789');
                    cy.get('#ratepayIban').type(IBAN.random(CountryCode.DE).toString());
                }
            ).then(processPayment);
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
            ).then(processPayment);
        });
    });

    function processPayment() {
        return cy.checkoutConfirmAndComplete(() => {
            cy.get('#ratepayBirthday').clear().type('1990-01-01');
            cy.get('#payonePhone').clear().type('012345789');
            cy.get('#ratepayIban').clear().type(IBAN.random(CountryCode.DE).toString());

            testPlanChanges(
                () => cy.get('#ratepayRuntime').select(2),
                () => cy.get('#ratepayRuntime').select(5),
                1
            );

            const changeRate = () => cy.get('#payone-ratepay-calculate-installment-btn').click();
            testPlanChanges(
                () => cy.get('#ratepayRate').clear().type("1").then(changeRate), // use a very small value to take the lowest rate
                () => cy.get('#ratepayRate').clear().type("5000").then(changeRate), // use a very high value to take the highest rate
                2
            );
        });
    }

    function testPlanChanges(initialValueChange, newValueChange, run) {
        // set default value to compare it later
        cy.intercept('payone/ratepay/installment/calculation').as('calcRequest1' + run)
        initialValueChange();
        cy.wait('@calcRequest1' + run);

        getPlanInfo().then((initialCalculatedPlan) => {
            // set new runtime value
            cy.intercept('payone/ratepay/installment/calculation').as('calcRequest2' + run)
            newValueChange();
            cy.wait('@calcRequest2' + run);
            getPlanInfo().should('not.equal', initialCalculatedPlan);

            // set runtime back to initial value
            cy.intercept('payone/ratepay/installment/calculation').as('calcRequest3' + run)
            initialValueChange();
            cy.wait('@calcRequest3' + run);
            getPlanInfo().should('equal', initialCalculatedPlan);
        });

        cy.get('#ratepayIbanCollapseTrigger').then(($el) => {
            if ($el.find('.payone-ratepay-open-text:visible')) {
                cy.get('#ratepayIban').should('be.visible').should('have.attr', 'required');
                cy.get('#ratepayIbanCollapseTrigger').click();
                cy.get('#ratepayIban').should('not.be.visible').should('not.have.attr', 'required');
                cy.get('#ratepayIbanCollapseTrigger').click();
                cy.get('#ratepayIban').should('be.visible').should('have.attr', 'required');
            } else {
                cy.get('#ratepayIban').should('not.be.visible').should('not.have.attr', 'required');
                cy.get('#ratepayIbanCollapseTrigger').click();
                cy.get('#ratepayIban').should('be.visible').should('have.attr', 'required');
                cy.get('#ratepayIbanCollapseTrigger').click();
                cy.get('#ratepayIban').should('not.be.visible').should('not.have.attr', 'required');
            }
        })
    }

    function getPlanInfo() {
        return cy.get('#ratepayInstallmentTable tr:first-child td:first-child p:first-child')
            .invoke('text')
            .then((t) => t.replaceAll(/\s*\n*(([^ ]+ \w+)+)\s*\n*$/g, '$1'));
    }
})
