describe('EPS', () => {
    const PAYMENT_METHOD_ID = '6004c8b082234ba5b2834da9874c5ec7';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'AT',
            zipcode: 1234
        }).then(() => epsCheckout);
    });

    it('cancel payment & complete after cancel', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            countryIso: 'AT',
            zipcode: 1234
        }).then(() => {
            return cy.checkoutConfirmAndCancel(
                () => cy.get('#epsBankGroup').select('ARZ_HTB'),
                () => cy.origin('www.banking.co.at', () => {
                    cy.get('[name="cancelSoTransaction"]').click();
                    cy.get('[name="back2Shop"]').click();
                })
            ).then(() => epsCheckout);
        });
    });

    function epsCheckout() {
        return cy.checkoutConfirmAndComplete(
            () => cy.get('#epsBankGroup').select('ARZ_HTB'),
            () =>
                cy.origin('www.banking.co.at', () => {
                cy.get('#verfNr').clear().type(123456);
                cy.get('#verfPIN').clear().type(12345);
                cy.get('#sbtnLogin').click();
                cy.get('#sbtnSign').click();
                cy.get('[name="zVerf"]').check('MTAN');
                cy.get('#sbtnSignCollect').click();
            })
        );
    }
})
