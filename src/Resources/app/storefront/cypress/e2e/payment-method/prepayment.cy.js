describe('Prepayment: Default success checkout', () => {
    it('passes', () => {
        cy.preparePaymentCheckout('267699739afd4cdd9663cac0bd269da6').then(() => {
            return cy.checkoutConfirmAndComplete();
        });
    })
})
