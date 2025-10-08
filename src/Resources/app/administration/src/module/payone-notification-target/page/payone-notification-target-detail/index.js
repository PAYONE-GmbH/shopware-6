import template from './payone-notification-target-detail.html.twig';

const {Mixin} = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel'
    },

    props: {
        notificationTargetId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            notificationTarget: null,
            isLoading: false,
            isSaveSuccessful: false,
            processSuccess: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier)
        };
    },

    computed: {
        notificationTargetIsLoading() {
            return this.isLoading || this.notificationTarget == null;
        },

        notificationTargetRepository() {
            return this.repositoryFactory.create('payone_payment_notification_target');
        },

        txactionsOptions() {
            return [
                { value: "appointed", label: "appointed" },
                { value: "capture", label: "capture" },
                { value: "paid", label: "paid" },
                { value: "underpaid", label: "underpaid" },
                { value: "cancelation", label: "cancelation" },
                { value: "refund", label: "refund" },
                { value: "debit", label: "debit" },
                { value: "transfer", label: "transfer" },
                { value: "reminder", label: "reminder" },
                { value: "vauthorization", label: "vauthorization" },
                { value: "vsettlement", label: "vsettlement" },
                { value: "invoice", label: "invoice" },
                { value: "failed", label: "failed" }
            ];
        }
    },

    watch: {
        notificationTargetId() {
            this.createdComponent();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.notificationTargetId) {
                this.loadEntityData();
                return;
            }

            if (!Shopware.Store.get('context').isSystemDefaultLanguage) {
                Shopware.Store.get('context').resetLanguageToDefault();
            }

            this.notificationTarget = this.notificationTargetRepository.create(Shopware.Context.api);
        },

        loadEntityData() {
            this.isLoading = true;

            this.notificationTargetRepository.get(this.notificationTargetId, Shopware.Context.api).then((notificationTarget) => {
                this.isLoading = false;

                this.notificationTarget = notificationTarget;

                if (null === notificationTarget.txactions) {
                    return;
                }

                if (!notificationTarget.txactions.length) {
                    this.notificationTarget.txactions = null;
                }
            });
        },

        isInvalid() {
            if (this.notificationTarget.isBasicAuth !== true) {
                return false;
            }

            if (this.notificationTarget.username && this.notificationTarget.password) {
                return false;
            }

            this.createNotificationError({
                message: this.$t(
                    'global.notification.notificationSaveErrorMessageRequiredFieldsInvalid'
                )
            });

            return true;
        },

        onSave() {
            if (this.isInvalid()) {
                return;
            }

            this.isLoading = true;

            this.notificationTargetRepository.save(this.notificationTarget, Shopware.Context.api).then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    message: this.$t(
                        'payonePayment.notificationTarget.messages.successfullySaved'
                    ),
                });

                if (this.notificationTargetId === null) {
                    this.$router.push({
                        name: 'payone.notification.target.detail',
                        params: {id: this.notificationTarget.id}
                    });
                    return;
                }

                this.loadEntityData();
            }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    message: this.$t(
                        'global.notification.notificationSaveErrorMessageRequiredFieldsInvalid'
                    )
                });
                throw exception;
            });
        },

        onCancel() {
            this.$router.push({name: 'payone.notification.target.list'});
        }
    }
};
