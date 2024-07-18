try {
    Shopware.Service('privileges').addPrivilegeMappingEntry({
        category: 'additional_permissions',
        parent: null,
        key: 'Payone',
        roles: {
            payone_order_management: {
                privileges: [
                    // 'order_transaction:update',
                    // 'order_line_item:update',
                    // 'state_machine_history:create',
                    // Shopware.Service('privileges').getPrivileges('order.viewer'),

                    'payone_order_management',

                    'payone_payment_order_transaction_data:read',
                    'payone_payment_order_transaction_data:update',

                    'payone_payment_order_action_log:read',
                    'payone_payment_order_action_log:create',

                    'payone_payment_webhook_log:read',
                    'payone_payment_notification_forward:read',
                    'payone_payment_notification_forward:create',
                ],
                dependencies: []
            },
            payone_configuration: {
                privileges: [
                    'system_config:read',
                    'system_config:create',
                    'system_config:update',
                    'system_config:delete',
                    'currency:read',
                    'sales_channel:read',
                    'payone:configuration'
                ],
                dependencies: []
            },
            payone_webhook_forward: {
                privileges: [
                    'payone_payment_notification_target:read',
                    'payone_payment_notification_target:create',
                    'payone_payment_notification_target:update',
                    'payone_payment_notification_target:delete',
                    'payone:manage_webhook_forwards'
                ],
                dependencies: []
            },
            payone_webhook_resend: {
                privileges: [
                    'payone_webhook_resend'
                ],
                dependencies: []
            }
        }
    });
} catch(e) {

}
