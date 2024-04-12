describe('Postfinance Wallet', () => {
    const PAYMENT_METHOD_ID = 'cd65c7f9c0cc4e0886799f7cc7407494';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'CH',
            zipcode: 1234
        }, {currencyIso: 'CHF'}).then(() => {
            return cy.checkoutConfirmAndComplete(undefined, postFinanceCheckout);
        });
    });

    it('cancel payment & complete after cancel', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'CH',
            zipcode: 1234
        }, {currencyIso: 'CHF'}).then(() => {
            return cy.checkoutConfirmAndCancel(undefined, () =>
                cy.origin('gpc-sys.pay1.de', () => {
                    cy.get('[name="status"]').select('Cancel');
                    cy.get('[type=submit]').click();
                })
            ).then(() => {
                return cy.checkoutConfirmAfterCancelAndComplete(undefined, postFinanceCheckout);
            });
        });
    });

    function postFinanceCheckout() {
        return cy.origin('gpc-sys.pay1.de', () => {
            cy.get('[name="status"]').select('Success');
            cy.get('[type=submit]').click();
        })
    }

})
