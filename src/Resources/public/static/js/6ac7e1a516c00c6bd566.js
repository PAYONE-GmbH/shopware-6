(window["webpackJsonpPluginpayone-payment"]=window["webpackJsonpPluginpayone-payment"]||[]).push([[992],{807:function(){},992:function(e,t,n){"use strict";n.r(t),n.d(t,{default:function(){return s}}),n(410);let{Mixin:i,Filter:r}=Shopware;var s={template:'{% block payone_payment_payment_details %}\n    <div class="payone-refund-button">\n        <sw-container v-tooltip="{message: $tc(\'sw-order.payone-payment.refund.tooltips.impossible\'), disabled: buttonEnabled}" :key="buttonEnabled">\n            <sw-button :disabled="!buttonEnabled" @click="openRefundModal">\n                {{ $tc(\'sw-order.payone-payment.refund.buttonTitle\') }}\n            </sw-button>\n        </sw-container>\n\n        <sw-modal v-if="showRefundModal" @modal-close="closeRefundModal" :title="$tc(`sw-order.payone-payment.modal.refund.title`)" class="payone-payment-detail--refund-modal">\n            <payone-order-items :order="order" :items="items"></payone-order-items>\n\n            <div class="payone-payment-detail--refund-modal--content">\n                <sw-container columns="1fr 1fr" gap="0 32px">\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.orderAmount\')" :value="currencyFilter(transaction.amount.totalPrice, order.currency.shortName)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.refund.refunded\')" :value="payoneCurrencyFilter(refundedAmount, order.currency.shortName)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.remainingAmount\')" :value="payoneCurrencyFilter(remainingAmount, order.currency.shortName)"></sw-text-field>\n                    <sw-number-field required="required" numberType="float" :digits="order.decimal_precision" :label="$tc(\'sw-order.payone-payment.modal.refund.amount\')"\n                                     v-model:value="refundAmount"\n                                     :min="0"\n                                     :max="maxRefundAmount"></sw-number-field>\n                </sw-container>\n            </div>\n\n            <template #modal-footer>\n                <sw-button :disabled="isLoading" @click="closeRefundModal">\n                    {{ $tc(\'sw-order.payone-payment.modal.close\') }}\n                </sw-button>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isRefundSuccessful" @process-finish="onRefundFinished()" :disabled="isLoading || refundAmount <= 0" variant="primary" @click="refundOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.refund.submit\') }}\n                </sw-button-process>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isRefundSuccessful" @process-finish="onRefundFinished()" :disabled="isLoading" variant="primary" @click="refundFullOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.refund.fullSubmit\') }}\n                </sw-button-process>\n            </template>\n        </sw-modal>\n    </div>\n{% endblock %}\n',mixins:[i.getByName("notification")],inject:["PayonePaymentService"],props:{order:{type:Object,required:!0},transaction:{type:Object,required:!0}},data(){return{isLoading:!1,hasError:!1,showRefundModal:!1,isRefundSuccessful:!1,refundAmount:0,includeShippingCosts:!1,items:[]}},computed:{currencyFilter(){return r.getByName("currency")},payoneCurrencyFilter(){return r.getByName("payone_currency")},decimalPrecision(){return this.order&&this.order.currency?this.order.currency.decimalPrecision?this.order.currency.decimalPrecision:this.order.currency.itemRounding?this.order.currency.itemRounding.decimals:void 0:2},remainingAmount(){let e=this.transaction?.extensions?.payonePaymentOrderTransactionData??{};return(e.capturedAmount??0)-(e.refundedAmount??0)},refundedAmount(){return this.transaction?.extensions?.payonePaymentOrderTransactionData?.refundedAmount??0},maxRefundAmount(){return this.remainingAmount/10**this.decimalPrecision},buttonEnabled(){return!!this.transaction?.extensions?.payonePaymentOrderTransactionData&&(this.remainingAmount>0&&this.refundedAmount>0||this.transaction.extensions.payonePaymentOrderTransactionData.allowRefund)},selectedItems(){return this.items.filter(e=>e.selected&&e.quantity>0)},hasRemainingRefundableShippingCosts(){if(this.order.shippingCosts.totalPrice<=0)return!1;let e=this.order.shippingCosts.totalPrice*10**this.decimalPrecision,t=0;return this.order.lineItems.forEach(e=>{e.customFields&&e.customFields.payone_refunded_quantity&&0<e.customFields.payone_refunded_quantity&&(t+=e.customFields.payone_refunded_quantity*e.unitPrice*10**this.decimalPrecision)}),this.refundedAmount-Math.round(t)<e}},methods:{calculateActionAmount(){let e=0;this.selectedItems.forEach(t=>{e+=t.unit_price*t.quantity}),this.refundAmount=e>this.remainingAmount?this.remainingAmount:e},openRefundModal(){this.showRefundModal=!0,this.isRefundSuccessful=!1,this.initItems()},initItems(){this.items=this.order.lineItems.map(e=>{let t=this.getRefundableQuantityOfItem(e);return{id:e.id,quantity:t,maxQuantity:t,unit_price:e.unitPrice,selected:!1,product:e.label,disabled:t<=0}}),this.order.shippingCosts.totalPrice>0&&this.items.push({id:"shipping",quantity:1,maxQuantity:1,unit_price:this.order.shippingCosts.totalPrice,selected:!1,disabled:!1,product:this.$tc("sw-order.payone-payment.modal.shippingCosts")})},closeRefundModal(){this.showRefundModal=!1},onRefundFinished(){this.isRefundSuccessful=!1},refundOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.refundAmount,orderLines:[],complete:this.refundAmount===this.maxRefundAmount,includeShippingCosts:!1};this.isLoading=!0,this.selectedItems.forEach(t=>{if("shipping"===t.id)e.includeShippingCosts=!0;else{let n=this.order.lineItems.find(e=>e.id===t.id);if(n){let i={...n},r=i.tax_rate/10**e.decimalPrecision;i.quantity=t.quantity,i.total_amount=i.unit_price*i.quantity,i.total_tax_amount=Math.round(i.total_amount/(100+r)*r),e.orderLines.push(i)}}}),this.remainingAmount<e.amount*10**this.decimalPrecision&&(e.amount=this.remainingAmount/10**this.decimalPrecision),this.executeRefund(e)},getRefundableQuantityOfItem(e){return(e.customFields?.payone_captured_quantity??e.quantity)-(e.customFields?.payone_refunded_quantity??0)},refundFullOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.maxRefundAmount,orderLines:[],complete:!0,includeShippingCosts:this.hasRemainingRefundableShippingCosts};this.isLoading=!0,e.orderLines=this.order.lineItems.map(e=>({id:e.id,quantity:this.getRefundableQuantityOfItem(e),unit_price:e.unitPrice,selected:!1})),this.executeRefund(e)},executeRefund(e){this.PayonePaymentService.refundPayment(e).then(()=>{this.createNotificationSuccess({title:this.$tc("sw-order.payone-payment.refund.successTitle"),message:this.$tc("sw-order.payone-payment.refund.successMessage")}),this.isRefundSuccessful=!0}).catch(e=>{this.createNotificationError({title:this.$tc("sw-order.payone-payment.refund.errorTitle"),message:e.message}),this.isRefundSuccessful=!1}).finally(()=>{this.isLoading=!1,this.closeRefundModal(),this.$nextTick().then(()=>{this.$emit("reload")})})}},watch:{items:{handler(){this.calculateActionAmount()},deep:!0}}}},410:function(e,t,n){var i=n(807);i.__esModule&&(i=i.default),"string"==typeof i&&(i=[[e.id,i,""]]),i.locals&&(e.exports=i.locals),n(346).Z("afa32006",i,!0,{})},346:function(e,t,n){"use strict";function i(e,t){for(var n=[],i={},r=0;r<t.length;r++){var s=t[r],a=s[0],o={id:e+":"+r,css:s[1],media:s[2],sourceMap:s[3]};i[a]?i[a].parts.push(o):n.push(i[a]={id:a,parts:[o]})}return n}n.d(t,{Z:function(){return f}});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var s={},a=r&&(document.head||document.getElementsByTagName("head")[0]),o=null,d=0,u=!1,c=function(){},l=null,m="data-vue-ssr-id",p="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function f(e,t,n,r){u=n,l=r||{};var a=i(e,t);return h(a),function(t){for(var n=[],r=0;r<a.length;r++){var o=s[a[r].id];o.refs--,n.push(o)}t?h(a=i(e,t)):a=[];for(var r=0;r<n.length;r++){var o=n[r];if(0===o.refs){for(var d=0;d<o.parts.length;d++)o.parts[d]();delete s[o.id]}}}}function h(e){for(var t=0;t<e.length;t++){var n=e[t],i=s[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(g(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{for(var a=[],r=0;r<n.parts.length;r++)a.push(g(n.parts[r]));s[n.id]={id:n.id,refs:1,parts:a}}}}function y(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function g(e){var t,n,i=document.querySelector("style["+m+'~="'+e.id+'"]');if(i){if(u)return c;i.parentNode.removeChild(i)}if(p){var r=d++;t=w.bind(null,i=o||(o=y()),r,!1),n=w.bind(null,i,r,!0)}else t=v.bind(null,i=y()),n=function(){i.parentNode.removeChild(i)};return t(e),function(i){i?(i.css!==e.css||i.media!==e.media||i.sourceMap!==e.sourceMap)&&t(e=i):n()}}var b=function(){var e=[];return function(t,n){return e[t]=n,e.filter(Boolean).join("\n")}}();function w(e,t,n,i){var r=n?"":i.css;if(e.styleSheet)e.styleSheet.cssText=b(t,r);else{var s=document.createTextNode(r),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(s,a[t]):e.appendChild(s)}}function v(e,t){var n=t.css,i=t.media,r=t.sourceMap;if(i&&e.setAttribute("media",i),l.ssrId&&e.setAttribute(m,t.id),r&&(n+="\n/*# sourceURL="+r.sources[0]+" */\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}}]);
//# sourceMappingURL=6ac7e1a516c00c6bd566.js.map