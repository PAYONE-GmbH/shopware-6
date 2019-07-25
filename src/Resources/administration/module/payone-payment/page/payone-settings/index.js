import { Mixin } from 'src/core/shopware';
import Criteria from 'src/core/data-new/criteria.data';
import template from './payone-settings.html.twig';

export default {
    name: 'payone-settings',

    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
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
            this.merchantIdFilled = !!this.getConfigValue('merchantId');
            this.accountIdFilled = !!this.getConfigValue('accountId');
            this.portalIdFilled = !!this.getConfigValue('portalId');
            this.portalKeyFilled = !!this.getConfigValue('portalKey');
        },

        getConfigValue(field) {
            const defaultConfig = this.$refs.systemConfig.actualConfigData.null;
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (salesChannelId === null) {
                return this.config[`PayonePayment.settings.${field}`];
            }
            return this.config[`PayonePayment.settings.${field}`]
                    || defaultConfig[`PayonePayment.settings.${field}`];
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

        onTest(method = '') {
            this.isLoading = true;
            this.PayonePaymentApiCredentialsService.validateApiCredentials(this.$refs.systemConfig.currentSalesChannelId).then((response) => {
                const credentialsValid = response.credentialsValid;
                const errors = response.errors;

                if (credentialsValid) {
                    this.createNotificationSuccess({
                        title: this.$tc('payone-payment.settingForm.titleSuccess'),
                        message: this.$tc('payone-payment.settingForm.messageTestSuccess')
                    });
                } else {
                    for(let key in errors) {
                        if(errors.hasOwnProperty(key)) {
                            this.createNotificationError({
                                title: this.$tc('payone-payment.settingForm.titleError'),
                                message: this.$tc('payone-payment.settingForm.messageTestError.' + key)
                            });
                        }
                    }
                }
                this.isLoading = false;
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.settingForm.titleError'),
                    message: this.$tc('payone-payment.settingForm.messageTestError.general')
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
        },

        getPaymentStatusCriteria() {
            const criteria = new Criteria(1, 100);
            criteria.addFilter(Criteria.equals('stateMachine.technicalName', 'order_transaction.state'));

            return criteria;
        }
    }
};
