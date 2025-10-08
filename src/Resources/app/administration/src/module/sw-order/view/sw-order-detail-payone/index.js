import template from './sw-order-detail-payone.html.twig';

const {  Store } = Shopware;

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
        order: () => Store.get('swOrderDetail').order,
    },

    methods: {
        reloadEntityData() {
            this.$refs.payoneOrderActionLogs?.reloadActionLogs();
            this.$refs.payoneWebhookLogs?.reloadWebhookLogs();
            this.$emit('reload-entity-data');
        },
    }
};
