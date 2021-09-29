const { Component, Mixin } = Shopware;
const { object, types } = Shopware.Utils;

import template from './payone-settings.html.twig';
import './style.scss';

Component.register('payone-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [ 'PayonePaymentSettingsService' ],

       data() {
        return {
            isLoading: false,
            isTesting: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            config: {},
            merchantIdFilled: false,
            accountIdFilled: false,
            portalIdFilled: false,
            portalKeyFilled: false,
            showValidationErrors: false,
            isSupportModalOpen: false,
            stateMachineTransitionActions: [],
            displayStatusMapping: {},
            collapsibleState: {
                'status_mapping': true,
                'payment_credit_card': true,
                'payment_paypal': true,
                'payment_paypal_express': true,
                'payment_debit': true,
                'payment_sofort': true,
                'payment_payolution_installment': true,
                'payment_payolution_invoicing': true,
                'payment_payolution_debit': true,
                'payment_eps': true,
                'payment_ideal': true,
                'payment_paydirekt': true,
                'payment_prepayment': true,
                'payment_trustly': true,
                'payment_secure_invoice': true,
            },
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        credentialsMissing: function() {
            return !this.merchantIdFilled || !this.accountIdFilled || !this.portalIdFilled || !this.portalKeyFilled;
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    methods: {
        createdComponent() {
            let me = this;

            this.PayonePaymentSettingsService.getStateMachineTransitionActions()
                .then((result) => {
                    result.data.forEach((element) => {
                        let translationKey = 'payone-payment.transitionActionNames.' + element.label;
                        let translationValue = me.$t(translationKey);

                        if (translationValue === translationKey) {
                            translationValue = element.label;
                        }

                        me.stateMachineTransitionActions.push({
                            "label": translationValue,
                            "value": element.value,
                        })
                    });
                });
        },

        paymentMethodPrefixes() {
            // TODO: Autogenerate config array with these prefixes
            return [
                'creditCard',
                'debit',
                'paypal',
                'paypalExpress',
                'payolutionInvoicing',
                'payolutionInstallment',
                'payolutionDebit',
                'sofort',
                'eps',
                'iDeal',
                'paydirekt',
                'prepayment',
                'trustly',
                'secureInvoice',
            ];
        },

        isVisiblePaymentMethodCard(card) {
            return card.name.startsWith('payment') && !this.isCollapsed(card);
        },

        isCollapsible(card) {
            return card.name in this.collapsibleState;
        },

        displayField(element, config, card) {
            if (!(card.name in this.collapsibleState)) {
                return true;
            }

            return !this.collapsibleState[card.name];
        },

        isCollapsed(card) {
            return this.collapsibleState[card.name];
        },

        toggleCollapsible(card) {
            if (!(card.name in this.collapsibleState)) {
                return;
            }

            this.collapsibleState[card.name] = !this.collapsibleState[card.name];
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        testFinish() {
            this.isTestSuccessful = false;
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

        getPaymentConfigValue(field, prefix) {
            let uppercasedField = field.charAt(0).toUpperCase() + field.slice(1);

            return this.getConfigValue(prefix + uppercasedField)
                || this.getConfigValue(field);
        },

        onSave() {
            if (this.credentialsMissing) {
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
            this.isTesting = true;
            this.isTestSuccessful = false;

            let credentials = {};
            this.paymentMethodPrefixes().forEach((prefix) => {
                credentials[prefix] = {
                    merchantId: this.getPaymentConfigValue('merchantId', prefix),
                    accountId: this.getPaymentConfigValue('accountId', prefix),
                    portalId: this.getPaymentConfigValue('portalId', prefix),
                    portalKey: this.getPaymentConfigValue('portalKey', prefix)
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
                    for(let key in errors) {
                        if(errors.hasOwnProperty(key)) {
                            this.createNotificationError({
                                title: this.$tc('payone-payment.settingsForm.titleError'),
                                message: this.$tc('payone-payment.settingsForm.messageTestError.' + key)
                            });
                        }
                    }
                }
                this.isTesting = false;
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('payone-payment.settingsForm.titleError'),
                    message: this.$tc('payone-payment.settingsForm.messageTestError.general')
                });
                this.isTesting = false;
            });
        },

        getBind(element, config) {
            let originalElement;

            if (config !== this.config) {
                this.config = config;
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

            this.$refs.systemConfig.config.forEach((configElement) => {
                configElement.elements.forEach((child) => {
                    if (child.name === element.name) {
                        originalElement = child;
                        return;
                    }
                });
            });

            return originalElement || element;
        },

        getElementBind(element) {
            const bind = object.deepCopyObject(element);

            // Add inherited values
            if (this.currentSalesChannelId !== null
                && this.inherit
                && this.actualConfigData.hasOwnProperty('null')
                && this.actualConfigData.null[bind.name] !== null) {
                if (bind.type === 'single-select' || bind.config.componentName === 'sw-entity-single-select') {
                    // Add inherited placeholder option
                    bind.placeholder = this.$tc('sw-settings.system-config.inherited');
                } else if (bind.type === 'bool') {
                    // Add inheritedValue for checkbox fields to restore the inherited state
                    bind.config.inheritedValue = this.actualConfigData.null[bind.name] || false;
                } else if (bind.type === 'password') {
                    // Add inherited placeholder and mark placeholder as password so the rendering element
                    // can choose to hide it
                    bind.placeholderIsPassword = true;
                    bind.placeholder = `${this.actualConfigData.null[bind.name]}`;
                } else if (bind.type !== 'multi-select' && !types.isUndefined(this.actualConfigData.null[bind.name])) {
                    // Add inherited placeholder
                    bind.placeholder = `${this.actualConfigData.null[bind.name]}`;
                }
            }

            // Add select properties
            if (['single-select', 'multi-select'].includes(bind.type)) {
                bind.config.labelProperty = 'name';
                bind.config.valueProperty = 'id';
            }

            return bind;
        },
    }
});
