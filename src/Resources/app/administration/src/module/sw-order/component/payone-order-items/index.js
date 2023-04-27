import template from './payone-order-items.html.twig';
import './payone-order-items.scss';

export default {
    template,

    props: {
        order: {
            type: Object,
            required: true
        },

        mode: {
            type: String,
            required: false
        }
    },

    computed: {
        orderItems() {
            const data = [];

            this.order.lineItems.forEach((order_item) => {
                const price = this.$options.filters.currency(
                    order_item.totalPrice,
                    this.order.currency.shortName,
                    this.order.decimal_precision
                );

                let disabled = false;
                let quantity = order_item.quantity;

                if(order_item.customFields) {
                    if ('refund' === this.mode) {
                        if(order_item.customFields.payone_captured_quantity &&
                            0 > order_item.customFields.payone_captured_quantity) {
                            quantity = order_item.customFields.payone_captured_quantity;
                        }

                        if(order_item.customFields.payone_refunded_quantity) {
                            quantity -= order_item.customFields.payone_refunded_quantity;
                        }
                    } else if ('capture' === this.mode && order_item.customFields.payone_captured_quantity &&
                        0 < order_item.customFields.payone_captured_quantity) {
                        quantity -= order_item.customFields.payone_captured_quantity;
                    }
                }

                if (1 > quantity) {
                    disabled = true;
                }

                data.push({
                    id: order_item.id,
                    product: order_item.label,
                    quantity: quantity,
                    disabled: disabled,
                    selected: false,
                    price: price,
                    orderItem: order_item
                });
            });

            if (this.order.shippingCosts.totalPrice > 0) {
                data.push({
                    id: 'shipping',
                    product: this.$tc('sw-order.payone-payment.modal.shippingCosts'),
                    quantity: 1,
                    disabled: false,
                    selected: false,
                    price: this.$options.filters.currency(this.order.shippingCosts.totalPrice, this.order.currency.shortName, this.order.decimal_precision),
                    orderItem: {}
                });
            }

            return data;
        },

        orderItemColumns() {
            return [
                {
                    property: 'product',
                    label: this.$tc('sw-order.payone-payment.modal.columns.product'),
                    rawData: true
                },
                {
                    property: 'quantity',
                    label: this.$tc('sw-order.payone-payment.modal.columns.quantity'),
                    rawData: true
                },
                {
                    property: 'price',
                    label: this.$tc('sw-order.payone-payment.modal.columns.price'),
                    rawData: true
                }
            ];
        }
    },

    methods: {
        onSelectItem(selection, item, selected) {
            this.$emit('select-item', item.id, selected);
        },

        onChangeQuantity(value, id) {
            this.$emit('change-quantity', id, value);
        }
    }
};
