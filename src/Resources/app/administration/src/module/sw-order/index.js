Shopware.Component.register('payone-capture-button', () => import('./component/payone-capture-button'));
Shopware.Component.register('payone-order-items', () => import('./component/payone-order-items'));
Shopware.Component.register('payone-payment-management', () => import('./component/payone-payment-management'));
Shopware.Component.register('payone-payment-order-action-log', () => import('./component/payone-payment-order-action-log'));
Shopware.Component.register('payone-payment-webhook-log', () => import('./component/payone-payment-webhook-log'));
Shopware.Component.register('payone-refund-button', () => import('./component/payone-refund-button'));
Shopware.Component.register('sw-order-detail-payone', () => import('./view/sw-order-detail-payone'));

Shopware.Component.override('sw-order-detail', () => import('./page/sw-order-detail'));

Shopware.Module.register('sw-order-detail-tab-payone', {
  routeMiddleware(next, currentRoute) {
    if (currentRoute.name === 'sw.order.detail') {
      currentRoute.children.push({
        name: 'sw.order.detail.payone',
        path: 'payone',
        component: 'sw-order-detail-payone',
        meta: {
          parentPath: "sw.order.detail",
          meta: {
            parentPath: 'sw.order.index',
            privilege: 'order.viewer',
          },
        }
      });
    }
    next(currentRoute);
  }
});
