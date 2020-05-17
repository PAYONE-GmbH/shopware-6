import template from './order-items.html.twig';

const { Component } = Shopware;

Component.register('payone-order-items', {
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
                    if ('refund' === this.mode && 0 < order_item.customFields.payone_captured_quantity) {
                        quantity = order_item.customFields.payone_captured_quantity;

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
                    reference: order_item.referencedId,
                    product: order_item.label,
                    quantity: quantity,
                    disabled: disabled,
                    price: price,
                    orderItem: order_item
                });
            });

            return data;
        },

        orderItemColumns() {
            return [
                {
                    property: 'reference',
                    label: this.$tc('payone-payment.modal.columns.reference'),
                    rawData: true
                },
                {
                    property: 'product',
                    label: this.$tc('payone-payment.modal.columns.product'),
                    rawData: true
                },
                {
                    property: 'quantity',
                    label: this.$tc('payone-payment.modal.columns.quantity'),
                    rawData: true
                },
                {
                    property: 'price',
                    label: this.$tc('payone-payment.modal.columns.price'),
                    rawData: true
                }
            ];
        }
    },

    methods: {
        onSelectItem(selection, item, selected) {
            this.$emit('select-item', item.id, selected);
        },

        onChangeQuantity(value, reference) {
            this.$emit('change-quantity', reference, value);
        }
    }
});
