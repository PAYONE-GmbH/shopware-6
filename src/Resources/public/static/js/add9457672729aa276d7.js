"use strict";(window["webpackJsonpPluginpayone-payment"]=window["webpackJsonpPluginpayone-payment"]||[]).push([[536],{536:function(n,a,e){e.r(a),e.d(a,{default:function(){return t}});var t={template:'{% block sw_order_detail_content_tabs_extension %}\n    {% parent %}\n\n    {% block sw_order_detail_content_tabs_payone %}\n        <sw-tabs-item\n                v-if="canAccessPayoneTab()"\n                class="sw-order-detail__tabs-tab-payone"\n                :route="{ name: \'sw.order.detail.payone\', params: { id: $route.params.id } }"\n                :title="$tc(\'sw-order.detail.payone\')"\n        >\n            {{ $tc(\'sw-order.detail.payone\') }}\n        </sw-tabs-item>\n    {% endblock %}\n\n{% endblock %}\n',inject:["acl"],methods:{hasPayoneTransaction(n){let a=this,e=!1;return!!n.transactions&&(n.transactions.map(function(n){a.isPayoneTransaction(n)&&a.isActiveTransaction(n)&&(e=!0)}),e)},isPayoneTransaction(n){return!!n.extensions&&!!n.extensions.payonePaymentOrderTransactionData&&!!n.extensions.payonePaymentOrderTransactionData.transactionId&&n.extensions.payonePaymentOrderTransactionData.transactionId},isActiveTransaction(n){return"cancelled"!==n.stateMachineState.technicalName},canAccessPayoneTab(){return(this.acl.can("payone_order_management")||this.acl.can("payone_payment_order_action_log:read")||this.acl.can("payone_payment_notification_forward:read")||this.acl.can("payone_payment_webhook_log:read"))&&this.order&&this.hasPayoneTransaction(this.order)}}}}}]);