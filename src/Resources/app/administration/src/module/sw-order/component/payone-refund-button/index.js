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

        orderTotalPrice() {
            return this.transaction.amount.totalPrice;
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

        transactionData() {
            return this.transaction?.extensions?.payonePaymentOrderTransactionData ?? {
                capturedAmount: 0,
                refundedAmount: 0,
                allowRefund: false
            };
        },

        capturedAmount() {
            return this.toFixedPrecision((this.transaction?.extensions?.payonePaymentOrderTransactionData?.capturedAmount ?? 0) / 100);
        },

        remainingAmount() {
            return (this.transactionData.capturedAmount ?? 0) - (this.transactionData.refundedAmount ?? 0);
        },

        refundedAmount() {
            return this.transactionData.refundedAmount ?? 0;
        },

        buttonEnabled() {
            if (!this.transaction?.extensions?.payonePaymentOrderTransactionData) {
                return false;
            }

            return this.remainingAmount > 0 || this.transactionData.allowRefund;
        },

        selectedItems() {
            return this.items.filter(item => item.selected && item.quantity > 0);
        },

        hasRemainingRefundableShippingCosts() {
            if (this.order.shippingCosts.totalPrice <= 0) {
                return false;
            }

            return this.toFixedPrecision(this.refundedAmount + this.order.shippingCosts.totalPrice) <= this.capturedAmount;
        }
    },

    methods: {
        toFixedPrecision(value) {
            return Math.round(value * (10 ** this.decimalPrecision)) / (10 ** this.decimalPrecision);
        },

        calculateActionAmount() {
            let amount = 0;

            this.selectedItems.forEach((selection) => {
                amount += selection.unit_price * selection.quantity;
            });

            this.refundAmount = this.toFixedPrecision(amount > this.remainingAmount ? this.remainingAmount : amount);
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
                    orderItem: orderItem,
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

                        copy.quantity = selection.quantity;
                        copy.total_amount = copy.unit_price * copy.quantity;
                        copy.total_tax_amount = copy.total_amount - (copy.total_amount / (1 + (copy.tax_rate / 100)));

                        request.orderLines.push(copy);
                    }
                }
            });

            if (this.remainingAmount < request.amount) {
                request.amount = this.remainingAmount;
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
