import template from './payone-payment-order-action-log.html.twig';

const {Criteria} = Shopware.Data;

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
            orderActionLogs: [],
            isLoading: false,
            showRequestDetails: null,
            showResponseDetails: null,
        };
    },

    computed: {
        orderActionLogRepository() {
            return this.repositoryFactory.create('payone_payment_order_action_log');
        },

        orderActionLogColumns() {
            return [
                {
                    property: 'transactionId',
                    label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleTransactionId')
                },
                {
                    property: 'request',
                    label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleRequest')
                },
                {
                    property: 'response',
                    label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleResponse')
                },
                {
                    property: 'amount',
                    label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleAmount')
                },
                {
                    property: 'requestDateTime',
                    label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleRequestDateTime')
                },
            ];
        },

        keyValueColumns() {
            return [
                {property: 'key', label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleKey')},
                {property: 'value', label: this.$tc('sw-order.payone-payment.orderActionLog.columnTitleValue')},
            ];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getOrderActionLogs();
        },

        reloadActionLogs() {
            this.getOrderActionLogs();
        },

        getOrderActionLogs() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('orderId', this.order.id));
            criteria.addSorting(Criteria.sort('requestDateTime', 'ASC', true));

            this.isLoading = true;
            return this.orderActionLogRepository.search(criteria, Shopware.Context.api).then((searchResult) => {
                this.orderActionLogs = searchResult;
                this.isLoading = false;
            });
        },

        openRequest(orderActionLog) {
            this.showRequestDetails = orderActionLog.requestDetails;
        },

        openResponse(orderActionLog) {
            this.showResponseDetails = orderActionLog.responseDetails;
        },

        onCloseRequestModal() {
            this.showRequestDetails = null;
        },

        onCloseResponseModal() {
            this.showResponseDetails = null;
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
