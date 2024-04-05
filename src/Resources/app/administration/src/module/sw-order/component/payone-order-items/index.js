import template from './payone-order-items.html.twig';
import './payone-order-items.scss';

const {Filter} = Shopware;

export default {
    template,

    props: {
        items: {
            type: Array,
            required: true
        },
        order: {
            type: Object,
            required: true
        }
    },

    computed: {
        currencyFilter: () => Filter.getByName('currency'),

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
        updateSelection(selection) {
            const selectionIds = Object.keys(selection);
            this.items.forEach(item => {
                item.selected = selectionIds.includes(item.id);
            });
        },
    }
};
