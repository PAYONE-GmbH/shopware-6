import {customerData, executeKlarnaCheckout, executeKlarnaCheckoutCancel} from "../../helper/klarna";

describe('Klarna Installment', () => {
    const PAYMENT_METHOD_ID = 'a18ffddd4baf4948b8c9f9d3d8abd2d4';

    it('complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckout(done));
    });

    it('cancel payment & complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckoutCancel(done));
    });

})
