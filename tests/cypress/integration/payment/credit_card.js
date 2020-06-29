describe('CreditCard Test', function () {
    it('Buy with CreditCard', function () {
        const findInIframe = (selector) => ($iframe) => $iframe.contents().find(selector)

        cy.buyDemoArticle()
        cy.register()
        cy.selectPaymentMethod('Credit Card')

        cy.get('#cardpan > iframe').pipe(findInIframe('input')).type('4111111111111111', {force: true});
        cy.get('#cardcvc2 > iframe').pipe(findInIframe('input')).type('123', {force: true});
        cy.get('#cardexpiremonth > iframe').pipe(findInIframe('select')).select('10', {force: true});
        cy.get('#cardexpireyear > iframe').pipe(findInIframe('select')).select('2022', {force: true});

        cy.finishCheckout()
    })
})
