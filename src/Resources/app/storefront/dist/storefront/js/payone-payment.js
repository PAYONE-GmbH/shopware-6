(window.webpackJsonp=window.webpackJsonp||[]).push([["payone-payment"],{T08A:function(e,t,n){"use strict";n.r(t);var r=n("FGIj"),o=n("477Q");function i(e){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function a(e){return function(e){if(Array.isArray(e))return l(e)}(e)||function(e){if("undefined"!=typeof Symbol&&Symbol.iterator in Object(e))return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return l(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);"Object"===n&&e.constructor&&(n=e.constructor.name);if("Map"===n||"Set"===n)return Array.from(e);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return l(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function l(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function u(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function c(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function s(e,t){return!t||"object"!==i(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function d(e){return(d=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function y(e,t){return(y=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var f,m,p,h=function(e){function t(){return u(this,t),s(this,d(t).apply(this,arguments))}var n,r,i;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&y(e,t)}(t,e),n=t,(r=[{key:"init",value:function(){var e=this;this.iframe=null,this.iframeFieldCheckerStarted=!1,this.orderFormDisabled=!0;var t=document.getElementById("payone-request"),n=t.getAttribute("data-payone-language"),r=JSON.parse(t.innerHTML);this._createScript((function(){var t=e.getClientConfig(n);e.iframe=new window.Payone.ClientApi.HostedIFrames(t,r);var o=document.getElementById("savedpseudocardpan"),i=document.getElementById("confirmOrderForm");o&&o.addEventListener("change",e._handleChangeSavedCard.bind(e)),i&&i.addEventListener("submit",e._handleOrderSubmit.bind(e))}))}},{key:"getSelectStyle",value:function(){return["width: 100%","padding: .5625rem","color: #8798a9","vertical-align: middle","line-height: 1.5","font-weight: 500","background-color: #fff","border: none","border-radius: 3px"]}},{key:"getFieldStyle",value:function(){return["width: 100%","height: 100%","padding: .5625rem","color: #8798a9","vertical-align: middle","line-height: 1.5","font-weight: 500","background-color: #fff","border: none","border-radius: .1875rem"]}},{key:"getClientConfig",value:function(e){return{fields:{cardpan:{selector:"cardpan",type:"text",style:this.getFieldStyle().join("; ")},cardcvc2:{selector:"cardcvc2",type:"password",size:"4",maxlength:"4",length:{V:3,M:3,A:4,D:3,J:0,O:3,P:3,U:3},style:this.getFieldStyle().join("; ")},cardexpiremonth:{selector:"cardexpiremonth",type:"select",size:"2",maxlength:"2",style:this.getSelectStyle().join("; ")},cardexpireyear:{selector:"cardexpireyear",type:"select",style:this.getSelectStyle().join("; ")}},language:window.Payone.ClientApi.Language[e],defaultStyle:{iframe:{height:"100%",width:"100%"}},autoCardtypeDetection:{supportedCardtypes:t.options.supportedCardtypes,callback:this._cardDetectionCallback}}}},{key:"_cardDetectionCallback",value:function(e){if("-"!==e&&"?"!==e){var t="https://cdn.pay1.de/cc/"+e.toLowerCase()+"/xl/default.png",n=document.getElementById("errorOutput"),r=document.getElementById("card-logo");r.setAttribute("src",t),n.style.display="none",r.style.display="block"}}},{key:"_createScript",value:function(e){var t=document.createElement("script");t.type="text/javascript",t.src="https://secure.pay1.de/client-api/js/v1/payone_hosted.js",t.addEventListener("load",e.bind(this),!1),document.head.appendChild(t)}},{key:"_handleOrderSubmit",value:function(e){var t=this;document.getElementById("errorOutput").style.display="none";var n=document.getElementById("savedpseudocardpan");if(n&&n.value.length>0)return!0;if(!this.iframe.isComplete()){var r=document.getElementById("iframeErrorOutput");return this.iframeFieldCheckerStarted||setInterval((function(){t.iframe.isComplete()?r.style.display="none":r.style.display="block"}),250),this.iframeFieldCheckerStarted=!0,this._handleOrderFormError(e),!1}return this.orderFormDisabled?(window.payoneCreditCardCheckCallback=this._payoneCheckCallback.bind(this),this.iframe.creditCardCheck("payoneCreditCardCheckCallback"),this._handleOrderFormError(e),!1):void 0}},{key:"_handleOrderFormError",value:function(e){var t=document.getElementById("confirmFormSubmit");if(e.preventDefault(),t){var n=new o.a(t);t.disabled=!1,n.remove()}}},{key:"_handleChangeSavedCard",value:function(){var e=document.getElementById("savedpseudocardpan");e.options[e.selectedIndex].value?a(document.getElementsByClassName("credit-card-input")).forEach((function(e){e.classList.add("hide")})):a(document.getElementsByClassName("credit-card-input")).forEach((function(e){e.classList.remove("hide")}))}},{key:"_payoneCheckCallback",value:function(e){if("VALID"===e.status)document.getElementById("pseudocardpan").value=e.pseudocardpan,document.getElementById("truncatedcardpan").value=e.truncatedcardpan,document.getElementById("cardexpiredate").value=e.cardexpiredate,this.orderFormDisabled=!1,document.getElementById("confirmOrderForm").submit();else{var t=document.getElementById("confirmFormSubmit"),n=document.getElementById("errorOutput");t.removeAttribute("disabled"),n.innerHTML=e.errormessage,n.style.display="block"}}}])&&c(n.prototype,r),i&&c(n,i),t}(r.a);p={supportedCardtypes:["#","V","A","M","D","J","O","U","P"]},(m="options")in(f=h)?Object.defineProperty(f,m,{value:p,enumerable:!0,configurable:!0,writable:!0}):f[m]=p;var v=n("p4AR"),b=n("2Jwc"),g=n("3xtq");function _(e){return(_="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function E(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function k(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function O(e,t){return!t||"object"!==_(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function S(e){return(S=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function w(e,t){return(w=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var I=function(e){function t(){return E(this,t),O(this,S(t).apply(this,arguments))}var n,r,i;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&w(e,t)}(t,e),n=t,(r=[{key:"init",value:function(){this.orderFormDisabled=!0,this._client=new v.a,document.getElementById("confirmOrderForm").addEventListener("submit",this._handleOrderSubmit.bind(this))}},{key:"_handleOrderSubmit",value:function(e){document.getElementById("errorOutput").style.display="none",this.orderFormDisabled&&(this._handleOrderFormError(e),this._getModal(e))}},{key:"_handleOrderFormError",value:function(e){var t=document.getElementById("confirmFormSubmit");if(e.preventDefault(),t){var n=new o.a(t);t.disabled=!1,n.remove()}}},{key:"_getModal",value:function(e){var t=this;e.preventDefault(),g.a.create();var n=this._getRequestData();this._client.abort(),this._client.post(this._getManageMandateUrl(),JSON.stringify(n),(function(e){return t._openModal(e)}))}},{key:"_submitForm",value:function(){this.orderFormDisabled=!1,document.getElementById("confirmOrderForm").submit()}},{key:"_openModal",value:function(e){if((e=JSON.parse(e)).error){var t=document.getElementById("errorOutput");return t.innerHTML=e.error,t.style.display="block",void g.a.remove()}if("active"!==e.mandate.Status){var n=new b.a(e.modal_content);n.open(this._onOpen.bind(this,n))}else this._submitForm()}},{key:"_onOpen",value:function(e){e.getModal().classList.add("payone-debit-mandate-modal"),window.PluginManager.initializePlugins(),this._registerEvents(),g.a.remove()}},{key:"_getRequestData",value:function(){var e=document.getElementById("payoneCsrfTokenDebitManageMandate"),t=document.getElementById("iban"),n=document.getElementById("bic");return{_csrf_token:e.value,iban:t.value,bic:n.value}}},{key:"_getManageMandateUrl",value:function(){return document.getElementById("payone-configuration").getAttribute("data-manage-mandate-url")}},{key:"_registerEvents",value:function(){document.getElementById("mandateSubmit").addEventListener("click",this._onMandateSubmit.bind(this))}},{key:"_onMandateSubmit",value:function(){document.getElementById("accept-mandate").checked&&this._submitForm()}}])&&k(n.prototype,r),i&&k(n,i),t}(r.a);function B(e){return(B="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function C(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function P(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function F(e,t){return!t||"object"!==B(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function j(e){return(j=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function L(e,t){return(L=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}!function(e,t,n){t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n}(I,"options",{editorModalClass:"payone-debit-modal"});var D=function(e){function t(){return C(this,t),F(this,j(t).apply(this,arguments))}var n,r,o;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&L(e,t)}(t,e),n=t,(r=[{key:"init",value:function(){this.orderFormDisabled=!0,this._client=new v.a,this._registerEventListeners()}},{key:"_registerEventListeners",value:function(){var e=document.getElementById("confirmOrderForm");e&&e.addEventListener("submit",this._handleOrderSubmit.bind(this))}},{key:"_handleOrderSubmit",value:function(e){this._hideErrorBox(),this.orderFormDisabled&&(this._validateField(e,"payolutionConsent"),this._validateInput(e,"payolutionBirthday"),e.defaultPrevented||(this._validatePaymentAcceptance(),e.preventDefault()))}},{key:"_validateField",value:function(e,t){var n=document.getElementById(t);n.checked?n.classList.remove("is-invalid"):(n.scrollIntoView({block:"start",behavior:"smooth"}),n.classList.add("is-invalid"),e.preventDefault())}},{key:"_validateInput",value:function(e,t){var n=document.getElementById(t);n.value?n.classList.remove("is-invalid"):(n.scrollIntoView({block:"start",behavior:"smooth"}),n.classList.add("is-invalid"),e.preventDefault())}},{key:"_validatePaymentAcceptance",value:function(){var e=this,t=JSON.stringify(this._getRequestData());g.a.create(),this._client.abort(),this._client.post(this._getValidateUrl(),t,(function(t){return e._handleValidateResponse(t)}))}},{key:"_handleValidateResponse",value:function(e){if(e=JSON.parse(e),g.a.remove(),"OK"!==e.status)this._showErrorBox();else{var t=document.getElementById("payoneWorkOrder");t&&(t.value=e.workorderid),this._submitForm()}}},{key:"_getValidateUrl",value:function(){return document.getElementById("payone-configuration").getAttribute("data-validate-url")}},{key:"_showErrorBox",value:function(){var e=document.getElementById("payolutionErrorContainer");e&&(e.hidden=!1)}},{key:"_hideErrorBox",value:function(){var e=document.getElementById("payolutionErrorContainer");e&&(e.hidden=!0)}},{key:"_submitForm",value:function(){this.orderFormDisabled=!1,document.getElementById("confirmOrderForm").submit()}},{key:"_getRequestData",value:function(){var e=document.getElementById("payoneCsrfTokenPayolutionInvoiceValidation"),t=document.getElementById("payolutionBirthday");return{_csrf_token:e.value,payolutionBirthday:t.value}}}])&&P(n.prototype,r),o&&P(n,o),t}(r.a);function A(e){return(A="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function M(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function x(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function T(e,t){return!t||"object"!==A(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function U(e){return(U=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function J(e,t){return(J=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var R=function(e){function t(){return M(this,t),T(this,U(t).apply(this,arguments))}var n,r,i;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&J(e,t)}(t,e),n=t,(r=[{key:"init",value:function(){this.orderFormDisabled=!0,this._client=new v.a,this._disableSubmitButton(),this._registerEventListeners()}},{key:"_registerEventListeners",value:function(){var e=document.getElementById("confirmOrderForm"),t=document.getElementById("checkInstallmentButton");e&&e.addEventListener("submit",this._handleOrderSubmit.bind(this)),t&&t.addEventListener("click",this._handleCalculationButtonClick.bind(this))}},{key:"_handleCalculationButtonClick",value:function(e){var t=this;if(this._hideErrorBox(),this._validateField(e,"payolutionConsent"),this._validateInput(e,"payolutionBirthday"),!e.defaultPrevented){g.a.create();var n=JSON.stringify(this._getRequestData());this._client.abort(),this._client.post(this._getCalculationUrl(),n,(function(e){return t._handleCalculationCallback(e)}))}}},{key:"_handleCalculationCallback",value:function(e){if(e=JSON.parse(e),g.a.remove(),"OK"===e.status){var t=document.getElementById("payoneWorkOrder"),n=document.getElementById("payoneCartHash");t.value=e.workorderid,n.value=e.carthash,this._displayInstallmentSelection(e),this._displayCalculationOverview(e),this._registerSelectionEventListeners(),this._enableSecondStep(),this._activateSubmitButton(),this._hideCheckInstallmentButton()}else this._showErrorBox()}},{key:"_hideCheckInstallmentButton",value:function(){var e=document.getElementById("checkInstallmentButton");e&&e.classList.add("hidden")}},{key:"_registerSelectionEventListeners",value:function(){document.getElementById("payolutionInstallmentDuration").addEventListener("change",(function(e){var t=e.target.value;document.querySelectorAll(".installmentDetail").forEach((function(e){e.dataset.duration===t?e.hidden=!1:e.hidden="hidden"}))}))}},{key:"_showErrorBox",value:function(){var e=document.getElementById("payolutionErrorContainer");e&&(e.hidden=!1)}},{key:"_hideErrorBox",value:function(){var e=document.getElementById("payolutionErrorContainer");e&&(e.hidden=!0)}},{key:"_enableSecondStep",value:function(){document.querySelectorAll(".payolution-installment .hidden").forEach((function(e){e.classList.remove("hidden")}))}},{key:"_displayInstallmentSelection",value:function(e){var t=document.getElementById("installmentSelection");t&&(t.innerHTML=e.installmentSelection)}},{key:"_displayCalculationOverview",value:function(e){var t=document.getElementById("calculationOverview");t&&(t.innerHTML=e.calculationOverview)}},{key:"_handleOrderSubmit",value:function(e){this._validateField(e,"payolutionConsent"),this._validateInput(e,"payolutionBirthday"),this._validateInput(e,"payolutionAccountOwner"),this._validateInput(e,"payolutionIban"),this._validateInput(e,"payolutionBic"),this._validateInput(e,"payolutionInstallmentDuration")}},{key:"_disableSubmitButton",value:function(){this.orderFormDisabled=!0;var e=document.getElementById("confirmFormSubmit");e&&e.setAttribute("disabled","disabled")}},{key:"_activateSubmitButton",value:function(){this.orderFormDisabled=!1;var e=document.getElementById("confirmFormSubmit");e&&e.removeAttribute("disabled")}},{key:"_getCalculationUrl",value:function(){return document.getElementById("payone-configuration").getAttribute("data-calculation-url")}},{key:"_validateField",value:function(e,t){var n=document.getElementById(t);n.checked?n.classList.remove("is-invalid"):(n.scrollIntoView({block:"start",behavior:"smooth"}),n.classList.add("is-invalid"),this._handleOrderFormError(e))}},{key:"_validateInput",value:function(e,t){var n=document.getElementById(t);n.value?n.classList.remove("is-invalid"):(n.scrollIntoView({block:"start",behavior:"smooth"}),n.classList.add("is-invalid"),this._handleOrderFormError(e))}},{key:"_handleOrderFormError",value:function(e){var t=document.getElementById("confirmFormSubmit");if(e.preventDefault(),t){var n=new o.a(t);t.disabled=!1,n.remove()}}},{key:"_getRequestData",value:function(){var e=document.getElementById("payoneCsrfTokenPayolutionInstallmentCalculation"),t=document.getElementById("payolutionBirthday"),n=document.getElementById("payoneWorkOrder"),r=document.getElementById("payoneCartHash");return{_csrf_token:e.value,payolutionBirthday:t.value,workorder:n.value,carthash:r.value}}}])&&x(n.prototype,r),i&&x(n,i),t}(r.a),V=n("gHbT");function N(e){return(N="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function q(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function H(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}function z(e,t){return!t||"object"!==N(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function W(e){return(W=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function K(e,t){return(K=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}function G(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var Q=function(e){function t(){return q(this,t),z(this,W(t).apply(this,arguments))}var n,r,o;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&K(e,t)}(t,e),n=t,(r=[{key:"init",value:function(){this.client=new v.a,this.validateMerchantUrl=this.el.dataset.validateMerchantUrl,this.processPaymentUrl=this.el.dataset.processPaymentUrl,this._registerEventHandler()}},{key:"createSession",value:function(){this.options.total.amount=.01,this.session=new ApplePaySession(3,this.options),this.session.addEventListener("validatemerchant",this.validateMerchant.bind(this)),this.session.addEventListener("paymentauthorized",this.authorizePayment.bind(this))}},{key:"performPayment",value:function(){this.session.begin()}},{key:"validateMerchant",value:function(e){var t=this;console.log("validate");var n=e.validationURL;this.client.abort(),this.client.post(this.validateMerchantUrl,JSON.stringify({validationUrl:n}),(function(e){var n=null;try{n=JSON.parse(e)}catch(e){return void t.handleErrorOnPayment()}n&&n.merchantSessionIdentifier&&n.signature?t.session.completeMerchantValidation(n):t.handleErrorOnPayment()}))}},{key:"handleErrorOnPayment",value:function(){var e=V.a.querySelector(document,"#payone-apple-pay-error");e.style.display="block",e.scrollIntoView({block:"start"})}},{key:"authorizePayment",value:function(e){this.client.abort(),this.client.post(this.processPaymentUrl,JSON.stringify({token:e.payment.token}),(function(e){}))}},{key:"_handleApplePayButtonClick",value:function(){V.a.querySelector(document,"#confirmOrderForm").reportValidity()&&(this.createSession(),this.performPayment())}},{key:"_registerEventHandler",value:function(){this.el.addEventListener("click",this._handleApplePayButtonClick.bind(this))}}])&&H(n.prototype,r),o&&H(n,o),t}(r.a);G(Q,"options",{countryCode:"",currencyCode:"",supportedNetworks:["visa","masterCard"],merchantCapabilities:["supports3DS"],total:{label:"",type:"final",amount:"0.01"}}),G(Q,"session",void 0),G(Q,"client",void 0),G(Q,"validateMerchantUrl",void 0),G(Q,"processPaymentUrl",void 0);var $=window.PluginManager;$.register("PayonePaymentCreditCard",h,"[data-is-payone-credit-card]"),$.register("PayonePaymentDebitCard",I,"[data-is-payone-debit-card]"),$.register("PayonePaymentPayolutionInvoicing",D,"[data-is-payone-payolution-invoicing]"),$.register("PayonePaymentPayolutionInstallment",R,"[data-is-payone-payolution-installment]"),$.register("PayonePaymentApplePay",Q,"[data-payone-payment-apple-pay-options]")}},[["T08A","runtime","vendor-node","vendor-shared"]]]);
