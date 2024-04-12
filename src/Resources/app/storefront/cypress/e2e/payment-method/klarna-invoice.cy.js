import {executeKlarnaCheckout, executeKlarnaCheckoutCancel, customerData} from "../../helper/klarna";

describe('Klarna Invoice', () => {
    const PAYMENT_METHOD_ID = 'c4cd059611cc4d049187d8d955ec1f91';

    it('complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckout(done));
    });

    it('cancel payment & complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckoutCancel(done));
    });
})
