Cypress.Commands.add('register', () => {
    cy.url().should('include', 'checkout/register')
    cy.get('#personalSalutation').select('Mr.').should('contain.text', 'Mr.');

    cy.get('#personalFirstName').type('Payone');
    cy.get('#personalLastName').type('Testkäufer');

    cy.get('#personalMail').type('demo@payone.demo');
    cy.get('#personalGuest').check({force: true});

    cy.get('#billingAddressAddressStreet').type('Demostreet 12');
    cy.get('#billingAddressAddressZipcode').type('12345');
    cy.get('#billingAddressAddressCity').type('Democity');

    cy.get('#billingAddressAddressCountry').select('Germany').should('contain.text', 'Germany');
    cy.get('.register-submit > button').click();
})
