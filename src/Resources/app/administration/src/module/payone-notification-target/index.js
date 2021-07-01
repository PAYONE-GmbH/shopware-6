import './page/payone-notification-target-list';
import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Shopware.Module.register('payone-notification-target', {
    type: 'plugin',
    name: 'PayoneNotificationTarget',
    title: 'payonePayment.notificationTarget.title',
    description: 'payonePayment.notificationTarget.description',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'payone-notification-target-list',
            path: 'list'
        },
       /* detail: {
            component: 'swag-example-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'payone.notification.target.list'
            }
        },
        create: {
            component: 'swag-example-create',
            path: 'create',
            meta: {
                parentPath: 'payone.notification.target.list'
            }
        }*/
    },

    navigation: [{
        label: 'payonePayment.notificationTarget.title',
        color: '#ff3d58',
        path: 'payone.notification.target.list',
        icon: 'default-shopping-paper-bag-product',
        parent: 'sw-settings',
        position: 100
    }]
});
