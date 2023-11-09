(this["webpackJsonpPluginpayone-payment"]=this["webpackJsonpPluginpayone-payment"]||[]).push([[4],{"4Rgz":function(e,t,n){var i=n("dI/e");i.__esModule&&(i=i.default),"string"==typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);(0,n("ydqr").default)("bc9d1220",i,!0,{})},"YxV/":function(e,t,n){"use strict";n.r(t);n("4Rgz");function i(e){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function r(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);t&&(i=i.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,i)}return n}function o(e,t,n){return(t=function(e){var t=function(e,t){if("object"!==i(e)||null===e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var r=n.call(e,t||"default");if("object"!==i(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===t?String:Number)(e)}(e,"string");return"symbol"===i(t)?t:String(t)}(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var a=Shopware.Mixin;t.default={template:'{% block payone_payment_payment_details %}\n    <div class="payone-capture-button">\n        <sw-container v-tooltip="{message: $tc(\'sw-order.payone-payment.capture.tooltips.impossible\'), disabled: buttonEnabled}" :key="buttonEnabled">\n            <sw-button :disabled="!buttonEnabled" @click="openCaptureModal">\n                {{ $tc(\'sw-order.payone-payment.capture.buttonTitle\') }}\n            </sw-button>\n        </sw-container>\n\n        <sw-modal v-if="showCaptureModal" @modal-close="closeCaptureModal" :title="$tc(`sw-order.payone-payment.modal.capture.title`)" class="payone-payment-detail--capture-modal">\n            <payone-order-items\n                    :order="order"\n                    mode="capture"\n                    v-on:select-item="onSelectItem"\n                    v-on:change-quantity="onChangeQuantity">\n            </payone-order-items>\n\n            <div class="payone-payment-detail--capture-modal--content">\n                <sw-container columns="1fr 1fr" gap="0 32px">\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.orderAmount\')" :value="transaction.amount.totalPrice | currency(order.currency.shortName)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.capture.captured\')" :value="capturedAmount | payone_currency(order.currency.shortName, decimalPrecision)"></sw-text-field>\n                    <sw-text-field :disabled="true" :label="$tc(\'sw-order.payone-payment.modal.remainingAmount\')" :value="remainingAmount | payone_currency(order.currency.shortName, decimalPrecision)"></sw-text-field>\n                    <sw-number-field required="required" numberType="float" :digits="order.decimal_precision" :label="$tc(\'sw-order.payone-payment.modal.capture.amount\')"\n                                     v-model="captureAmount"\n                                     :min="0"\n                                     :max="maxCaptureAmount"></sw-number-field>\n                </sw-container>\n            </div>\n\n            <template slot="modal-footer">\n                <sw-button :disabled="isLoading" @click="closeCaptureModal">\n                    {{ $tc(\'sw-order.payone-payment.modal.close\') }}\n                </sw-button>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading || captureAmount <= 0 || !isItemSelected" variant="primary" @click="captureOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.capture.submit\') }}\n                </sw-button-process>\n\n                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading" variant="primary" @click="captureFullOrder">\n                    {{ $tc(\'sw-order.payone-payment.modal.capture.fullSubmit\') }}\n                </sw-button-process>\n            </template>\n        </sw-modal>\n    </div>\n{% endblock %}\n',mixins:[a.getByName("notification")],inject:["PayonePaymentService","repositoryFactory"],props:{order:{type:Object,required:!0},transaction:{type:Object,required:!0}},computed:{decimalPrecision:function(){return this.order&&this.order.currency?this.order.currency.decimalPrecision?this.order.currency.decimalPrecision:this.order.currency.itemRounding?this.order.currency.itemRounding.decimals:void 0:2},totalTransactionAmount:function(){return Math.round(this.transaction.amount.totalPrice*Math.pow(10,this.decimalPrecision),0)},capturedAmount:function(){return this.transaction.extensions&&this.transaction.extensions.payonePaymentOrderTransactionData&&this.transaction.extensions.payonePaymentOrderTransactionData.capturedAmount?this.transaction.extensions.payonePaymentOrderTransactionData.capturedAmount:0},remainingAmount:function(){return this.totalTransactionAmount-this.capturedAmount},maxCaptureAmount:function(){return this.remainingAmount/Math.pow(10,this.decimalPrecision)},buttonEnabled:function(){return!(!this.transaction.extensions||!this.transaction.extensions.payonePaymentOrderTransactionData)&&(this.remainingAmount>0&&this.capturedAmount>0||this.transaction.extensions.payonePaymentOrderTransactionData.allowCapture)},isItemSelected:function(){var e=!1;return this.selection.forEach((function(t){t.selected&&(e=!0)})),e},hasRemainingShippingCosts:function(){var e=this;if(this.order.shippingCosts.totalPrice<=0)return!1;var t=this.order.shippingCosts.totalPrice*Math.pow(10,this.decimalPrecision),n=0;return this.order.lineItems.forEach((function(t){t.customFields&&t.customFields.payone_captured_quantity&&0<t.customFields.payone_captured_quantity&&(n+=t.customFields.payone_captured_quantity*t.unitPrice*Math.pow(10,e.decimalPrecision))})),!(this.capturedAmount-Math.round(n)>=t)}},data:function(){return{isLoading:!1,hasError:!1,showCaptureModal:!1,isCaptureSuccessful:!1,selection:[],captureAmount:0,includeShippingCosts:!1}},methods:{calculateCaptureAmount:function(){var e=0;this.selection.forEach((function(t){t.selected&&(e+=t.unit_price*t.quantity)})),e>this.remainingAmount&&(e=this.remainingAmount),this.captureAmount=e},openCaptureModal:function(){this.showCaptureModal=!0,this.isCaptureSuccessful=!1,this.selection=[]},closeCaptureModal:function(){this.showCaptureModal=!1},onCaptureFinished:function(){this.isCaptureSuccessful=!1},captureOrder:function(){var e=this,t={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.captureAmount,orderLines:[],complete:this.captureAmount===this.remainingAmount,includeShippingCosts:!1};this.isLoading=!0,this.selection.forEach((function(n){e.order.lineItems.forEach((function(e){if(e.id===n.id&&n.selected&&0<n.quantity){var i=function(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?r(Object(n),!0).forEach((function(t){o(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):r(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}({},e),a=i.tax_rate/Math.pow(10,t.decimalPrecision);i.quantity=n.quantity,i.total_amount=i.unit_price*i.quantity,i.total_tax_amount=Math.round(i.total_amount/(100+a)*a),t.orderLines.push(i)}})),"shipping"===n.id&&n.selected&&0<n.quantity&&(t.includeShippingCosts=!0)})),this.remainingAmount<t.amount*Math.pow(10,this.decimalPrecision)&&(t.amount=this.remainingAmount/Math.pow(10,this.decimalPrecision)),this.executeCapture(t)},captureFullOrder:function(){var e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.remainingAmount/Math.pow(10,this.decimalPrecision),orderLines:[],complete:!0,includeShippingCosts:this.hasRemainingShippingCosts};this.isLoading=!0,this.order.lineItems.forEach((function(t){var n=t.quantity;t.customFields&&t.customFields.payone_captured_quantity&&0<t.customFields.payone_captured_quantity&&(n-=t.customFields.payone_captured_quantity),e.orderLines.push({id:t.id,quantity:n,unit_price:t.unitPrice,selected:!1})})),this.executeCapture(e)},executeCapture:function(e){var t=this;this.PayonePaymentService.capturePayment(e).then((function(){t.createNotificationSuccess({title:t.$tc("sw-order.payone-payment.capture.successTitle"),message:t.$tc("sw-order.payone-payment.capture.successMessage")}),t.isCaptureSuccessful=!0})).catch((function(e){t.createNotificationError({title:t.$tc("sw-order.payone-payment.capture.errorTitle"),message:e.message}),t.isCaptureSuccessful=!1})).finally((function(){t.isLoading=!1,t.closeCaptureModal(),t.$nextTick().then((function(){t.$emit("reload")}))}))},onSelectItem:function(e,t){0===this.selection.length&&this._populateSelectionProperty(),this.selection.forEach((function(n){n.id===e&&(n.selected=t)})),this.calculateCaptureAmount()},onChangeQuantity:function(e,t){0===this.selection.length&&this._populateSelectionProperty(),this.selection.forEach((function(n){n.id===e&&(n.quantity=t)})),this.calculateCaptureAmount()},_populateSelectionProperty:function(){var e=this;this.order.lineItems.forEach((function(t){var n=t.quantity;t.customFields&&t.customFields.payone_captured_quantity&&0<t.customFields.payone_captured_quantity&&(n-=t.customFields.payone_captured_quantity),e.selection.push({id:t.id,quantity:n,unit_price:t.unitPrice,selected:!1})})),this.order.shippingCosts.totalPrice>0&&this.selection.push({id:"shipping",quantity:1,unit_price:this.order.shippingCosts.totalPrice,selected:!1})}}}},"dI/e":function(e,t,n){},ydqr:function(e,t,n){"use strict";function i(e,t){for(var n=[],i={},r=0;r<t.length;r++){var o=t[r],a=o[0],s={id:e+":"+r,css:o[1],media:o[2],sourceMap:o[3]};i[a]?i[a].parts.push(s):n.push(i[a]={id:a,parts:[s]})}return n}n.r(t),n.d(t,"default",(function(){return h}));var r="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!r)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var o={},a=r&&(document.head||document.getElementsByTagName("head")[0]),s=null,c=0,u=!1,d=function(){},l=null,p="data-vue-ssr-id",m="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function h(e,t,n,r){u=n,l=r||{};var a=i(e,t);return y(a),function(t){for(var n=[],r=0;r<a.length;r++){var s=a[r];(c=o[s.id]).refs--,n.push(c)}t?y(a=i(e,t)):a=[];for(r=0;r<n.length;r++){var c;if(0===(c=n[r]).refs){for(var u=0;u<c.parts.length;u++)c.parts[u]();delete o[c.id]}}}}function y(e){for(var t=0;t<e.length;t++){var n=e[t],i=o[n.id];if(i){i.refs++;for(var r=0;r<i.parts.length;r++)i.parts[r](n.parts[r]);for(;r<n.parts.length;r++)i.parts.push(g(n.parts[r]));i.parts.length>n.parts.length&&(i.parts.length=n.parts.length)}else{var a=[];for(r=0;r<n.parts.length;r++)a.push(g(n.parts[r]));o[n.id]={id:n.id,refs:1,parts:a}}}}function f(){var e=document.createElement("style");return e.type="text/css",a.appendChild(e),e}function g(e){var t,n,i=document.querySelector("style["+p+'~="'+e.id+'"]');if(i){if(u)return d;i.parentNode.removeChild(i)}if(m){var r=c++;i=s||(s=f()),t=w.bind(null,i,r,!1),n=w.bind(null,i,r,!0)}else i=f(),t=C.bind(null,i),n=function(){i.parentNode.removeChild(i)};return t(e),function(i){if(i){if(i.css===e.css&&i.media===e.media&&i.sourceMap===e.sourceMap)return;t(e=i)}else n()}}var b,v=(b=[],function(e,t){return b[e]=t,b.filter(Boolean).join("\n")});function w(e,t,n,i){var r=n?"":i.css;if(e.styleSheet)e.styleSheet.cssText=v(t,r);else{var o=document.createTextNode(r),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(o,a[t]):e.appendChild(o)}}function C(e,t){var n=t.css,i=t.media,r=t.sourceMap;if(i&&e.setAttribute("media",i),l.ssrId&&e.setAttribute(p,t.id),r&&(n+="\n/*# sourceURL="+r.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(r))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}}]);