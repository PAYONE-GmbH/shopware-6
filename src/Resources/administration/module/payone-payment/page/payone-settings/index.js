import { Mixin } from 'src/core/shopware';
import template from './payone-settings.html.twig';

export default {
    name: 'payone-settings',

    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['PayonePaymentApiCredentialsService'],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            config: {},
            merchantIdFilled: false,
            accountIdFilled: false,
            portalIdFilled: false,
            portalKeyFilled: false,
            showValidationErrors: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onConfigChange(config) {
            this.config = config;

            this.checkCredentialsFilled();

            this.showValidationErrors = false;
        },

        checkCredentialsFilled() {
            const defaultConfig = this.$refs.systemConfig.actualConfigData.null;
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (salesChannelId === null) {
                this.merchantIdFilled = !!this.config['PayonePayment.settings.merchantId'];
                this.accountIdFilled = !!this.config['PayonePayment.settings.accountId'];
                this.portalIdFilled = !!this.config['PayonePayment.settings.portalId'];
                this.portalKeyFilled = !!this.config['PayonePayment.settings.portalKey'];
            } else {
                this.merchantIdFilled = !!this.config['PayonePayment.settings.merchantId']
                    || !!defaultConfig['PayonePayment.settings.merchantId'];
                this.accountIdFilled = !!this.config['PayonePayment.settings.accountId']
                    || !!defaultConfig['PayonePayment.settings.accountId'];
                this.portalIdFilled = !!this.config['PayonePayment.settings.portalId']
                    || !!defaultConfig['PayonePayment.settings.portalId'];
                this.portalKeyFilled = !!this.config['PayonePayment.settings.portalKey']
                    || !!defaultConfig['PayonePayment.settings.portalKey'];
            }
        },

        onSave() {
            if (!this.merchantIdFilled || !this.accountIdFilled || !this.portalIdFilled || !this.portalKeyFilled) {
                this.showValidationErrors = true;
                return;
            }

            this.isSaveSuccessful = false;
            this.isLoading = true;
            this.$refs.systemConfig.saveAll().then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onTest() {
            this.isLoading = true;
            this.PayonePaymentApiCredentialsService.validateApiCredentials(
                this.config['PayonePayment.settings.merchantId'],
                this.config['PayonePayment.settings.accountId'],
                this.config['PayonePayment.settings.portalId'],
                this.config['PayonePayment.settings.portalKey'],
                this.config['PayonePayment.settings.transactionMode']
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-payment.settingForm.titleSuccess'),
                        message: this.$tc('payone-payment.settingForm.messageTestSuccess')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('payone-payment.settingForm.titleError'),
                        message: this.$tc('payone-payment.settingForm.messageTestError')
                    });
                }
                this.isLoading = false;
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.settingForm.titleError'),
                    message: this.$tc('payone-payment.settingForm.messageTestError')
                });
                this.isLoading = false;
            });
        },

        getBind(element, config) {
            if (config !== this.config) {
                this.onConfigChange(config);
            }
            if (this.showValidationErrors) {
                if (element.name === 'PayonePayment.settings.merchantId' && !this.merchantIdFilled) {
                    element.config.error = {
                        code: 1,
                        detail: this.$tc('payone-payment.messageNotBlank')
                    };
                }
                if (element.name === 'PayonePayment.settings.accountId' && !this.accountIdFilled) {
                    element.config.error = {
                        code: 1,
                        detail: this.$tc('payone-payment.messageNotBlank')
                    };
                }
                if (element.name === 'PayonePayment.settings.portalId' && !this.portalIdFilled) {
                    element.config.error = {
                        code: 1,
                        detail: this.$tc('payone-payment.messageNotBlank')
                    };
                }
                if (element.name === 'PayonePayment.settings.portalKey' && !this.portalKeyFilled) {
                    element.config.error = {
                        code: 1,
                        detail: this.$tc('payone-payment.messageNotBlank')
                    };
                }
            }

            return element;
        }
    }
};
