import {processPayment} from "../../helper/paypal";

describe('PayPal', () => {
    const PAYMENT_METHOD_ID = '21e157163fdb4aa4862a2109abcd7522'

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmAndComplete(undefined, processPayment);
        });
    });

    it('cancel payment & complete after cancel', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmAndCancel(undefined, () => {
                return cy.origin('www.sandbox.paypal.com', () => {
                    cy.get('#cancelLink').click();
                })
            }).then(() => {
                return cy.checkoutConfirmAfterCancelAndComplete(undefined, processPayment);
            });
        });
    });
})
