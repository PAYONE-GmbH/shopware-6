(this["webpackJsonpPluginpayone-payment"]=this["webpackJsonpPluginpayone-payment"]||[]).push([[11],{wAwH:function(n,a,t){"use strict";t.r(a);a.default={template:'{% block sw_order_detail_content_tabs_extension %}\n    {% parent %}\n\n    {% block sw_order_detail_content_tabs_payone %}\n        <sw-tabs-item\n                v-if="order && hasPayoneTransaction(order)"\n                class="sw-order-detail__tabs-tab-payone"\n                :route="{ name: \'sw.order.detail.payone\', params: { id: $route.params.id } }"\n                :title="$tc(\'sw-order.detail.payone\')"\n        >\n            {{ $tc(\'sw-order.detail.payone\') }}\n        </sw-tabs-item>\n    {% endblock %}\n\n{% endblock %}',methods:{hasPayoneTransaction:function(n){var a=this,t=!1;return!!n.transactions&&(n.transactions.map((function(n){a.isPayoneTransaction(n)&&a.isActiveTransaction(n)&&(t=!0)})),t)},isPayoneTransaction:function(n){return!!(n.extensions&&n.extensions.payonePaymentOrderTransactionData&&n.extensions.payonePaymentOrderTransactionData.transactionId)&&n.extensions.payonePaymentOrderTransactionData.transactionId},isActiveTransaction:function(n){return"cancelled"!==n.stateMachineState.technicalName}}}}}]);