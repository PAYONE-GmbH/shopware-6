Cypress.Commands.add('getSalesChannel', () => {
    const salesChannel = window.localStorage.getItem('salesChannel');
    if (salesChannel) {
        return JSON.parse(salesChannel);
    }

    return cy.searchViaAdminApi({
        data: {
            field: 'name',
            value: Cypress.env('salesChannelName')
        },
        endpoint: 'sales-channel'
    }).then((salesChannel) => {
        window.localStorage.setItem('salesChannel', JSON.stringify(salesChannel));
        return salesChannel;
    });
});

Cypress.Commands.add('allowCountryForSalesChannel', (isoCode) => {
    return cy.task('getAllowedCountryId', isoCode).then((countryId) => {
        if (countryId) {
            return countryId;
        }

        return cy.getSalesChannel().then((salesChannel) => {
            cy.searchViaAdminApi({
                endpoint: 'country',
                data: {
                    field: 'iso',
                    value: isoCode
                }
            }).then((country) => {
                return cy.updateViaAdminApi('sales-channel', salesChannel.id, {
                    data: {
                        countries: [{
                            id: country.id
                        }]
                    }
                }).then(() => {
                    return cy.task('addAllowedCountry', {isoCode: isoCode, id: country.id})
                }).then(() => country.id)
            })
        })
    })
});

Cypress.Commands.add('allowCurrencyForSalesChannel', (isoCode) => {
    return cy.task('getAllowedCurrencyId', isoCode).then((currencyId) => {
        if (currencyId) {
            return currencyId;
        }

        const allowedCurrencies = JSON.parse(window.localStorage.getItem('allowedCurrencies') ?? '{}');
        if (allowedCurrencies[isoCode] ?? false) {
            return allowedCurrencies[isoCode];
        }

        return cy.getSalesChannel().then((salesChannel) => {
            cy.searchViaAdminApi({
                endpoint: 'currency',
                data: {
                    field: 'isoCode',
                    value: isoCode
                }
            }).then((currency) => {
                return cy.updateViaAdminApi('sales-channel', salesChannel.id, {
                    data: {
                        currencies: [{
                            id: currency.id
                        }]
                    }

                }).then(() => {
                    return cy.task('addAllowedCurrency', {isoCode: isoCode, id: currency.id})
                })
            })
        })
    }).then(() => {
        cy.visit('/'); // navigate to home, to make sure dropdown is visible
        cy.get('body').then($body => {
            if ($body.find('.top-bar .currencies-menu').length) {
                cy.get('.top-bar .currencies-menu > .dropdown-toggle').click();
                cy.get('.top-bar .currencies-menu [title="' + isoCode + '"]').click();
            }
        });
    })
});

Cypress.Commands.add('allowPaymentMethod', (paymentMethodUuid) => {
    return cy.task('isPaymentMethodAllowed', paymentMethodUuid).then(isAllowed => {
        if (isAllowed) {
            return;
        }

        return cy.getSalesChannel().then(salesChannelData => {
            // make sure that payment method is enabled in sales-channel
            return cy.updateViaAdminApi('sales-channel', salesChannelData.id, {
                data: {
                    id: salesChannelData.id,
                    paymentMethods: [{
                        id: paymentMethodUuid
                    }]
                }
            });
        }).then(() => {
            // make sure that payment method is enabled
            return cy.updateViaAdminApi('payment-method', paymentMethodUuid, {
                data: {
                    id: paymentMethodUuid,
                    active: true
                }
            });
        }).then(() => cy.task('addAllowedPaymentMethod', paymentMethodUuid))
    })
});


