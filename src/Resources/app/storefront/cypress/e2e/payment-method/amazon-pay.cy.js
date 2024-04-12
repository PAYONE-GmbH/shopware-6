import {registerCommands, processCheckout} from '../../helper/amazon';

describe('Amazon Pay', () => {
    const PAYMENT_METHOD_ID = 'ae2b29f0b99d4ba9852063d84d198180';

    beforeEach(() => cy.setShopwareConfig({
        'PayonePayment.settings.amazonPayAmazonMerchantId': Cypress.env('paymentMethodAmazonPayMerchantId'),
        'PayonePayment.settings.amazonPayStoreName': 'Test GmbH'
    }));

    before(registerCommands)

    it('complete payment', () => init().then(() => cy.checkoutConfirmAndComplete(fillValidData, processCheckout)));

    it('cancel payment & complete after cancel', (done) => {
        init()
            .then(() => cy.checkoutConfirmAndCancel(
                fillValidData,
                () => {
                    cy.origin('www.amazon.de', () => {
                        cy.amazonLogin();
                    });
                    cy.origin('payments.amazon.de', () => {
                        cy.get('#return_back_to_merchant_link').click();
                    })

                    return Promise.resolve();
                }
            ))
            .then(() => cy.checkoutConfirmAfterCancelAndComplete(fillValidData, done));
        // it seems that there are problems, if the simulated browser reopen amazon again. Amazon throws an error, that jquery is already registered.
        // so we will not complete the order, just check if the user is redirected to the amazon pay again, and then we will stop the test
    });

    function init() {
        return cy.preparePaymentCheckout(PAYMENT_METHOD_ID);
    }

    function fillValidData() {
        cy.get('#payonePhone').type('0123456789');
    }
});


