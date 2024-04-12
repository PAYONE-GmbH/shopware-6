Cypress.Commands.add('checkoutRegister', (customerData = {}, inCheckout = true) => {
    customerData.countryIso ??= 'DE';
    customerData.guest ??= true;

    if (!inCheckout) {
        customerData.guest = false;
    }

    return cy.allowCountryForSalesChannel(customerData.countryIso).then((countryId) => {
        if (inCheckout) {
            cy.visit('/checkout/register');
        } else {
            cy.visit('/account/login');
        }
        cy.get('#personalSalutation').select(1);

        cy.get('#personalFirstName').type(customerData.firstName ?? 'Payone');
        cy.get('#personalLastName').type(customerData.lastName ?? 'Testbuyer');

        const mail = customerData.mail ?? 'demo+' + (new Date()).getTime() + '@payone.demo';
        cy.get('#personalMail').type(mail);

        cy.get('#billingAddressAddressStreet').type(customerData.street ?? 'Demostreet 12');
        cy.get('#billingAddressAddressZipcode').type(customerData.zipcode ?? '12345');
        cy.get('#billingAddressAddressCity').type(customerData.city ?? 'Democity');

        cy.get('#billingAddressAddressCountry').select(countryId);

        cy.get('#personalPassword').then((el) => {
            const isPasswordVisible = el?.is(':visible');
            if ((customerData.guest && isPasswordVisible) || !customerData.guest && !isPasswordVisible) {
                cy.get('#personalGuest').click()
            }
        });

        if (!customerData.guest) {
            cy.get('#personalPassword').type(mail);
        }

        cy.get('.register-submit > button').click()
    })
});
