import template from './capture.html.twig';
import './style.scss';

const { Component, Mixin, Context } = Shopware;

Component.register('payone-capture-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['PayonePaymentService', 'repositoryFactory'],

    props: {
        order: {
            type: Object,
            required: true
        }
    },

    computed: {
        transaction() {
            return this.order.transactions[0];
        },

        lineItems() {
            const orderLineItemRepository = this.repositoryFactory.create('order_line_item');

            this.order.lineItems.forEach((order_item) => {
                if (order_item.id) {
                    orderLineItemRepository.get(order_item.id, Context.api).then((fullOrderItem) => {
                        window.console.log(fullOrderItem);
                    });
                }
            });

            return this.order.lineItems;
        },

        totalTransactionAmount() {
            return this.transaction.amount.totalPrice * (10 ** this.order.currency.decimalPrecision);
        },

        remainingAmount() {
            return this.totalTransactionAmount - this.capturedAmount;
        },

        capturedAmount() {
            if (!this.transaction.customFields || this.transaction.customFields.payone_captured_amount === undefined) {
                return 0;
            }

            return this.transaction.customFields.payone_captured_amount;
        },

        maxCaptureAmount() {
            return this.remainingAmount / (10 ** this.order.currency.decimalPrecision);
        },

        minCaptureAmount() {
            return 1 / (10 ** this.order.currency.decimalPrecision);
        },

        buttonEnabled() {
            if (!this.transaction.customFields) {
                return false;
            }

            return this.remainingAmount > 0;
        },
    },

    data() {
        return {
            isLoading: false,
            hasError: false,
            showCaptureModal: false,
            isCaptureSuccessful: false,
            selection: [],
            captureAmount: 0.0
        };
    },

    methods: {
        calculateCaptureAmount() {
            let amount = 0;

            this.selection.forEach((selection) => {
                if (selection.selected) {
                    amount += selection.unit_price * selection.quantity;
                }
            });

            if (0 === amount || amount > this.remainingAmount) {
                amount = this.remainingAmount;
            }

            this.captureAmount = amount;
        },

        openCaptureModal() {
            this.showCaptureModal = true;
            this.isCaptureSuccessful = false;

            this.calculateCaptureAmount();
            this.selection = [];

            window.console.log(this.order.lineItems);
            window.console.log(this.lineItems);
        },

        closeCaptureModal() {
            this.showCaptureModal = false;
        },

        onCaptureFinished() {
            this.isCaptureSuccessful = false;
        },

        captureOrder() {
            const request = {
                orderTransactionId: this.transaction.id,
                payone_order_id: this.transaction.customFields.payone_transaction_id,
                salesChannel: this.order.salesChannel,
                amount: this.captureAmount,
                orderLines: [],
                complete: this.captureAmount === this.remainingAmount
            };
            this.isLoading = true;

            this.selection.forEach((selection) => {
                this.lineItems.forEach((order_item) => {
                    if (order_item.id === selection.id && selection.selected && 0 < selection.quantity) {
                        const copy = { ...order_item };
                        const taxRate = copy.tax_rate / (10 ** request.decimalPrecision);

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;

                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                });
            });

            this.PayonePaymentService.capturePayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('payone-payment-order-management.messages.captureSuccessTitle'),
                    message: this.$tc('payone-payment-order-management.messages.captureSuccessMessage')
                });

                this.isCaptureSuccessful = true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('payone-payment-order-management.messages.captureErrorTitle'),
                    message: this.$tc('payone-payment-order-management.messages.captureErrorMessage')
                });

                this.isCaptureSuccessful = false;
            }).finally(() => {
                this.isLoading = false;
                this.closeCaptureModal();

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

            this.calculateCaptureAmount();
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

            this.calculateCaptureAmount();
        },

        onChangeDescription(description) {
            const max_chars = 255;

            if (description.length >= max_chars) {
                description = description.substr(0, max_chars);
            }

            this.description = description;
        },

        _populateSelectionProperty() {
            this.order.lineItems.forEach((order_item) => {
                let quantity = order_item.quantity;

                if (order_item.customFields && order_item.customFields.captured_quantity
                    && order_item.customFields.captured_quantity > 0) {
                    quantity -= order_item.customFields.captured_quantity;
                }

                this.selection.push({
                    id: order_item.id,
                    reference: order_item.referencedId,
                    quantity: quantity,
                    unit_price: order_item.unitPrice,
                    selected: false
                });
            });
        }
    }
});
