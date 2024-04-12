import {registerCommands} from '../../helper/amazon';

describe('Amazon Pay Express', () => {
    const PAYMENT_METHOD_ID = 'd310a86cdaf14dd6b69bcf2b98f23268';

    before(registerCommands);

    beforeEach(() => {
        cy.viewport(1400, 1400); // amazon pay button is not clickable if it is not visible
        cy.setShopwareConfig({
            'PayonePayment.settings.amazonPayExpressAmazonMerchantId': Cypress.env('paymentMethodAmazonPayMerchantId'),
        });

        cy.checkoutRegister({}, false);// only required to get a valid context

        cy.allowPaymentMethod(PAYMENT_METHOD_ID);
        cy.addProductToCart();
        cy.allowCurrencyForSalesChannel('EUR');
    });

    it('complete payment', () => {
        cy.visit('/checkout/cart');
        cy.get('#AmazonPayExpressButton-cart').click();
        processPayment();
    });

    it('complete payment in cart-offcanvas', () => {
        cy.visit('/');
        cy.get('.header-cart-btn').click();
        cy.wait(500) // wait a few seconds to make sure that button is loaded
        cy.get('#AmazonPayExpressButton-offcanvas').click();
        processPayment();
    });

    function processPayment() {
        cy.origin('www.amazon.de', () => {
            cy.amazonLogin();
        });

        cy.origin('payments.amazon.de', () => {
            cy.waitForAmazonLogin();
            cy.wait(1000);
            cy.get('#change-address-button').click();
            cy.get('#change-buyer-details [data-country="DE"]').first().should('be.visible').click();
            cy.get('[data-action="update-address"] [type="submit"]').click();
            cy.get('[data-action="continue-checkout"] [type="submit"]').click();
        });

        cy.window().then(() => {
            cy.wait(2000);
            cy.checkoutConfirmAndComplete(() => {
                // validate if only amazon pay express is visible
                cy.get('#changePaymentForm .payment-methods .payment-method').should('have.length', 1);
                cy.get('#changePaymentForm [name="paymentMethodId"]').invoke('val').should('equal', PAYMENT_METHOD_ID);
                cy.get('a[href*="payone/delete-checkout-data"]').should('be.visible');
            });
        });
    }
});


