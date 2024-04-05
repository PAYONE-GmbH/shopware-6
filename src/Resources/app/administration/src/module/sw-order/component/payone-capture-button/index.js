import template from './payone-capture-button.html.twig';
import './payone-capture-button.scss';

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
            showCaptureModal: false,
            isCaptureSuccessful: false,
            captureAmount: 0.0,
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

        totalTransactionAmount() {
            return Math.round(this.transaction.amount.totalPrice * (10 ** this.decimalPrecision), 0);
        },

        capturedAmount() {
            return this.transaction?.extensions?.payonePaymentOrderTransactionData?.capturedAmount ?? 0;
        },

        remainingAmount() {
            return this.totalTransactionAmount - this.capturedAmount;
        },

        maxCaptureAmount() {
            return this.remainingAmount / (10 ** this.decimalPrecision);
        },

        buttonEnabled() {
            if (!this.transaction?.extensions?.payonePaymentOrderTransactionData) {
                return false;
            }

            return (this.remainingAmount > 0 && this.capturedAmount > 0) || this.transaction.extensions.payonePaymentOrderTransactionData.allowCapture;
        },

        selectedItems() {
            return this.items.filter(item => item.selected && item.quantity > 0);
        },

        hasRemainingShippingCosts() {
            if (this.order.shippingCosts.totalPrice <= 0) {
                return false;
            }

            const shippingCosts = this.order.shippingCosts.totalPrice * (10 ** this.decimalPrecision);

            let capturedPositionAmount = 0;

            this.order.lineItems.forEach((order_item) => {
                if (order_item.customFields && order_item.customFields.payone_captured_quantity
                    && 0 < order_item.customFields.payone_captured_quantity) {
                    capturedPositionAmount += order_item.customFields.payone_captured_quantity * order_item.unitPrice * (10 ** this.decimalPrecision);
                }
            });

            return this.capturedAmount - Math.round(capturedPositionAmount) < shippingCosts;
        }
    },

    methods: {
        calculateActionAmount() {
            let amount = 0;

            this.selectedItems.forEach((selection) => {
                amount += selection.unit_price * selection.quantity;
            });

            this.captureAmount = amount > this.remainingAmount ? this.remainingAmount : amount;
        },

        openCaptureModal() {
            this.showCaptureModal = true;
            this.isCaptureSuccessful = false;
            this.initItems();
        },

        initItems() {
            this.items = this.order.lineItems.map((orderItem) => {
                const qty = orderItem.quantity - (orderItem.customFields?.payone_captured_quantity ?? 0);

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

        closeCaptureModal() {
            this.showCaptureModal = false;
        },

        onCaptureFinished() {
            this.isCaptureSuccessful = false;
        },

        captureOrder() {
            const request = {
                orderTransactionId: this.transaction.id,
                payone_order_id: this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,
                salesChannel: this.order.salesChannel,
                amount: this.captureAmount,
                orderLines: [],
                complete: this.captureAmount === this.remainingAmount,
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

            this.executeCapture(request)
        },

        captureFullOrder() {
            const request = {
                orderTransactionId: this.transaction.id,
                payone_order_id: this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,
                salesChannel: this.order.salesChannel,
                amount: this.remainingAmount / (10 ** this.decimalPrecision),
                orderLines: [],
                complete: true,
                includeShippingCosts: this.hasRemainingShippingCosts
            };

            this.isLoading = true;

            request.orderLines = this.order.lineItems.map((orderItem) => {
                return {
                    id: orderItem.id,
                    quantity: orderItem.quantity - (orderItem.customFields?.payone_captured_quantity ?? 0),
                    unit_price: orderItem.unitPrice,
                    selected: false
                };
            });

            this.executeCapture(request);
        },

        executeCapture(request) {
            this.PayonePaymentService.capturePayment(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('sw-order.payone-payment.capture.successTitle'),
                    message: this.$tc('sw-order.payone-payment.capture.successMessage')
                });

                this.isCaptureSuccessful = true;
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('sw-order.payone-payment.capture.errorTitle'),
                    message: error.message
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
