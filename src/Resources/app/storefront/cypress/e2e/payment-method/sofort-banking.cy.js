// info: we use `force` for actions on sofort.com to ignore the cookie-modal, which pop up on a very late stage.

describe('Sofort Banking', () => {
    const PAYMENT_METHOD_ID = '9022c4733d14411e84a78707088487aa';

    beforeEach(() => {
        // make sure that klarna does not store any cookies in the session
        cy.clearAllCookies();
        cy.clearAllSessionStorage();
        cy.clearAllLocalStorage();
    })

    it('complete payment', () => init().then(() => cy.checkoutConfirmAndComplete(undefined, processCheckout)));

    it('cancel payment & complete after cancel', () => {
        init()
            .then(() => cy.checkoutConfirmAndCancel(
                undefined,
                () => klarnaLogin().then(() =>
                    cy.origin('www.sofort.com', () => {
                        cy.get('.cancel-transaction').click({force: true});

                        // sometimes the user is asked for the reason why he cancelled the payment.
                        cy.wait(400);
                        cy.get('.wait-box').should($el => $el.length === 0 || !$el.is('visible'));
                        cy.window().then(window => {
                            if (window.origin.includes('www.sofort.com')) {
                                window.document.querySelector('#CancelTransaction').click();
                            }
                        })
                    })
                )
            ))
            .then(() => cy.checkoutConfirmAfterCancelAndComplete(undefined, processCheckout)) // after-order is not enabled, so the payment method is not available after cancel
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID);
    }

    function klarnaLogin() {
        return cy.wait(1500).then(() =>
            cy.window().then(window => {
                if (window.origin.includes('login.nonprod.klarna.net')) {
                    return cy.origin('login.nonprod.klarna.net', () => {
                        cy.get('#phone').type('+4901761428434');
                        cy.get('#onContinue').click();
                        cy.get('#otp_field').type('123456')
                    });
                }
            })
        );
    }

    function processCheckout() {
        return klarnaLogin().then(() =>
            cy.origin('www.sofort.com', () => {
                cy.get('label[for="account-88888888"]').click({force: true}); // demo-bank

                cy.get('#BackendFormLOGINNAMEUSERID').type('12345678', {force: true})
                cy.get('#BackendFormUSERPIN').type('12345678', {force: true});
                cy.get('.button-right.primary').click({force: true});

                cy.get('body').then($body => {
                    if ($body.find('.account-selector').length) {
                        cy.get('.button-right.primary').click({force: true});
                    }
                });

                cy.get('#BackendFormTan').type('12345', {force: true})
                cy.get('.button-right.primary').click({force: true});
            })
        );
    }
});


