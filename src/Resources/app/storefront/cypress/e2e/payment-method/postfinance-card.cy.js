describe('Postfinance Card', () => {
    const PAYMENT_METHOD_ID = '8b4503f88a7746069a670e1689908832';

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
