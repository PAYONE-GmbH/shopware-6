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

            Object.entries(this.configuration).forEach(function (shop) {
                let minBasket = '';
                let maxBasket = '';

                switch (name) {
                    case 'PayonePayment.settings.ratepayDebitProfileConfigurations':
                        minBasket = shop[1]['tx-limit-prepayment-min'];
                        maxBasket = shop[1]['tx-limit-prepayment-max'];
                        break;
                    case 'PayonePayment.settings.ratepayInstallmentProfileConfigurations':
                        minBasket = shop[1]['tx-limit-installment-min'];
                        maxBasket = shop[1]['tx-limit-installment-max'];
                        break;
                    case 'PayonePayment.settings.ratepayInvoicingProfileConfigurations':
                        minBasket = shop[1]['tx-limit-invoice-min'];
                        maxBasket = shop[1]['tx-limit-invoice-max'];
                        break;
                    default:
                        return;
                }

                const profileConfig = {
                    'shopId': shop[0],
                    'shopCurrency': shop[1]['currency'],
                    'invoiceCountry': shop[1]['country-code-billing'],
                    'shippingCountry': shop[1]['country-code-delivery'],
                    'minBasket': minBasket,
                    'maxBasket': maxBasket
                }

                profileConfigurations.push(profileConfig);
            });

            return profileConfigurations;
        }
    },

    methods: {
        createdComponent() {
            this.$root.$on('payone-ratepay-profiles-update-result', this.onProfilesUpdateResult);
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
