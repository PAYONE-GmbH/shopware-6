import template from './payone-payment-management.html.twig';
import './payone-payment-management.scss';

const {Mixin, Filter} = Shopware;
const {Criteria} = Shopware.Data;

export default {
    template,

    inject: ['acl', 'PayonePaymentService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        order: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            notificationForwards: null
        };
    },

    computed: {
        dateFilter() {
            return Filter.getByName('date');
        },

        payoneTransactions: function () {
            return this.order.transactions.filter(transaction => this.isPayoneTransaction(transaction)).sort((a, b) => { // newest transaction first
                if (a.createdAt < b.createdAt) {
                    return 1;
                } else if (a.createdAt > b.createdAt) {
                    return -1;
                } else {
                    return 0;
                }
            });
        },

        notificationForwardRepository() {
            return this.repositoryFactory.create('payone_payment_notification_forward');
        },

        notificationTargetColumns() {
            return [{
                property: 'txaction',
                type: 'text',
                width: '100px'
            }, {
                property: 'notificationTarget.url',
                type: 'text',
            }, {
                property: 'response',
                width: '100px'
            }, {
                property: 'updatedAt',
                align: 'right',
                type: 'date'
            }];
        },
    },

    methods: {
        isPayoneTransaction(transaction) {
            if (!transaction.extensions || !transaction.extensions.payonePaymentOrderTransactionData || !transaction.extensions.payonePaymentOrderTransactionData.transactionId) {
                return false;
            }

            return transaction.extensions.payonePaymentOrderTransactionData.transactionId;
        },

        isActiveTransaction(transaction) {
            return transaction.stateMachineState.technicalName !== 'cancelled';
        },

        hasNotificationForwards(transaction) {
            if (this.notificationForwards === null) {
                this.getNotificationForwards(transaction);
                return false;
            }

            return this.notificationForwards.length > 0;
        },

        getNotificationForwards(transaction) {
            const criteria = new Criteria();
            criteria.addAssociation('notificationTarget');
            criteria.addSorting(Criteria.sort('updatedAt', 'DESC', true));
            criteria.addFilter(Criteria.equals('transactionId', transaction.id));
            criteria.setLimit(500);

            return this.notificationForwardRepository.search(criteria, Shopware.Context.api)
                .then((searchResult) => {
                    this.notificationForwards = searchResult;
                });
        },

        requeue(notificationForward, transaction) {
            const request = {
                notificationForwardId: notificationForward.id
            };

            this.PayonePaymentService.requeueNotificationForward(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$t('payonePayment.notificationTarget.actions.requeue'),
                    message: this.$t('payonePayment.notificationTarget.messages.success')
                });

                this.getNotificationForwards(transaction);
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$t('payonePayment.notificationTarget.actions.requeue'),
                    message: error.message
                });
            }).finally(() => {
                this.$nextTick().then(() => {
                    this.$emit('reload')
                });
            });
        },

        reloadEntityData() {
            this.$emit('reload-entity-data');
        },

        getPayoneCardType(transaction) {
            let cardType = transaction.extensions.payonePaymentOrderTransactionData?.additionalData?.card_type;

            return cardType ? this.$t('sw-order.payone-payment.creditCard.cardTypes.' + cardType) : null;
        },
    }
};
