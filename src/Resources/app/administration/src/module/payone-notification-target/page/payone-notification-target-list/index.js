import template from './payone-notification-target-list.html.twig';
import { Component, Mixin } from 'src/core/shopware';
const { Criteria } = Shopware.Data;

Component.register('payone-notification-target-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            isLoading: false,
            items: null,
            sortBy: 'createdAt'
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        notificationTargetColumns() {
            return [
                {
                    dataIndex: 'url',
                    property: 'url',
                    label: 'payonePayment.notificationTarget.columns.url',
                    primary: true
                },
                {
                    dataIndex: 'isBasicAuth',
                    property: 'isBasicAuth',
                    label: 'payonePayment.notificationTarget.columns.isBasicAuth'
                },
                {
                    property: 'txactions',
                    label: 'payonePayment.notificationTarget.columns.txactions'
                },
            ];
        },
        repository() {
            return this.repositoryFactory.create('payone_payment_notification_target');
        },
        criteria() {
            const criteria = new Criteria();

            return criteria;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.getList();
        },

        getList() {
            this.isLoading = true;

            const context = { ...Shopware.Context.api, inheritance: true };
            return this.repository.search(this.criteria, context).then((result) => {
                this.total = result.total;
                this.items = result;
                this.isLoading = false;
            });
        },

        onDelete(option) {
            this.$refs.listing.deleteItem(option);
            this.getList();
        },
    }
})
