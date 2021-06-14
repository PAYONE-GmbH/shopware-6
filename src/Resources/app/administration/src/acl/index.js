Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'additional_permissions',
    parent: null,
    key: 'Payone',
    roles: {
        payone_order_management: {
            privileges: ['order_transaction:update', 'order_line_item:update', 'order.viewer', 'order:read', 'user_config:read', 'state_machine_history:create'],
            dependencies: []
        }
    }
});
