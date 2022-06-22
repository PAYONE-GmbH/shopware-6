import './payone-ratepay-profile-configurations.scss';
import template from './payone-ratepay-profile-configurations.html.twig';

const { Component } = Shopware;

Component.register('payone-ratepay-profile-configurations', {
    template,

    inject: [ 'PayonePaymentSettingsService' ],

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

    methods: {
        createdComponent() {
            this.$root.$on('payone-settings-save-successful', this.loadProfile);
            this.$root.$on('payone-settings-save-failed', this.loadProfile);
        },

        destroyedComponent() {
            this.$root.$off('payone-settings-save-successful');
            this.$root.$off('payone-settings-save-failed');
        },

        loadProfile(salesChannelId) {
            this.isLoading = true;
            this.PayonePaymentSettingsService.getSettingValue(this.name, salesChannelId)
                .then((result) => {
                    this.configuration = result.value;
                    this.isLoading = false;
                });
        }
    }
});
