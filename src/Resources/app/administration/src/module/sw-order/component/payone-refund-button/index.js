import template from './payone-refund-button.html.twig';
import './payone-refund-button.scss';

const {Mixin, Filter} = Shopware;

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
            refundAmount: 0.0,
            includeShippingCosts: false,
            items: [],
        };
    },

    computed: {
        currencyFilter() {
            return Filter.getByName('currency');
        },

        payoneCurrencyFilter() {
            return Filter.getByName('payone_currency');
        },

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
            const data = this.transaction?.extensions?.payonePaymentOrderTransactionData ?? {};
            return (data.capturedAmount ?? 0) - (data.refundedAmount ?? 0);
        },

        refundedAmount() {
            return this.transaction?.extensions?.payonePaymentOrderTransactionData?.refundedAmount ?? 0;
        },

        maxRefundAmount() {
            return this.remainingAmount / (10 ** this.decimalPrecision);
        },

        buttonEnabled() {
            if (!this.transaction?.extensions?.payonePaymentOrderTransactionData) {
                return false;
            }

            return (this.remainingAmount > 0 && this.refundedAmount > 0) || this.transaction.extensions.payonePaymentOrderTransactionData.allowRefund;
        },

        selectedItems() {
            return this.items.filter(item => item.selected && item.quantity > 0);
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

            return this.refundedAmount - Math.round(refundedPositionAmount) < shippingCosts;
        }
    },

    methods: {
        calculateActionAmount() {
            let amount = 0;

            this.selectedItems.forEach((selection) => {
                amount += selection.unit_price * selection.quantity;
            });

            this.refundAmount = amount > this.remainingAmount ? this.remainingAmount : amount;
        },

        openRefundModal() {
            this.showRefundModal = true;
            this.isRefundSuccessful = false;
            this.initItems();
        },

        initItems() {
            this.items = this.order.lineItems.map((orderItem) => {
                // note: if the order got captured during the payment (direct authorize instead of pre-authorize), the field `payone_captured_quantity` will be empty.
                // in this case we are using the ordered quantity
                const qty = this.getRefundableQuantityOfItem(orderItem);

                return {
                    id: orderItem.id,
                    quantity: qty,
                    maxQuantity: qty,
                    unit_price: orderItem.unitPrice,
                    selected: false,
                    product: orderItem.label,
                    disabled: qty <= 0,
                };
            });

            if (this.order.shippingCosts.totalPrice > 0) {
                this.items.push({
                    id: 'shipping',
                    quantity: 1,
                    maxQuantity: 1,
                    unit_price: this.order.shippingCosts.totalPrice,
                    selected: false,
                    disabled: false,
                    product: this.$tc('sw-order.payone-payment.modal.shippingCosts'),
                });
            }
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

            this.selectedItems.forEach((selection) => {
                if (selection.id === 'shipping') {
                    request.includeShippingCosts = true;
                } else {
                    const orderLineItem = this.order.lineItems.find(lineItem => lineItem.id === selection.id);
                    if (orderLineItem) {
                        const copy = {...orderLineItem};
                        const taxRate = copy.tax_rate / (10 ** request.decimalPrecision);

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;
                        copy.total_tax_amount = Math.round(copy.total_amount / (100 + taxRate) * taxRate);

                        request.orderLines.push(copy);
                    }
                }
            });

            if (this.remainingAmount < (request.amount * (10 ** this.decimalPrecision))) {
                request.amount = this.remainingAmount / (10 ** this.decimalPrecision);
            }

            this.executeRefund(request);
        },

        getRefundableQuantityOfItem(orderItem) {
            return (orderItem.customFields?.payone_captured_quantity ?? orderItem.quantity) - (orderItem.customFields?.payone_refunded_quantity ?? 0);
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

            request.orderLines = this.order.lineItems.map((orderItem) => {
                return {
                    id: orderItem.id,
                    quantity: this.getRefundableQuantityOfItem(orderItem),
                    unit_price: orderItem.unitPrice,
                    selected: false
                };
            });

            this.executeRefund(request);
        },

        executeRefund(request) {
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
    },
    watch: {
        items: {
            handler() {
                this.calculateActionAmount();
            },
            deep: true,
        }
    }
};
