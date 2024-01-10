import template from './payone-refund-button.html.twig';
import './payone-refund-button.scss';

const { Mixin } = Shopware;

export default {
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
            refundAmount: 0.0,
            includeShippingCosts: false
        };
    },

    computed: {
        decimalPrecision() {
            if (!this.order || !this.order.currency) {
                return 2;
            }
            if (this.order.currency.decimalPrecision) {
                return this.order.currency.decimalPrecision;
            }
            if (this.order.currency.itemRounding) {
                return this.order.currency.itemRounding.decimals;
            }
        },

        remainingAmount() {
            if (!this.transaction.extensions
                || !this.transaction.extensions.payonePaymentOrderTransactionData
                || !this.transaction.extensions.payonePaymentOrderTransactionData.capturedAmount) {
                return 0;
            }

            return this.transaction.extensions.payonePaymentOrderTransactionData.capturedAmount - this.refundedAmount;
        },

        refundedAmount() {
            if (!this.transaction.extensions
                || !this.transaction.extensions.payonePaymentOrderTransactionData
                || !this.transaction.extensions.payonePaymentOrderTransactionData.refundedAmount) {
                return 0;
            }

            return this.transaction.extensions.payonePaymentOrderTransactionData.refundedAmount;
        },

        maxRefundAmount() {
            return this.remainingAmount / (10 ** this.decimalPrecision);
        },

        buttonEnabled() {
            if (!this.transaction.extensions
                || !this.transaction.extensions.payonePaymentOrderTransactionData) {
                return false;
            }

            return (this.remainingAmount > 0 && this.refundedAmount > 0) || this.transaction.extensions.payonePaymentOrderTransactionData.allowRefund;
        },

        hasRemainingRefundableShippingCosts() {
            if (this.order.shippingCosts.totalPrice <= 0) {
                return false;
            }

            const shippingCosts = this.order.shippingCosts.totalPrice * (10 ** this.decimalPrecision);

            let refundedPositionAmount = 0;

            this.order.lineItems.forEach((order_item) => {
                if (order_item.customFields && order_item.customFields.payone_refunded_quantity
                    && 0 < order_item.customFields.payone_refunded_quantity) {
                    refundedPositionAmount += order_item.customFields.payone_refunded_quantity * order_item.unitPrice * (10 ** this.decimalPrecision);
                }
            });

            if (this.refundedAmount - Math.round(refundedPositionAmount) >= shippingCosts) {
                return false;
            }

            return true;
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

            if (Math.round(amount * (10 ** this.decimalPrecision) > this.remainingAmount)) {
                amount = this.remainingAmount / (10 ** this.decimalPrecision)
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
                payone_order_id: this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,
                salesChannel: this.order.salesChannel,
                amount: this.refundAmount,
                orderLines: [],
                complete: this.refundAmount === this.maxRefundAmount,
                includeShippingCosts: false
            };
            this.isLoading = true;

            this.selection.forEach((selection) => {
                this.order.lineItems.forEach((order_item) => {
                    if (order_item.id === selection.id && selection.selected && 0 < selection.quantity) {
                        const copy = { ...order_item },
                            taxRate = copy.tax_rate / (10 ** this.decimalPrecision);

                        copy.quantity         = selection.quantity;
                        copy.total_amount     = copy.unit_price * copy.quantity;
                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                });

                if (selection.id === 'shipping' && selection.selected && 0 < selection.quantity) {
                    request.includeShippingCosts = true;
                }
            });

            this.PayonePaymentService.refundPayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('sw-order.payone-payment.refund.successTitle'),
                    message: this.$tc('sw-order.payone-payment.refund.successMessage')
                });

                this.isRefundSuccessful = true;
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('sw-order.payone-payment.refund.errorTitle'),
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
                payone_order_id: this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,
                salesChannel: this.order.salesChannel,
                amount: this.maxRefundAmount,
                orderLines: [],
                complete: true,
                includeShippingCosts: this.hasRemainingRefundableShippingCosts
            };

            this.isLoading = true;

            this.order.lineItems.forEach((order_item) => {
                let quantity = order_item.quantity;

                if (order_item.customFields && order_item.customFields.payone_refunded_quantity
                    && 0 < order_item.customFields.payone_refunded_quantity) {
                    quantity -= order_item.customFields.payone_refunded_quantity;
                }

                request.orderLines.push({
                    id: order_item.id,
                    quantity: quantity,
                    unit_price: order_item.unitPrice,
                    selected: false
                });
            });

            this.PayonePaymentService.refundPayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('sw-order.payone-payment.refund.successTitle'),
                    message: this.$tc('sw-order.payone-payment.refund.successMessage')
                });

                this.isRefundSuccessful = true;
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('sw-order.payone-payment.refund.errorTitle'),
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

            if (this.order.shippingCosts.totalPrice > 0) {
                this.selection.push({
                    id: 'shipping',
                    quantity: 1,
                    unit_price: this.order.shippingCosts.totalPrice,
                    selected: false
                });
            }
        }
    }
};
