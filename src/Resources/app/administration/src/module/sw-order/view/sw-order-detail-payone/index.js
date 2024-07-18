import template from './sw-order-detail-payone.html.twig';

const { Component } = Shopware;
const { mapState } = Component.getComponentHelper();

export default {
    template,

    inject: ['acl'],

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
            this.$refs.payoneOrderActionLogs?.reloadActionLogs();
            this.$refs.payoneWebhookLogs?.reloadWebhookLogs();
            this.$emit('reload-entity-data');
        },
    }
};
