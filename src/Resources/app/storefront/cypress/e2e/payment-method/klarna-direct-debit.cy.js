import {executeKlarnaCheckout, executeKlarnaCheckoutCancel, customerData} from "../../helper/klarna";

describe('Klarna Direct Debit', () => {
    const PAYMENT_METHOD_ID = '31af2cbeda5242bfbfe4531e203f8a42';

    it('complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckout(done));
    });

    it('cancel payment & complete payment', (done) => {
        cy.preparePaymentCheckout(PAYMENT_METHOD_ID, customerData).then(() => executeKlarnaCheckoutCancel(done));
    });
})
