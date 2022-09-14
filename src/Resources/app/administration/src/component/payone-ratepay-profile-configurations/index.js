import './payone-ratepay-profile-configurations.scss';
import template from './payone-ratepay-profile-configurations.html.twig';

const {Component} = Shopware;

Component.register('payone-ratepay-profile-configurations', {
    template,

    inject: ['PayonePaymentSettingsService'],

    props: {
        value: {
            type: Object,
            required: false,
            default() {
                return {};
            }
        },
        name: {
            type: String,
            required: true
        },
    },

    data() {
        return {
            isLoading: false,
            configuration: this.value
        }
    },

    created() {
        this.createdComponent();
    },

    destroyed() {
        this.destroyedComponent();
    },

    computed: {
        profileConfigurations() {
            const name = this.name;
            let profileConfigurations = [];

            for (const shopId in this.configuration) {
                let minBasket = '';
                let maxBasket = '';

                switch (name) {
                    case 'PayonePayment.settings.ratepayDebitProfileConfigurations':
                        minBasket = this.configuration[shopId]['tx-limit-elv-min'];
                        maxBasket = this.configuration[shopId]['tx-limit-elv-max'];
                        break;
                    case 'PayonePayment.settings.ratepayInstallmentProfileConfigurations':
                        minBasket = this.configuration[shopId]['tx-limit-installment-min'];
                        maxBasket = this.configuration[shopId]['tx-limit-installment-max'];
                        break;
                    case 'PayonePayment.settings.ratepayInvoicingProfileConfigurations':
                        minBasket = this.configuration[shopId]['tx-limit-invoice-min'];
                        maxBasket = this.configuration[shopId]['tx-limit-invoice-max'];
                        break;
                    default:
                        return;
                }

                const profileConfig = {
                    'shopId': shopId,
                    'shopCurrency': this.configuration[shopId]['currency'],
                    'invoiceCountry': this.configuration[shopId]['country-code-billing'],
                    'shippingCountry': this.configuration[shopId]['country-code-delivery'],
                    'minBasket': minBasket,
                    'maxBasket': maxBasket
                }

                profileConfigurations.push(profileConfig);
            }

            return profileConfigurations;
        }
    },

    methods: {
        createdComponent() {
            this.$root.$on('payone-ratepay-profiles-update-result', this.onProfilesUpdateResult);
            console.log(this.configuration);
        },

        destroyedComponent() {
            this.$root.$off('payone-ratepay-profiles-update-result');
        },

        onProfilesUpdateResult(result) {
            if (result['updates'][this.name]) {
                this.configuration = result['updates'][this.name];
            }
        }
    }
});
