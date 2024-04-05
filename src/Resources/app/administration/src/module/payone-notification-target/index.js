import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Shopware.Component.register('payone-notification-target-detail', import('./page/payone-notification-target-detail'));
Shopware.Component.register('payone-notification-target-list', import('./page/payone-notification-target-list'));

Shopware.Module.register('payone-notification-target', {
    type: 'plugin',
    name: 'PayoneNotificationTarget',
    title: 'payonePayment.notificationTarget.module.title',
    description: 'payonePayment.notificationTarget.module.title',
    icon: 'regular-cog',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'payone-notification-target-list',
            path: 'list'
        },
       detail: {
            component: 'payone-notification-target-detail',
            path: 'detail/:id',
            props: {
               default(route) {
                   return {
                       notificationTargetId: route.params.id
                   };
               }
            },
            meta: {
                parentPath: 'payone.notification.target.list'
            }
        },
        create: {
            component: 'payone-notification-target-detail',
            path: 'create',
            meta: {
                parentPath: 'payone.notification.target.list'
            }
        }
    }
});
