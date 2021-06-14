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
                    'order_delivery:read',
                    'order_line_item:read',
                    'shipping_method:read',
                    'country:read',
                    'country_state:read',
                    'payment_method:read',
                    'document_type:read',
                    'tag:read',
                    'custom_field_set:read',
                    'custom_field:read',
                    'custom_field_set_relation:read',
                    'state_machine_history:read',
                    'state_machine_state:read',
                    'user:read',
                    'state_machine_state:read',
                    'state_machine:read'
                ],
                dependencies: []
            }
        }
    });
} catch(e) {

}
