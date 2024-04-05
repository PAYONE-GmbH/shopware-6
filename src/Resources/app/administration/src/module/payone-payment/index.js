import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';
import './filter/payone_currency.filter';
import './component/payone-payment-settings';

Shopware.Component.register('payone-payment-plugin-icon', () => import('./component/payone-payment-plugin-icon'));
Shopware.Component.register('payone-ratepay-profile-configurations', () => import('./component/payone-ratepay-profile-configurations'));
Shopware.Component.register('payone-ratepay-profiles', () => import('./component/payone-ratepay-profiles'));
Shopware.Component.register('payone-settings', () => import('./page/payone-settings'));

Shopware.Module.register('payone-payment', {
    type: 'plugin',
    name: 'PayonePayment',
    title: 'payone-payment.general.mainMenuItemGeneral',
    description: 'payone-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    icon: 'regular-cog',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        next(currentRoute);
    },

    routes: {
        index: {
            component: 'payone-settings',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    settingsItem: [{
        name:   'payone-payment',
        to:     'payone.payment.index',
        label:  'payone-payment.general.mainMenuItemGeneral',
        group:  'plugins',
        iconComponent: 'payone-payment-plugin-icon',
        backgroundEnabled: false
    }],
});
