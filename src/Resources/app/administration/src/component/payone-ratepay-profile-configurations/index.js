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
