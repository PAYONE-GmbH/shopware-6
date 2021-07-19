import './page/payone-notification-target-list';
import './page/payone-notification-target-detail';
import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Shopware.Module.register('payone-notification-target', {
    type: 'plugin',
    name: 'PayoneNotificationTarget',
    title: 'payonePayment.notificationTarget.module.title',
    description: 'payonePayment.notificationTarget.module.title',
    color: '#3596d6',
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
