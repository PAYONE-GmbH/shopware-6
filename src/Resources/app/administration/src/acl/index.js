Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'additional_permissions',
    parent: null,
    key: 'Payone',
    roles: {
        payone_order_management: {
            privileges: [
                'order_transaction:update',
                'order_line_item:update',
                'order.viewer',
                'order:read',
                'user_config:read',
                'state_machine_history:create',
                'order_address:read',
                'sales_channel:read',
                'order_customer:read',
                'currency:read',
                'document:read',
                'order_transaction:read',
                'order_delivery:read'
            ],
            dependencies: []
        }
    }
});

