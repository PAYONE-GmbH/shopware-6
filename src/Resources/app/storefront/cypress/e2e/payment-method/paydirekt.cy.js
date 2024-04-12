describe('Paydirekt', () => {
    const PAYMENT_METHOD_ID = 'b5b52a27e6b14a37bbb4087ec821b0f4';

    before(() => {
        cy.origin('sandbox.paydirekt.de', () => {
            Cypress.Commands.add('waitForGiropayWelcomeScreen', () => {
                cy.get('co\\.welcome-screen', {timeout: 10000}).should('be.visible');
                cy.get('co\\.welcome-screen', {timeout: 10000}).should('not.exist');
                cy.get('body').then(($body) => {
                    $body.find('.rds-cookies-overlay__allow-essential-cookies-btn button')?.click();
                });
            });
        });
    })

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmAndComplete(undefined, paydirektCheckout);
        });
    });

    it('cancel payment & complete after cancel', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            return cy.checkoutConfirmAndCancel(undefined, () => {
                return cy.origin('sandbox.paydirekt.de', () => {
                    cy.waitForGiropayWelcomeScreen();
                    cy.get('[name="cancelPayment"]', {timeout: 6000}).click();
                    cy.get('[name="abortionButton"]').click();
                })
            }).then(() => {
                return cy.checkoutConfirmAfterCancelAndComplete(undefined, paydirektCheckout);
            });
        });
    });

    function paydirektCheckout() {
        return cy.origin('sandbox.paydirekt.de', () => {
            cy.waitForGiropayWelcomeScreen();

            cy.get('[name="selectedPaymentOptionType"]').check('paydirekt');
            cy.get('button[name="paymentButton"][type="submit"]').click();
            cy.get('#username', {timeout: 6000}).type('PAYONE-1');
            cy.get('#password').type('Payone!-1');
            cy.get('[name="loginBtn"]').click();
            cy.get('[name="confirmPaymentButton"]', {timeout: 6000}).click();
            cy.get('success-with-redirect').should('be.visible');
            cy.get('[name="redirectImmediatelyButton"]').click();
        })
    }
})
