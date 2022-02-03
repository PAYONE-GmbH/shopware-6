try {
    Shopware.Service('privileges').addPrivilegeMappingEntry({
        category: 'additional_permissions',
        parent: null,
        key: 'Payone',
        roles: {
            payone_order_management: {
                privileges: [
                    'order_transaction:update',
                    'order_line_item:update',
                    'state_machine_history:create',
                    Shopware.Service('privileges').getPrivileges('order.viewer')
                ],
                dependencies: []
            }
        }
    });
} catch(e) {

}
