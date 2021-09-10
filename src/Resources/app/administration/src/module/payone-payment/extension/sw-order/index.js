const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
import template from './sw-order.html.twig';
import './sw-order.scss';

Component.override('sw-order-detail-base', {
    template,

    inject: ['PayonePaymentService', 'repositoryFactory', 'acl'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            disableButtons: false,
            notificationForwards: null
        };
    },

    computed: {
        payoneTransactions: function() {
            return this.order.transactions.filter(transaction => this.isPayoneTransaction(transaction)).sort((a, b) => { // newest transaction first
                if(a.createdAt < b.createdAt) {
                    return 1;
                } else if(a.createdAt > b.createdAt) {
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
            },{
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
        }
    },

    methods: {
        requeue(notificationForward, transaction) {
            const request = {
                notificationForwardId: notificationForward.id
            };

            this.PayonePaymentService.requeueNotificationForward(request).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('payonePayment.notificationTarget.actions.requeue'),
                    message: this.$tc('payonePayment.notificationTarget.messages.success')
                });

                this.getNotificationForwards(transaction);
            }).catch((error) => {
                this.createNotificationError({
                    title: this.$tc('payonePayment.notificationTarget.actions.requeue'),
                    message: error.message
                });
            }).finally(() => {
                this.$nextTick().then(() => {
                    this.$emit('reload')
                });
            });
        },

        isPayoneTransaction(transaction) {
            if (!transaction.customFields) {
                return false;
            }

            return transaction.customFields.payone_transaction_id;
        },

        hasNotificationForwards(transaction) {
            if(null === this.notificationForwards) {
                this.getNotificationForwards(transaction);
                return false;
            }

            if(this.notificationForwards.length <= 0) {
                return false;
            }

            return true;
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

        can: function(permission) {
            try {
                return this.acl.can(permission);
            } catch(e) {
                return true;
            }
        },

        isActiveTransaction(transaction) {
            return transaction.stateMachineState.technicalName !== 'cancelled';
        },

        hasPayoneTransaction(order) {
            let me = this;
            let isPayone = false;

            if (!order.transactions) {
                return false;
            }

            order.transactions.map(function(transaction) {
                if (me.isPayoneTransaction(transaction) && me.isActiveTransaction(transaction)) {
                    isPayone = true;
                }
            });

            return isPayone;
        }
    }
});
