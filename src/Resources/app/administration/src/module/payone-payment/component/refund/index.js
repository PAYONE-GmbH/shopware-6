import template from './refund.html.twig';
import './style.scss';

const { Component, Mixin } = Shopware;

Component.register('payone-refund-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['PayonePaymentService'],

    props: {
        order: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: false,
            hasError: false,
            showRefundModal: false,
            isRefundSuccessful: false,
            selection: [],
            refundAmount: 0.0
        };
    },

    computed: {
        transaction() {
            return this.order.transactions[0];
        },

        remainingAmount() {
            if (undefined === this.transaction.customFields ||
                undefined === this.transaction.customFields.payone_captured_amount) {
                return 0;
            }

            return this.transaction.customFields.payone_captured_amount - this.refundedAmount;
        },

        refundedAmount() {
            if (undefined === this.transaction.customFields ||
                undefined === this.transaction.customFields.payone_refunded_amount) {
                return 0;
            }

            return this.transaction.customFields.payone_refunded_amount;
        },

        maxRefundAmount() {
            return this.remainingAmount / (10 ** this.order.currency.decimalPrecision);
        },

        buttonEnabled() {
            if (!this.transaction.customFields) {
                return false;
            }

            return (this.remainingAmount > 0 && this.refundedAmount > 0) || this.transaction.customFields.payone_allow_refund;
        }
    },

    methods: {
        calculateRefundAmount() {
            let amount = 0;

            this.selection.forEach((selection) => {
                if (selection.selected) {
                    amount += selection.unit_price * selection.quantity;
                }
            });

            if (Math.round(amount * (10 ** this.order.currency.decimalPrecision) > this.remainingAmount)) {
                amount = this.remainingAmount / (10 ** this.order.currency.decimalPrecision)
            }

            this.refundAmount = amount;
        },

        openRefundModal() {
            this.showRefundModal = true;
            this.isRefundSuccessful = false;
            this.selection = [];
        },

        closeRefundModal() {
            this.showRefundModal = false;
        },

        onRefundFinished() {
            this.isRefundSuccessful = false;
        },

        refundOrder() {
            const request = {
                orderTransactionId: this.transaction.id,
                payone_order_id: this.transaction.customFields.payone_transaction_id,
                salesChannel: this.order.salesChannel,
                amount: this.refundAmount,
                orderLines: [],
                complete: this.refundAmount === this.maxRefundAmount
            };
            this.isLoading = true;

            this.selection.forEach((selection) => {
                this.order.lineItems.forEach((order_item) => {
                    if (order_item.id === selection.id && selection.selected && 0 < selection.quantity) {
                        const copy = { ...order_item },
                            taxRate = copy.tax_rate / (10 ** this.order.currency.decimalPrecision);

                        copy.quantity         = selection.quantity;
                        copy.total_amount     = copy.unit_price * copy.quantity;
                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                });
            });

            this.PayonePaymentService.refundPayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('payone-payment.refund.successTitle'),
                    message: this.$tc('payone-payment.refund.successMessage')
                });

                this.isRefundSuccessful = true;
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.refund.errorTitle'),
                    message: error.message
                });

                this.isRefundSuccessful = false;
            }).finally(() => {
                this.isLoading = false;
                this.closeRefundModal();

                this.$nextTick().then(() => {
                    this.$emit('reload')
                });
            });
        },

        refundFullOrder() {
            const request = {
                orderTransactionId: this.transaction.id,
                payone_order_id: this.transaction.customFields.payone_transaction_id,
                salesChannel: this.order.salesChannel,
                amount: this.maxRefundAmount,
                orderLines: [],
                complete: true,
            };

            this.isLoading = true;

            this._populateSelectionProperty();

            this.selection.forEach((selection) => {
                this.order.lineItems.forEach((order_item) => {
                    if (order_item.id === selection.id && 0 < selection.quantity) {
                        const copy = { ...order_item },
                            taxRate = copy.tax_rate / (10 ** this.order.currency.decimalPrecision);

                        copy.quantity         = selection.quantity;
                        copy.total_amount     = copy.unit_price * copy.quantity;
                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                });
            });

            this.PayonePaymentService.refundPayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('payone-payment.refund.successTitle'),
                    message: this.$tc('payone-payment.refund.successMessage')
                });

                this.isRefundSuccessful = true;
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.refund.errorTitle'),
                    message: error.message
                });

                this.isRefundSuccessful = false;
            }).finally(() => {
                this.isLoading = false;
                this.closeRefundModal();

                this.$nextTick().then(() => {
                    this.$emit('reload')
                });
            });
        },

        onSelectItem(id, selected) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.id === id) {
                    selection.selected = selected;
                }
            });

            this.calculateRefundAmount();
        },

        onChangeQuantity(id, quantity) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.id === id) {
                    selection.quantity = quantity;
                }
            });

            this.calculateRefundAmount();
        },

        _populateSelectionProperty() {
            this.order.lineItems.forEach((order_item) => {
                let quantity = order_item.quantity;

                if (order_item.customFields && order_item.customFields.payone_refunded_quantity
                    && 0 < order_item.customFields.payone_refunded_quantity) {
                    quantity -= order_item.customFields.payone_refunded_quantity;
                }

                this.selection.push({
                    id: order_item.id,
                    quantity: quantity,
                    unit_price: order_item.unitPrice,
                    selected: false
                });
            });
        }
    }
});
