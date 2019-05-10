import { Module } from 'src/core/shopware';

import './extension/sw-order';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Module.register('payome-payment', {
    type: 'plugin',
    name: 'PayonePaypal',
    description: 'description',
    version: '1.0.0',
    targetVersion: '1.0.0',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routeMiddleware(next, currentRoute) {
        next(currentRoute);
    }
});
