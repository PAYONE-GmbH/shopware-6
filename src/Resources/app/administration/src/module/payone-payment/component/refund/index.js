import template from './refund.html.twig';

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
        },
        transaction: {
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
            description: ''
        };
    },

    computed: {
        remainingAmount() {
            if (!this.transaction.customFields) {
                return this.transaction.amount.totalPrice;
            }

            return this.transaction.customFields.payone_captured_amount - this.refundedAmount;
        },

        refundedAmount() {
            window.console.log(this.transaction);
            if (!this.transaction.customFields) {
                return 0;
            }

            return this.transaction.customFields.payone_refunded_amount === undefined ? 0 : this.transaction.customFields.payone_refunded_amount;
        },

        buttonEnabled() {
            if (!this.transaction.customFields) {
                return false;
            }

            return this.remainingAmount > 0;
        },

        maxRefundAmount() {
            return this.remainingAmount / (10 ** this.order.decimal_precision);
        },

        minRefundValue() {
            return 1 / (10 ** this.order.decimal_precision);
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

            if (amount === 0 || amount > this.remainingAmount) {
                amount = this.remainingAmount;
            }

            amount /= (10 ** this.order.decimal_precision);

            this.refundAmount = amount;
        },

        openRefundModal() {
            this.showRefundModal = true;
            this.isRefundSuccessful = false;

            this.refundAmount = this.remainingAmount / (10 ** this.order.currency.decimal_precision);
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
                this.order.order_lines.forEach((order_item) => {
                    if (order_item.reference === selection.reference && selection.selected && selection.quantity > 0) {
                        const copy = { ...order_item },
                            taxRate = copy.tax_rate / (10 ** this.order.decimal_precision);

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;

                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                });
            });

            this.PayonePaymentService.refundPayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('payone-payment-order-management.messages.refundSuccessTitle'),
                    message: this.$tc('payone-payment-order-management.messages.refundSuccessMessage')
                });

                this.isRefundSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('payone-payment-order-management.messages.refundErrorTitle'),
                    message: this.$tc('payone-payment-order-management.messages.refundErrorMessage')
                });

                this.isRefundSuccessful = false;
            }).finally(() => {
                this.$emit('reload');

                this.isLoading = false;
                this.showRefundModal = false;
            });
        },

        onSelectItem(reference, selected) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.selected = selected;
                }
            });

            this.calculateRefundAmount();
        },

        onChangeQuantity(reference, quantity) {
            if (this.selection.length === 0) {
                this._populateSelectionProperty();
            }

            this.selection.forEach((selection) => {
                if (selection.reference === reference) {
                    selection.quantity = quantity;
                }
            });

            this.calculateRefundAmount();
        },

        onChangeDescription(description) {
            const max_chars = 255;

            if (description.length >= max_chars) {
                description = description.substr(0, max_chars);
            }

            this.description = description;
        },

        _populateSelectionProperty() {
            this.order.order_lines.forEach((order_item) => {
                let quantity = order_item.quantity;

                if (order_item.captured_quantity > 0) {
                    quantity = order_item.captured_quantity;
                }

                this.selection.push({
                    quantity: quantity - order_item.refunded_quantity,
                    reference: order_item.reference,
                    unit_price: order_item.unit_price,
                    selected: false
                });
            });
        }
    }
});
