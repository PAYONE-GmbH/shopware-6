import template from './sw-order-detail-payone.html.twig';

const { Component } = Shopware;
const { mapState } = Component.getComponentHelper();

export default {
    template,

    props: {
        orderId: {
            type: String,
            required: true,
        },
    },

    computed: {
        ...mapState('swOrderDetail', [
            'order',
        ]),
    },

    methods: {
        reloadEntityData() {
            this.$emit('reload-entity-data');
        },
    }
};
