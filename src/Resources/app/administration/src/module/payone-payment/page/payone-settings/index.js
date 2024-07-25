import template from './payone-settings.html.twig';
import './style.scss';

const {Mixin} = Shopware;

export default {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: ['PayonePaymentSettingsService', 'acl'],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            isApplePayCertConfigured: true,
            isSupportModalOpen: false,
            stateMachineTransitionActions: [],
            displayStatusMapping: {},
        };
    },

    created() {
        this.createdComponent();
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        createdComponent() {
            this.PayonePaymentSettingsService.hasApplePayCert()
                .then((result) => {
                    this.isApplePayCertConfigured = result;
                });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        testFinish() {
            this.isTestSuccessful = false;
        },

        getConfigValue(field) {
            const actualConfig = this.$refs.systemConfig.actualConfigData;
            const defaultConfig = actualConfig.null;
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (salesChannelId === null) {
                return actualConfig.null[`PayonePayment.settings.${field}`];
            }

            return actualConfig[salesChannelId][`PayonePayment.settings.${field}`]
                || defaultConfig[`PayonePayment.settings.${field}`];
        },

        getPaymentConfigValue(field, prefix) {
            let uppercasedField = field.charAt(0).toUpperCase() + field.slice(1);

            return this.getConfigValue(prefix + uppercasedField)
                || this.getConfigValue(field);
        },

        onSave() {
            this.isSaveSuccessful = false;
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then((response) => {
                this.handleRatepayProfileUpdates(response);
                this.isSaveSuccessful = true;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onTest() {
            this.isTesting = true;
            this.isTestSuccessful = false;

            let credentials = {};
            this.$refs.systemConfig.config.forEach((cards) => {
                const match = cards.name.match(/^payment_(.+)$/);
                const paymentMethodKey = match ? match[1] : null
                if (!paymentMethodKey) {
                    return;
                }

                credentials[paymentMethodKey] = {
                    merchantId: this.getPaymentConfigValue('merchantId', paymentMethodKey),
                    accountId: this.getPaymentConfigValue('accountId', paymentMethodKey),
                    portalId: this.getPaymentConfigValue('portalId', paymentMethodKey),
                    portalKey: this.getPaymentConfigValue('portalKey', paymentMethodKey)
                };
            });

            this.PayonePaymentSettingsService.validateApiCredentials(credentials).then((response) => {
                const testCount = response.testCount;
                const credentialsValid = response.credentialsValid;
                const errors = response.errors;

                if (credentialsValid) {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-payment.settingsForm.titleSuccess'),
                        message: testCount > 0
                            ? this.$tc('payone-payment.settingsForm.messageTestSuccess')
                            : this.$tc('payone-payment.settingsForm.messageTestNoTestedPayments'),
                    });
                    this.isTestSuccessful = true;
                } else {
                    for (let key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            this.createNotificationError({
                                title: this.$tc('payone-payment.settingsForm.titleError'),
                                message: this.$tc('payone-payment.settingsForm.messageTestError.' + key)
                            });
                            let message = errors[key];
                            if (typeof message === 'string') {
                                this.createNotificationError({
                                    title: this.$tc('payone-payment.settingsForm.titleError'),
                                    message: message
                                });
                            }
                        }
                    }
                }
            }).finally((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.settingsForm.titleError'),
                    message: this.$tc('payone-payment.settingsForm.messageTestError.general')
                });
                this.isTesting = false;
            });
        },

        handleRatepayProfileUpdates(response) {
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (response.payoneRatepayProfilesUpdateResult && response.payoneRatepayProfilesUpdateResult[salesChannelId]) {
                const resultBySalesChannel = response.payoneRatepayProfilesUpdateResult[salesChannelId];

                this.$root.$emit(
                    'payone-ratepay-profiles-update-result',
                    resultBySalesChannel
                );

                if (!Array.isArray(resultBySalesChannel.errors)) {
                    this.createNotificationError({
                        title: this.$tc('payone-payment.settingsForm.titleError'),
                        message: this.$tc('payone-payment.settingsForm.messageSaveError.ratepayProfilesUpdateFailed')
                    });
                }
            }
        }
    }
};
