const {v4: uuid} = require('uuid');

Cypress.Commands.add('getDefaultProductId', () => {
    return cy.task('getFromGlobalStore', 'productId').then(productId => {
        if (productId) {
            return productId
        }

        productId = uuid().replace(/-/g, '');

        const productNumber = 'payone-cypress-storefront-test';

        cy.searchViaAdminApi({
            endpoint: 'product',
            data: {
                field: 'productNumber',
                value: productNumber
            }
        }).then((product) => {
            if (product?.id) {
                return product.id;
            }

            return cy.searchViaAdminApi({
                endpoint: 'currency',
                data: {
                    field: 'isoCode',
                    value: 'EUR'
                }
            }).then(currency => {
                return cy.searchViaAdminApi({
                    endpoint: 'tax',
                    data: {field: 'name', value: 'Standard rate'}
                }).then(taxData => [currency.id, taxData.id]);
            }).then(([currencyId, taxId]) => {
                return cy.getSalesChannel()
                    .then((salesChannelData) => [currencyId, taxId, salesChannelData.id])
            }).then(([currencyId, taxId, salesChannelId]) => {
                return cy.createViaAdminApi({
                    endpoint: 'product',
                    data: {
                        id: productId,
                        name: productNumber,
                        productNumber: productNumber,
                        stock: 10,
                        taxId: taxId,
                        price: [{
                            currencyId: currencyId,
                            gross: 300,
                            net: 300 / 1.19,
                            linked: false
                        }],
                        visibilities: [{
                            salesChannelId: salesChannelId,
                            visibility: 30
                        }]
                    }
                });
            })
        }).then((productId) => {
            return cy.task('addToGlobalStore', {key: 'productId', value: productId});
        });
    });
});

Cypress.Commands.add('addProductToCart', () => {
    return cy.getDefaultProductId().then(productId => {
        cy.visit('/detail/' + productId);

        cy.intercept({method: 'POST', url: '*checkout/line-item/add*'}).as('addProduct');
        cy.intercept({method: 'GET', url: '*widgets/checkout/info*'}).as('ajaxCart');

        cy.get('.btn.btn-primary.btn-buy').click();
        cy.wait('@addProduct');
        cy.wait('@ajaxCart');
        cy.visit('/checkout/cart/');
    });
});
