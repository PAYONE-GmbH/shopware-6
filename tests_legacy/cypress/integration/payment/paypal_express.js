describe('PayPal Express Test', function () {
    it('Buy with PayPal Express', function () {
        cy.buyDemoArticle();
        cy.get('a[title="PayPal Express"]').click({force: true});
        cy.url().should('include', 'paypal.com');
    })
})
