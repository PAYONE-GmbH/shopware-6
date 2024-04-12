import {processPayment} from '../../helper/paypal';

describe('PayPal Express', () => {
    const PAYMENT_METHOD_ID = '5ddf648859a84396a98c97a1a92c107f';

    beforeEach(() => {
        cy.viewport(1400, 1400); // amazon pay button is not clickable if it is not visible
        // cy.checkoutRegister({}, false);// only required to get a valid context

        cy.allowPaymentMethod(PAYMENT_METHOD_ID);
        cy.addProductToCart();
        cy.allowCurrencyForSalesChannel('EUR');
    });

    it('complete payment', () => {
        cy.visit('/checkout/cart');
        _processPayment();


    });

    it('complete payment in cart-offcanvas', () => {
        cy.visit('/');
        cy.get('.header-cart-btn').click();
        cy.wait(500) // wait a few seconds to make sure that button is loaded
        _processPayment();
    });

    function _processPayment() {
        cy.get('a[href*="/payone/express-checkout/redirect/"]').click();
        processPayment(null);

        cy.window().then(() => {
            cy.wait(2000);
            cy.checkoutConfirmAndComplete(() => {
                // validate if only paypal pay express is visible
                cy.get('#changePaymentForm .payment-methods .payment-method').should('have.length', 1);
                cy.get('#changePaymentForm [name="paymentMethodId"]').invoke('val').should('equal', PAYMENT_METHOD_ID);
                cy.get('a[href*="payone/delete-checkout-data"]').should('be.visible');
            });
        });
    }

});


