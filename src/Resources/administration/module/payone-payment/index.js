const { Module } = Shopware;
import payoneSettings from './page/payone-settings';

import './extension/sw-order';
import './extension/sw-settings-index';
import './extension/sw-plugin-list';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Module.register('payone-payment', {
    type: 'plugin',
    name: 'PayonePayment',
    title: 'payone-payment.general.mainMenuItemGeneral',
    description: 'payone-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    icon: 'default-action-settings',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        next(currentRoute);
    },

    routes: {
        index: {
            component: payoneSettings,
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
