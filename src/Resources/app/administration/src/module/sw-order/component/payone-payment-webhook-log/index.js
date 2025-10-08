import template from './payone-payment-webhook-log.html.twig';

const {Criteria} = Shopware.Data;
const { Filter } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    props: {
        order: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            webhookLogs: [],
            isLoading: false,
            showWebhookDetails: null,
        };
    },

    computed: {
        webhookLogRepository() {
            return this.repositoryFactory.create('payone_payment_webhook_log');
        },

        assetFilter() {
            return Filter.getByName('asset');
        },

        dateFilter() {
            return Filter.getByName('date');
        },

        webhookLogColumns() {
            return [
                {
                    property: 'transactionId',
                    label: this.$t('sw-order.payone-payment.webhookLog.columnTitleTransactionId')
                },
                {
                    property: 'transactionState',
                    label: this.$t('sw-order.payone-payment.webhookLog.columnTitleTransactionState')
                },
                {
                    property: 'sequenceNumber',
                    label: this.$t('sw-order.payone-payment.webhookLog.columnTitleSequenceNumber')
                },
                {
                    property: 'clearingType',
                    label: this.$t('sw-order.payone-payment.webhookLog.columnTitleClearingType')
                },
                {
                    property: 'webhookDateTime',
                    label: this.$t('sw-order.payone-payment.webhookLog.columnTitleWebhookDateTime')
                },
            ];
        },

        keyValueColumns() {
            return [
                {property: 'key', label: this.$t('sw-order.payone-payment.webhookLog.columnTitleKey')},
                {property: 'value', label: this.$t('sw-order.payone-payment.webhookLog.columnTitleValue')},
            ];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getWebhookLogs();
        },

        reloadWebhookLogs() {
            this.getWebhookLogs();
        },

        getWebhookLogs() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('orderId', this.order.id));
            criteria.addSorting(Criteria.sort('webhookDateTime', 'ASC', true));

            this.isLoading = true;
            return this.webhookLogRepository.search(criteria, Shopware.Context.api).then((searchResult) => {
                this.webhookLogs = searchResult;
                this.isLoading = false;
            });
        },

        openDetails(webhookLog) {
            this.showWebhookDetails = webhookLog.webhookDetails;
        },

        onCloseWebhookModal() {
            this.showWebhookDetails = null;
        },

        toKeyValueSource(object) {
            const data = [];

            for (const key in object) {
                data.push({key, value: object[key]});
            }

            data.sort((a, b) => a.key.localeCompare(b.key));

            return data;
        },

        downloadAsTxt(object, objectType, id) {
            const link = document.createElement('a');
            link.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(object, null, 4));
            link.download = `PAYONE-${objectType}-${id}.txt`;
            link.dispatchEvent(new MouseEvent('click'));
            link.remove();
        },
    }
};
