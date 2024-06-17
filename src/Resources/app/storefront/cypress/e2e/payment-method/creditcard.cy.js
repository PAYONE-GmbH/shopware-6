describe('Credit Card', () => {
    const PAYMENT_METHOD_ID = '37f90a48d9194762977c9e6db36334e0';

    it('complete payment', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => cy.checkoutConfirmAndComplete(
            () => {
                fillIframe();
                cy.get('#saveCreditCard').should('not.exist'); // user is a guest user, so the user should not able to save the credit-card
            }));
    });

    it('fails with invalid credit card number', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID).then(() => {
            cy.get('#tos').check();
            fillIframe('invalid-cc-number')
            cy.get('#confirmFormSubmit').click();
            cy.get('#errorOutput');
        });
    });

    it('save credit card and reuse it', () => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, {
            guest: false
        }).then(() => cy.checkoutConfirmAndComplete(
            () => {
                fillIframe();
                cy.get('#saveCreditCard').check();
            }));

        cy.visit('account/card/overview');
        cy.get('.account-payone-card table tbody tr').should('have.length', 1)
        cy.get('.account-payone-card table tbody tr td:first-child').should('contain.text', '411111');
        cy.get('.account-payone-card table tbody tr td:last-child a').then($el => {
            const matches = $el.prop('href').match(/\?pseudoCardPan=(\d+)$/);
            cy.wrap(matches).should('have.length', 2);
            cy.wrap(matches[1]).as('creditCardPan');
        });

        cy.addProductToCart().then(() => cy.selectPaymentMethod(PAYMENT_METHOD_ID));

        cy.checkoutConfirmAndComplete(
            () => {
                cy.get('@creditCardPan').then((storedPan) => {
                    cy.get('#savedpseudocardpan').select(storedPan);
                    cy.get('.credit-card-input').should('not.be.visible');
                })
            });
    });

    function fillIframe(iban = '4111111111111111') {
        cy.get('#creditCardHolder').type('credit card holder');
        setIframeValue('cardpan', 'input', iban);
        setIframeValue('cardcvc2', 'input', '123');
        setIframeValue('cardexpiremonth', 'select', 5);
        setIframeValue('cardexpireyear', 'select', 5);
    }

    function setIframeValue(id, type, value) {
        const selector = '#' + id + ' > iframe';

        return cy.frameLoaded(selector, {url: 'https://secure.pay1.de/'})
            .then(() => cy.enter(selector))
            .then(getBody => {
                if (type === 'input') {
                    return getBody().find('#' + id).type(value, {force: true})
                } else if (type === 'select') {
                    return getBody().find('#' + id).select(value, {force: true})
                } else {
                    throw new Error('unknown input type ' + type)
                }
            })
    }
})
