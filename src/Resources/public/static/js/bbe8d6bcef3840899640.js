(window["webpackJsonpPluginpayone-payment"]=window["webpackJsonpPluginpayone-payment"]||[]).push([[279],{174:function(){},279:function(e,t,n){"use strict";n.r(t),n.d(t,{default:function(){return a}}),n(4);let{Mixin:i,Filter:r}=Shopware;var a={template:'{% block payone_payment_payment_details %}\n    <div class="payone-capture-button">\n        <sw-container v-tooltip="{message: $tc(\'sw-order.payone-payment.capture.tooltips.impossible\'), disabled: buttonEnabled}" :key="buttonEnabled">\n            <sw-button :disabled="!buttonEnabled" @click="openCaptureModal">\n                {{ $tc(\'sw-order.payone-payment.capture.buttonTitle\') }}\n            </sw-button>\n        </sw-container>\n\n        <sw-modal v-if="showCaptureModal" @modal-close="closeCaptureModal" :title="$tc(`sw-order.payone-payment.modal.capture.title`)" class="payone-payment-detail--capture-modal">\n            <payone-order-items :order="order" :items="items"></payone-order-items>\n\n            <div class="payone-payment-detail--capture-modal--content">\n                <sw-container columns="1fr 1fr" gap="0 32px">\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.orderAmount\')" :value="currencyFilter(transaction.amount.totalPrice, order.currency.shortName)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.capture.captured\')" :value="payoneCurrencyFilter(capturedAmount, order.currency.shortName)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.remainingAmount\')" :value="payoneCurrencyFilter(remainingAmount, order.currency.shortName)"></sw-text-field>\n                    <sw-number-field required="required" numberType="float" :digits="order.decimal_precision" :label="$tc(\'sw-order.payone-payment.modal.capture.amount\')"\n                                     v-model:value="captureAmount"\n                                     :min="0"\n                                     :max="maxCaptureAmount"></sw-number-field>\n                </sw-container>\n            </div>\n\n            <template #modal-footer>\n                <sw-button :disabled="isLoading" @click="closeCaptureModal">\n                    {{ $tc(\'sw-order.payone-payment.modal.close\') }}\n                </sw-button>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading || captureAmount <= 0" variant="primary" @click="captureOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.capture.submit\') }}\n                </sw-button-process>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading || remainAmount <= 0" variant="primary" @click="captureFullOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.capture.fullSubmit\') }}\n                </sw-button-process>\n            </template>\n        </sw-modal>\n    </div>\n{% endblock %}\n',mixins:[i.getByName("notification")],inject:["PayonePaymentService"],props:{order:{type:Object,required:!0},transaction:{type:Object,required:!0}},data(){return{isLoading:!1,hasError:!1,showCaptureModal:!1,isCaptureSuccessful:!1,captureAmount:0,includeShippingCosts:!1,items:[]}},computed:{currencyFilter(){return r.getByName("currency")},payoneCurrencyFilter(){return r.getByName("payone_currency")},decimalPrecision(){return this.order&&this.order.currency?this.order.currency.decimalPrecision?this.order.currency.decimalPrecision:this.order.currency.itemRounding?this.order.currency.itemRounding.decimals:void 0:2},totalTransactionAmount(){return Math.round(this.transaction.amount.totalPrice*10**this.decimalPrecision,0)},capturedAmount(){return this.transaction?.extensions?.payonePaymentOrderTransactionData?.capturedAmount??0},remainingAmount(){return this.totalTransactionAmount-this.capturedAmount},maxCaptureAmount(){return this.remainingAmount/10**this.decimalPrecision},buttonEnabled(){return!!this.transaction?.extensions?.payonePaymentOrderTransactionData&&(this.remainingAmount>0&&this.capturedAmount>0||this.transaction.extensions.payonePaymentOrderTransactionData.allowCapture)},selectedItems(){return this.items.filter(e=>e.selected&&e.quantity>0)},hasRemainingShippingCosts(){if(this.order.shippingCosts.totalPrice<=0)return!1;let e=this.order.shippingCosts.totalPrice*10**this.decimalPrecision,t=0;return this.order.lineItems.forEach(e=>{e.customFields&&e.customFields.payone_captured_quantity&&0<e.customFields.payone_captured_quantity&&(t+=e.customFields.payone_captured_quantity*e.unitPrice*10**this.decimalPrecision)}),this.capturedAmount-Math.round(t)<e}},methods:{calculateActionAmount(){let e=0;this.selectedItems.forEach(t=>{e+=t.unit_price*t.quantity}),this.captureAmount=e>this.remainingAmount?this.remainingAmount:e},openCaptureModal(){this.showCaptureModal=!0,this.isCaptureSuccessful=!1,this.initItems()},initItems(){this.items=this.order.lineItems.map(e=>{let t=e.quantity-(e.customFields?.payone_captured_quantity??0);return{id:e.id,quantity:t,maxQuantity:t,unit_price:e.unitPrice,selected:!1,product:e.label,disabled:t<=0}}),this.order.shippingCosts.totalPrice>0&&this.items.push({id:"shipping",quantity:1,maxQuantity:1,unit_price:this.order.shippingCosts.totalPrice,selected:!1,disabled:!1,product:this.$tc("sw-order.payone-payment.modal.shippingCosts")})},closeCaptureModal(){this.showCaptureModal=!1},onCaptureFinished(){this.isCaptureSuccessful=!1},captureOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.captureAmount,orderLines:[],complete:this.captureAmount===this.remainingAmount,includeShippingCosts:!1};this.isLoading=!0,this.selectedItems.forEach(t=>{if("shipping"===t.id)e.includeShippingCosts=!0;else{let n=this.order.lineItems.find(e=>e.id===t.id);if(n){let i={...n},r=i.tax_rate/10**e.decimalPrecision;i.quantity=t.quantity,i.total_amount=i.unit_price*i.quantity,i.total_tax_amount=Math.round(i.total_amount/(100+r)*r),e.orderLines.push(i)}}}),this.remainingAmount<e.amount*10**this.decimalPrecision&&(e.amount=this.remainingAmount/10**this.decimalPrecision),this.executeCapture(e)},captureFullOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.remainingAmount/10**this.decimalPrecision,orderLines:[],complete:!0,includeShippingCosts:this.hasRemainingShippingCosts};this.isLoading=!0,e.orderLines=this.order.lineItems.map(e=>({id:e.id,quantity:e.quantity-(e.customFields?.payone_captured_quantity??0),unit_price:e.unitPrice,selected:!1})),this.executeCapture(e)},executeCapture(e){this.PayonePaymentService.capturePayment(e).then(()=>{this.createNotificationSuccess({title:this.$tc("sw-order.payone-payment.capture.successTitle"),message:this.$tc("sw-order.payone-payment.capture.successMessage")}),this.isCaptureSuccessful=!0}).catch(e=>{this.createNotificationError({title:this.$tc("sw-order.payone-payment.capture.errorTitle"),message:e.message}),this.isCaptureSuccessful=!1}).finally(()=>{this.isLoading=!1,this.closeCaptureModal(),this.$nextTick().then(()=>{this.$emit("reload")})})}},watch:{items:{handler(){this.calculateActionAmount()},deep:!0}}}},4:function(e,t,n){var i=n(174);i.__esModule&&(i=i.default),"string"==typeof i&&(i=[[e.id,i,""]]),i.locals&&(e.exports=i.locals),n(346).Z("31aa70b3",i,!0,{})},346:function(e,t,n){"use strict";function i(e,t){for(var n=[],i={},r=0;r<t.length;r++){var a=t[r],s=a[0],o={id:e+":"+r,css:a[1],media:a[2],sourceMap:a[3]};i[s]?i[s].parts.push(o):n.push(i[s]={id:s,parts:[o]})}return n}n.d(t,{Z:function(){return h}});var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var a={},s=r&&(document.head||document.getElementsByTagName("head")[0]),o=null,u=0,c=!1,d=function(){},l=null,p="data-vue-ssr-id",m="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function h(e,t,n,r){c=n,l=r||{};var s=i(e,t);return y(s),function(t){for(var n=[],r=0;r<s.length;r++){var o=a[s[r].id];o.refs--,n.push(o)}t?y(s=i(e,t)):s=[];for(var r=0;r<n.length;r++){var o=n[r];if(0===o.refs){for(var u=0;u<o.parts.length;u++)o.parts[u]();delete a[o.id]}}}}function y(e){for(var t=0;t<e.length;t++){var n=e[t],i=a[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(g(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{for(var s=[],r=0;r<n.parts.length;r++)s.push(g(n.parts[r]));a[n.id]={id:n.id,refs:1,parts:s}}}}function f(){var e=document.createElement("style");return e.type="text/css",s.appendChild(e),e}function g(e){var t,n,i=document.querySelector("style["+p+'~="'+e.id+'"]');if(i){if(c)return d;i.parentNode.removeChild(i)}if(m){var r=u++;t=C.bind(null,i=o||(o=f()),r,!1),n=C.bind(null,i,r,!0)}else t=w.bind(null,i=f()),n=function(){i.parentNode.removeChild(i)};return t(e),function(i){i?(i.css!==e.css||i.media!==e.media||i.sourceMap!==e.sourceMap)&&t(e=i):n()}}var b=function(){var e=[];return function(t,n){return e[t]=n,e.filter(Boolean).join("\n")}}();function C(e,t,n,i){var r=n?"":i.css;if(e.styleSheet)e.styleSheet.cssText=b(t,r);else{var a=document.createTextNode(r),s=e.childNodes;s[t]&&e.removeChild(s[t]),s.length?e.insertBefore(a,s[t]):e.appendChild(a)}}function w(e,t){var n=t.css,i=t.media,r=t.sourceMap;if(i&&e.setAttribute("media",i),l.ssrId&&e.setAttribute(p,t.id),r&&(n+="\n/*# sourceURL="+r.sources[0]+" */\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}}]);
//# sourceMappingURL=bbe8d6bcef3840899640.js.map