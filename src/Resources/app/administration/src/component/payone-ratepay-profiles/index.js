import './payone-ratepay-profiles.scss';
import template from './payone-ratepay-profiles.html.twig';

const { Component } = Shopware;

Component.register('payone-ratepay-profiles', {
    template,

    props: {
        value: {
            type: Array,
            required: false,
            default() {
                return [];
            }
        },
    },

    watch: {
        value(value) {
            this.$emit('input', value);
            this.$emit('change', value);
        },
    },

    methods: {
        addProfile(profile) {
            this.value.push(profile);
        }
    }
});
