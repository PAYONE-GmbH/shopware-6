import template from './payone-payment-plugin-icon.html.twig';
import './payone-payment-plugin-icon.scss';

export default {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
};
