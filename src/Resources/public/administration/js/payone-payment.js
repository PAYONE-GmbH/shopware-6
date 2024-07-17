(()=>{var Ye=Object.defineProperty;var i=(e,t)=>()=>(e&&(t=e(e=0)),t);var r=(e,t)=>{for(var a in t)Ye(e,a,{get:t[a],enumerable:!0})};var v,A=i(()=>{v=`{% block payone_payment_plugin_icon %}
    <img class="payone-payment-plugin-icon" :src="assetFilter('payonepayment/plugin.png')">
{% endblock %}
`});var S=i(()=>{});var T={};r(T,{default:()=>Ke});var Ke,C=i(()=>{A();S();Ke={template:v,computed:{assetFilter(){return Shopware.Filter.getByName("asset")}}}});var x=i(()=>{});var E,I=i(()=>{E=`{% block payone_ratepay_profile_configurations %}
    <div class="payone-ratepay-profile-configuration">
        <template v-if="profileConfigurations.length > 0">
            <h3>{{ $tc('payone-payment.general.headlines.ratepayProfileConfigurations') }}</h3>

            <div class="payone-ratepay-profile-configuration--items">
                <div v-for="profileConfiguration in profileConfigurations">
                    <p class="payone-ratepay-profile-configuration--headline">{{ $tc('payone-payment.general.label.shopId') }}: {{ profileConfiguration.shopId }}</p>

                    {% block payone_ratepay_configuration_grid %}
                        <sw-description-list grid="1fr 1fr">
                            <dt>{{ $tc('payone-payment.general.label.currency') }}</dt>
                            <dd>{{ profileConfiguration.shopCurrency }}</dd>

                            <dt>{{ $tc('payone-payment.general.label.invoiceCountry') }}</dt>
                            <dd>{{ profileConfiguration.invoiceCountry }}</dd>

                            <dt>{{ $tc('payone-payment.general.label.shippingCountry') }}</dt>
                            <dd>{{ profileConfiguration.shippingCountry }}</dd>

                            <dt>{{ $tc('payone-payment.general.label.minBasket') }}</dt>
                            <dd>{{ profileConfiguration.minBasket }}</dd>

                            <dt>{{ $tc('payone-payment.general.label.maxBasket') }}</dt>
                            <dd>{{ profileConfiguration.maxBasket }}</dd>
                        </sw-description-list>
                    {% endblock %}
                </div>
            </div>
        </template>

        <sw-alert variant="info" appearance="default" :showIcon="true" :closable="false">
            <span v-html="$tc('payone-payment.general.label.reloadConfigInfo')"></span>
        </sw-alert>
    </div>
{% endblock %}
`});var $={};r($,{default:()=>Ve});var Ve,D=i(()=>{x();I();Ve={template:E,inject:["PayonePaymentSettingsService"],props:{value:{type:Object,required:!1,default(){return{}}},name:{type:String,required:!0}},data(){return{isLoading:!1,configuration:this.value}},created(){this.createdComponent()},destroyed(){this.destroyedComponent()},computed:{profileConfigurations(){let e=this.name,t=[];for(let a in this.configuration){let n="",o="";switch(e){case"PayonePayment.settings.ratepayDebitProfileConfigurations":n=this.configuration[a]["tx-limit-elv-min"],o=this.configuration[a]["tx-limit-elv-max"];break;case"PayonePayment.settings.ratepayInstallmentProfileConfigurations":n=this.configuration[a]["tx-limit-installment-min"],o=this.configuration[a]["tx-limit-installment-max"];break;case"PayonePayment.settings.ratepayInvoicingProfileConfigurations":n=this.configuration[a]["tx-limit-invoice-min"],o=this.configuration[a]["tx-limit-invoice-max"];break;default:return}let s={shopId:a,shopCurrency:this.configuration[a].currency,invoiceCountry:this.configuration[a]["country-code-billing"],shippingCountry:this.configuration[a]["country-code-delivery"],minBasket:n,maxBasket:o};t.push(s)}return t}},methods:{createdComponent(){this.$root.$on("payone-ratepay-profiles-update-result",this.onProfilesUpdateResult)},destroyedComponent(){this.$root.$off("payone-ratepay-profiles-update-result")},onProfilesUpdateResult(e){e.updates[this.name]&&(this.configuration=e.updates[this.name])}}}});var L=i(()=>{});var O,N=i(()=>{O=`{% block payone_ratepay_profiles %}
    <div class="payone-ratepay-profiles">
        {% block payone_ratepay_shop_ids %}
            <h3>{{ $tc('payone-payment.general.headlines.ratepayProfiles') }}</h3>
            <sw-container slot="grid" type="row" class="">

                {% block payone_ratepay_shop_ids_actions %}
                    <sw-container class="sw-card__toolbar"
                                  columns="1fr">

                        {% block payone_ratepay_shop_ids_create_actions %}
                            <div align="right">
                                <sw-button class=""
                                           size="small"
                                           @click="createNewLineItem">
                                    {{ $tc('payone-payment.general.actions.addShop') }}
                                </sw-button>
                            </div>
                        {% endblock %}
                    </sw-container>
                {% endblock %}

                {% block payone_ratepay_shop_ids_grid %}
                    <sw-data-grid v-if="value"
                                  ref="shopIdsDataGrid"
                                  :dataSource="profiles"
                                  :columns="getLineItemColumns"
                                  :fullPage="false"
                                  :showSettings="false"
                                  :showSelection="false"
                                  :showActions="true"
                                  :allowColumnEdit="false"
                                  :allowInlineEdit="true"
                                  :compactMode="true"
                                  identifier="sw-order-line-item-grid"
                                  class="sw-order-line-items-grid__data-grid"
                                  @inline-edit-save="onInlineEditSave"
                                  @inline-edit-cancel="onInlineEditCancel">
                        {% block payone_ratepay_shop_ids_grid_columns %}
                            {% block payone_ratepay_shop_ids_grid_column_status %}
                                <template #column-error="{ item, column }">
                                    <sw-icon v-tooltip="{
                                                message: item.error,
                                                width: 150,
                                                position: 'bottom'
                                             }"
                                             v-if="item.error"
                                             name="regular-exclamation-triangle"
                                             color="red">
                                    </sw-icon>
                                </template>
                            {% endblock %}

                            {% block payone_ratepay_shop_ids_bulk_actions %}
                                <template #actions="{ item }">
                                    {% block sw_settings_units_content_grid_column_menu_delete %}
                                        <sw-context-menu-item @click="onDeleteSelectedItem(item)" variant="danger">
                                            {{ $tc('global.default.delete') }}
                                        </sw-context-menu-item>
                                    {% endblock %}
                                </template>
                            {% endblock %}
                        {% endblock %}
                    </sw-data-grid>
                {% endblock %}

                {% block payone_ratepay_shop_ids_error %}
                    <sw-alert v-if="showDuplicateAlert" variant="error">
                        {{ $tc('payone-payment.general.errors.existingShopId') }}
                    </sw-alert>

                    <sw-alert v-if="showEmptyAlert" variant="error">
                        {{ $tc('payone-payment.general.errors.emptyInputs') }}
                    </sw-alert>
                {% endblock %}
        </sw-container>
        {% endblock %}
    </div>
{% endblock %}
`});var R={};r(R,{default:()=>Qe});var He,Qe,F=i(()=>{L();N();({Utils:He}=Shopware),Qe={template:O,props:{value:{type:Array,required:!1,default(){return[]}},name:{type:String,required:!0}},data(){return{selectedItems:{},newItem:null,showDuplicateAlert:!1,showEmptyAlert:!1,profiles:this.value}},computed:{getLineItemColumns(){return[{property:"shopId",dataIndex:"shopId",label:this.$tc("payone-payment.general.label.shopId"),allowResize:!1,inlineEdit:"string",width:"200px",primary:!0},{property:"currency",dataIndex:"currency",label:this.$tc("payone-payment.general.label.currency"),allowResize:!1,inlineEdit:"string",width:"200px",primary:!0},{property:"error",label:this.$tc("payone-payment.general.label.error"),allowResize:!1,width:"100px",primary:!0}]}},watch:{profiles(e){this.$emit("update:value",e),this.$emit("input",e),this.$emit("change",e)}},created(){this.createdComponent()},destroyed(){this.destroyedComponent()},methods:{createdComponent(){this.$root.$on("payone-ratepay-profiles-update-result",this.onProfilesUpdateResult)},destroyedComponent(){this.$root.$off("payone-ratepay-profiles-update-result")},onProfilesUpdateResult(e){if(e.updates[this.name]&&(this.profiles=e.updates[this.name]),e.errors[this.name])for(let t of e.errors[this.name])this.profiles.push(t)},onInlineEditCancel(e){e.shopId===""&&e.currency===""&&this.profiles.forEach(function(t,a,n){t.id===e.id&&n.splice(a,1)}),this.$emit("item-cancel")},onInlineEditSave(e){if(e.shopId!==""&&e.currency!==""){this.showEmptyAlert=!1;let t=!1;this.profiles.forEach(function(a){a.id!==e.id&&a.shopId===e.shopId&&(t=!0)}),t?(this.showDuplicateAlert=!0,this.$nextTick(()=>{this.$refs.shopIdsDataGrid.currentInlineEditId=e.id,this.$refs.shopIdsDataGrid.enableInlineEdit()})):this.showDuplicateAlert=!1}else this.showEmptyAlert=!0,this.$nextTick(()=>{this.$refs.shopIdsDataGrid.currentInlineEditId=e.id,this.$refs.shopIdsDataGrid.enableInlineEdit()});this.$emit("update-list",this.profiles)},createNewLineItem(){let e=!1;if(e=this.profiles.length===0,e){this.createLine();return}this.profiles[this.profiles.length-1].shopId!==""&&this.createLine()},createLine(){let e=He.createId();this.profiles.push({id:e,shopId:"",currency:""}),this.$nextTick(()=>{this.$refs.shopIdsDataGrid.currentInlineEditId=e,this.$refs.shopIdsDataGrid.enableInlineEdit()})},onDeleteSelectedItem(e){this.profiles=this.profiles.filter(t=>t.shopId!==e.shopId),this.$emit("deleted",this.profiles)}}}});var q,M=i(()=>{q=`{% block payone_payment %}
<sw-page class="payone-payment">
    {% block payone_payment_header %}
    <template #smart-bar-header>
        <h2>
            {{ $tc('sw-settings.index.title') }}
            <sw-icon name="regular-chevron-right-xs" small></sw-icon>
            {{ $tc('payone-payment.title') }}
        </h2>
    </template>
    {% endblock %}

    {% block payone_payment_actions %}
    <template #smart-bar-actions>
        {% block payone_payment_settings_actions_feedback %}
        <sw-button
                @click="isSupportModalOpen = true"
                :disabled="false"
                variant="ghost"
                :square="false"
                :block="false"
                :isLoading="false">
            {{ $tc('payone-payment.supportModal.menuButton') }}
        </sw-button>
        {% endblock %}

        {% block payone_payment_settings_actions_notification_forward_target %}
            <sw-button
                :routerLink="{ name: 'payone.notification.target.list' }"
                :disabled="false"
                variant="ghost"
                :square="false"
                :block="false"
                :isLoading="false">
                {{ $tc('payonePayment.notificationTarget.module.buttonTitle') }}
            </sw-button>
        {% endblock %}

        {% block payone_payment_settings_actions_test %}
        <sw-button-process @click="onTest"
                           :isLoading="isTesting"
                           :processSuccess="isTestSuccessful"
                           :disabled="isLoading">
            {{ $tc('payone-payment.settingsForm.test') }}
        </sw-button-process>
        {% endblock %}

        {% block payone_payment_settings_actions_save %}
        <sw-button-process
                class="sw-settings-login-registration__save-action"
                :isLoading="isLoading"
                :processSuccess="isSaveSuccessful"
                :disabled="isLoading || isTesting"
                variant="primary"
                @process-finish="saveFinish"
                @click="onSave">
            {{ $tc('payone-payment.settingsForm.save') }}
        </sw-button-process>
        {% endblock %}
    </template>
    {% endblock %}

    {% block payone_payment_settings_content %}
    <template #content>
        <sw-modal
                v-if="isSupportModalOpen"
                @modal-close="isSupportModalOpen = false"
                :title="$tc('payone-payment.supportModal.title')"
                class="payone-feedback sw-modal--large">
            <sw-container columns="1fr 1fr 1fr 1fr">
                <div class="payone-feedback__col">
                    <div class="payone-feedback__text">
                        <div class="payone-feedback__icon">
                            <sw-icon name="regular-file-text" large="true"></sw-icon>
                        </div>
                        <p class="payone-feedback__desc">
                            {{ $tc('payone-payment.supportModal.documentation.description') }}
                        </p>
                    </div>
                    <sw-button
                            :disabled="false"
                            variant="primary"
                            :square="false"
                            :block="false"
                            :isLoading="false"
                            link="https://docs.payone.com/display/public/INT/Shopware+6+Plugin">
                        {{ $tc('payone-payment.supportModal.documentation.button') }}
                    </sw-button>
                </div>
                <div class="payone-feedback__col">
                    <div class="payone-feedback__text">
                        <div class="payone-feedback__icon">
                            <sw-icon name="regular-headset" large="true"></sw-icon>
                        </div>
                        <p class="payone-feedback__desc">
                            {{ $tc('payone-payment.supportModal.support.description') }}
                        </p>
                    </div>
                    <sw-button
                            :disabled="false"
                            variant="primary"
                            :square="false"
                            :block="false"
                            :isLoading="false"
                            link="mailto:tech.support@payone.com">
                        {{ $tc('payone-payment.supportModal.support.button') }}
                    </sw-button>
                </div>
                <div class="payone-feedback__col">
                    <div class="payone-feedback__text">
                        <div class="payone-feedback__icon">
                            <sw-icon name="regular-code" large="true"></sw-icon>
                        </div>
                        <p class="payone-feedback__desc">
                            {{ $tc('payone-payment.supportModal.repository.description') }}
                        </p>
                    </div>
                    <sw-button
                            :disabled="false"
                            variant="primary"
                            :square="false"
                            :block="false"
                            :isLoading="false"
                            link="https://github.com/PAYONE-GmbH/shopware-6">
                        {{ $tc('payone-payment.supportModal.repository.button') }}
                    </sw-button>
                </div>
                <div class="payone-feedback__col">
                    <div class="payone-feedback__text">
                        <div class="payone-feedback__icon">
                            <sw-icon name="regular-fingerprint" large="true"></sw-icon>
                        </div>
                        <p class="payone-feedback__desc">
                            {{ $tc('payone-payment.supportModal.testdata.description') }}
                        </p>
                    </div>
                    <sw-button
                            :disabled="false"
                            variant="primary"
                            :square="false"
                            :block="false"
                            :isLoading="false"
                            link="https://www.payone.com/DE-de/kampagne/ecom-testaccount">
                        {{ $tc('payone-payment.supportModal.testdata.button') }}
                    </sw-button>
                </div>
            </sw-container>
        </sw-modal>

        <sw-card-view>
            <payone-payment-settings
                    class="payone-config__wrapper"
                    ref="systemConfig"
                    sales-channel-switchable
                    inherit
                    domain="PayonePayment.settings">

                <template #beforeElements="{card, config}">
                    <div v-if="card.setShowFields !== undefined" class="payone-config__collapsible-container" v-bind:class="{'collapsed': !card.showFields}" >
                        <a class="payone-config__collapsible-handle" @click="card.setShowFields(!card.showFields)">
                            <sw-icon small v-if="!card.showFields" name="regular-chevron-down-xxs" class="payone-config__collapsible-handle-open"></sw-icon>
                            <sw-icon small v-if="card.showFields" name="regular-chevron-up-xxs" class="payone-config__collapsible-handle-close"></sw-icon>
                        </a>
                    </div>

                    <sw-alert v-if="card.showFields && card.name === 'payment_apple_pay' && !isApplePayCertConfigured"
                              variant="info" appearance="default" :showIcon="true" :closable="false">
                        <span v-html="$tc('payone-payment.applePay.cert.notification')"></span>
                    </sw-alert>
                </template>
            </payone-payment-settings>
        </sw-card-view>
    </template>
    {% endblock %}
</sw-page>
{% endblock %}
`});var B=i(()=>{});var W={};r(W,{default:()=>Xe});var Y,Xe,z=i(()=>{M();B();({Mixin:Y}=Shopware),Xe={template:q,mixins:[Y.getByName("notification"),Y.getByName("sw-inline-snippet")],inject:["PayonePaymentSettingsService"],data(){return{isLoading:!1,isTesting:!1,isSaveSuccessful:!1,isTestSuccessful:!1,isApplePayCertConfigured:!0,isSupportModalOpen:!1,stateMachineTransitionActions:[],displayStatusMapping:{}}},created(){this.createdComponent()},metaInfo(){return{title:this.$createTitle()}},methods:{createdComponent(){this.PayonePaymentSettingsService.hasApplePayCert().then(e=>{this.isApplePayCertConfigured=e})},saveFinish(){this.isSaveSuccessful=!1},testFinish(){this.isTestSuccessful=!1},getConfigValue(e){let t=this.$refs.systemConfig.actualConfigData,a=t.null,n=this.$refs.systemConfig.currentSalesChannelId;return n===null?t.null[`PayonePayment.settings.${e}`]:t[n][`PayonePayment.settings.${e}`]||a[`PayonePayment.settings.${e}`]},getPaymentConfigValue(e,t){let a=e.charAt(0).toUpperCase()+e.slice(1);return this.getConfigValue(t+a)||this.getConfigValue(e)},onSave(){this.isSaveSuccessful=!1,this.isLoading=!0,this.$refs.systemConfig.saveAll().then(e=>{this.handleRatepayProfileUpdates(e),this.isSaveSuccessful=!0}).finally(()=>{this.isLoading=!1})},onTest(){this.isTesting=!0,this.isTestSuccessful=!1;let e={};this.$refs.systemConfig.config.forEach(t=>{let a=t.name.match(/^payment_(.+)$/),n=a?a[1]:null;n&&(e[n]={merchantId:this.getPaymentConfigValue("merchantId",n),accountId:this.getPaymentConfigValue("accountId",n),portalId:this.getPaymentConfigValue("portalId",n),portalKey:this.getPaymentConfigValue("portalKey",n)})}),this.PayonePaymentSettingsService.validateApiCredentials(e).then(t=>{let a=t.testCount,n=t.credentialsValid,o=t.errors;if(n)this.createNotificationSuccess({title:this.$tc("payone-payment.settingsForm.titleSuccess"),message:a>0?this.$tc("payone-payment.settingsForm.messageTestSuccess"):this.$tc("payone-payment.settingsForm.messageTestNoTestedPayments")}),this.isTestSuccessful=!0;else for(let s in o)if(o.hasOwnProperty(s)){this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:this.$tc("payone-payment.settingsForm.messageTestError."+s)});let l=o[s];typeof l=="string"&&this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:l})}}).finally(t=>{this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:this.$tc("payone-payment.settingsForm.messageTestError.general")}),this.isTesting=!1})},handleRatepayProfileUpdates(e){let t=this.$refs.systemConfig.currentSalesChannelId;if(e.payoneRatepayProfilesUpdateResult&&e.payoneRatepayProfilesUpdateResult[t]){let a=e.payoneRatepayProfilesUpdateResult[t];this.$root.$emit("payone-ratepay-profiles-update-result",a),Array.isArray(a.errors)||this.createNotificationError({title:this.$tc("payone-payment.settingsForm.titleError"),message:this.$tc("payone-payment.settingsForm.messageSaveError.ratepayProfilesUpdateFailed")})}}}}});var U,K=i(()=>{U=`{% block payone_notification_target_detail %}
    <sw-page class="payone-notification-target-detail">

        {% block payone_notification_target_detail_header %}
            <template #smart-bar-header>
                <h2>{{ $tc('payonePayment.notificationTarget.detail.headline') }}</h2>
            </template>
        {% endblock %}

        {% block payone_notification_target_detail_actions %}
            <template #smart-bar-actions>

                {% block payone_notification_target_detail_actions_abort %}
                    <sw-button :disabled="notificationTargetIsLoading" @click="onCancel">
                        {{ $tc('payonePayment.notificationTarget.detail.label.buttonCancel') }}
                    </sw-button>
                {% endblock %}

                {% block payone_notification_target_detail_actions_save %}
                    <sw-button-process
                        class="payone-notification-target-detail__save-action"
                        :isLoading="isLoading"
                        v-model="isSaveSuccessful"
                        :disabled="isLoading"
                        variant="primary"
                        :process-success="processSuccess"
                        @click.prevent="onSave">
                        {{ $tc('payonePayment.notificationTarget.detail.label.buttonSave') }}
                    </sw-button-process>
                {% endblock %}

            </template>
        {% endblock %}

        <template #content>
            {% block payone_notification_target_detail_content %}
                <sw-card-view>

                    {% block payone_notification_target_detail_base_basic_info_card %}
                        <sw-card position-identifier="payone-notification-target-detail-content"
                                 :title="$tc('payonePayment.notificationTarget.detail.headline')"
                                 :isLoading="notificationTargetIsLoading">
                            <template v-if="!notificationTargetIsLoading">
                                <sw-container class="payone-notification-target-detail__container"
                                              columns="repeat(auto-fit, minmax(250px, 1fr))"
                                              gap="0 30px">
                                    <div class="payone-notification-target-detail__base-info-wrapper">

                                        {% block payone_notification_target_detail_base_info_field_url %}
                                            <sw-text-field
                                                      :label="$tc('payonePayment.notificationTarget.detail.label.url')"
                                                      :placeholder="$tc('payonePayment.notificationTarget.detail.placeholder.url')"
                                                      name="url"
                                                      validation="required"
                                                      required
                                                      v-model:value="notificationTarget.url">
                                            </sw-text-field>
                                        {% endblock %}

                                        {% block payone_notification_target_detail_base_info_field_is_basic_auth %}
                                            <sw-checkbox-field :label="$tc('payonePayment.notificationTarget.detail.label.isBasicAuth')"
                                                      name="isBasicAuth"
                                                      v-model:value="notificationTarget.isBasicAuth">
                                            </sw-checkbox-field>
                                        {% endblock %}

                                        {% block payone_notification_target_detail_base_info_field_is_basic_auth_fields %}
                                            <sw-text-field v-if="notificationTarget.isBasicAuth"
                                                      :label="$tc('payonePayment.notificationTarget.detail.label.username')"
                                                      :placeholder="$tc('payonePayment.notificationTarget.detail.placeholder.username')"
                                                      name="username"
                                                      required
                                                      v-model:value="notificationTarget.username">
                                            </sw-text-field>

                                            <sw-password-field v-if="notificationTarget.isBasicAuth"
                                                      :label="$tc('payonePayment.notificationTarget.detail.label.password')"
                                                      :placeholder="$tc('payonePayment.notificationTarget.detail.placeholder.password')"
                                                      name="password"
                                                      required
                                                      v-model:value="notificationTarget.password">
                                            </sw-password-field>
                                        {% endblock %}

                                        {% block payone_notification_target_detail_base_info_field_txactions %}
                                            <sw-multi-select
                                                :label="$tc('payonePayment.notificationTarget.detail.label.txactions')"
                                                :options="[
                                                    { value: 'appointed', label: 'appointed' },
                                                    { value: 'capture', label: 'capture' },
                                                    { value: 'paid', label: 'paid' },
                                                    { value: 'underpaid', label: 'underpaid' },
                                                    { value: 'cancelation', label: 'cancelation' },
                                                    { value: 'refund', label: 'refund' },
                                                    { value: 'debit', label: 'debit' },
                                                    { value: 'transfer', label: 'transfer' },
                                                    { value: 'reminder', label: 'reminder' },
                                                    { value: 'vauthorization', label: 'vauthorization' },
                                                    { value: 'vsettlement', label: 'vsettlement' },
                                                    { value: 'invoice', label: 'invoice' },
                                                    { value: 'failed', label: 'failed' }
                                                ]"
                                                v-model:value="notificationTarget.txactions">
                                            </sw-multi-select>
                                        {% endblock %}

                                    </div>
                                </sw-container>
                            </template>
                        </sw-card>
                    {% endblock %}
                </sw-card-view>
            {% endblock %}
        </template>

    </sw-page>
{% endblock %}
`});var V={};r(V,{default:()=>it});var nt,it,j=i(()=>{K();({Mixin:nt}=Shopware),it={template:U,inject:["repositoryFactory"],mixins:[nt.getByName("notification")],shortcuts:{"SYSTEMKEY+S":"onSave",ESCAPE:"onCancel"},props:{notificationTargetId:{type:String,required:!1,default:null}},data(){return{notificationTarget:null,isLoading:!1,isSaveSuccessful:!1,processSuccess:!1}},metaInfo(){return{title:this.$createTitle(this.identifier)}},computed:{notificationTargetIsLoading(){return this.isLoading||this.notificationTarget==null},notificationTargetRepository(){return this.repositoryFactory.create("payone_payment_notification_target")}},watch:{notificationTargetId(){this.createdComponent()}},created(){this.createdComponent()},methods:{createdComponent(){if(this.notificationTargetId){this.loadEntityData();return}Shopware.State.commit("context/resetLanguageToDefault"),this.notificationTarget=this.notificationTargetRepository.create(Shopware.Context.api)},loadEntityData(){this.isLoading=!0,this.notificationTargetRepository.get(this.notificationTargetId,Shopware.Context.api).then(e=>{this.isLoading=!1,this.notificationTarget=e,e.txactions!==null&&(e.txactions.length||(this.notificationTarget.txactions=null))})},isInvalid(){return this.notificationTarget.isBasicAuth!==!0||this.notificationTarget.username&&this.notificationTarget.password?!1:(this.createNotificationError({message:this.$tc("global.notification.notificationSaveErrorMessageRequiredFieldsInvalid")}),!0)},onSave(){this.isInvalid()||(this.isLoading=!0,this.notificationTargetRepository.save(this.notificationTarget,Shopware.Context.api).then(()=>{if(this.isLoading=!1,this.isSaveSuccessful=!0,this.createNotificationSuccess({message:this.$tc("payonePayment.notificationTarget.messages.successfullySaved")}),this.notificationTargetId===null){this.$router.push({name:"payone.notification.target.detail",params:{id:this.notificationTarget.id}});return}this.loadEntityData()}).catch(e=>{throw this.isLoading=!1,this.createNotificationError({message:this.$tc("global.notification.notificationSaveErrorMessageRequiredFieldsInvalid")}),e}))},onCancel(){this.$router.push({name:"payone.notification.target.list"})}}}});var Q,H=i(()=>{Q=`{% block payone_notification_target_list %}
    <sw-page class="sw-review-list">

        {% block payone_notification_target_list_smart_bar_header %}
            <template #smart-bar-header>
                {% block payone_notification_target_list_smart_bar_header_title %}
                    <h2>

                        {% block payone_notification_target_list_smart_bar_header_title_text %}
                            {{ $tc('payonePayment.notificationTarget.list.title') }}
                        {% endblock %}

                        {% block payone_notification_target_list_smart_bar_header_amount %}
                            <span v-if="!isLoading" class="sw-page__smart-bar-amount">
                                ({{ items.total }})
                            </span>
                        {% endblock %}
                    </h2>
                {% endblock %}
            </template>
        {% endblock %}

        {% block payone_notification_target_list_actions %}
            <template #smart-bar-actions>
                {% block payone_notification_target_list_smart_bar_actions %}
                    <sw-button
                        :routerLink="{ name: 'payone.notification.target.create' }"
                        variant="primary">
                        {{ $tc('payonePayment.notificationTarget.list.buttonCreate') }}
                    </sw-button>
                {% endblock %}
            </template>
        {% endblock %}

        {% block payone_notification_target_list_content %}
            <template #content>

                {% block payone_notification_target_list_content_list %}
                    <sw-entity-listing
                        v-if="items"
                        ref="payoneNotificationTargetGrid"
                        detailRoute="payone.notification.target.detail"
                        :limit="criteriaLimit"
                        :repository="repository"
                        :columns="notificationTargetColumns"
                        :items="items"
                        @column-sort="onSortColumn"
                        :disableDataFetching="true"
                        :sortBy="sortBy"
                        :showSelection="false"
                        :allowInlineEdit="false"
                        :sortDirection="sortDirection"
                        identifier="payone-notification-target-list">

                        {% block payone_notification_target_list_grid_columns %}
                            {% block payone_notification_target_list_grid_columns_url %}
                                <template #column-url="{ item }">
                                    <router-link :to="{ name: 'payone.notification.target.detail', params: { id: item.id } }">
                                        {{ item.url  }}
                                    </router-link>
                                </template>
                            {% endblock %}

                            {% block payone_notification_target_list_grid_columns_is_basic_auth %}
                                <template #column-isBasicAuth="{ item }">
                                    <sw-icon v-if="item.isBasicAuth" name="regular-checkmark-xs" small class="is--active"></sw-icon>
                                    <sw-icon v-else name="regular-times-s" small class="is--inactive"></sw-icon>
                                </template>
                            {% endblock %}

                            {% block payone_notification_target_list_grid_columns_txactions %}
                                <template #column-txactions="{ item }">
                                    {{ renderTxactions(item.txactions) }}
                                </template>
                            {% endblock %}
                        {% endblock %}
                    </sw-entity-listing>
                {% endblock %}

                {% block payone_notification_target_list_empty_state %}
                    <sw-empty-state v-if="!isLoading && !total"
                                    icon="default-documentation-file"
                                    :title="$tc('payonePayment.notificationTarget.list.empty')">
                    </sw-empty-state>
                {% endblock %}
            </template>
        {% endblock %}

        {% block payone_notification_target_list_sidebar %}
            <template #sidebar>
                <sw-sidebar>

                    {% block payone_notification_target_list_sidebar_refresh %}
                        <sw-sidebar-item
                            icon="default-arrow-360-left"
                            :title="$tc('sw-review.list.titleSidebarItemRefresh')"
                            @click="onRefresh">
                        </sw-sidebar-item>
                    {% endblock %}
                </sw-sidebar>
            </template>
        {% endblock %}
    </sw-page>
{% endblock %}
`});var J={};r(J,{default:()=>lt});var rt,st,lt,X=i(()=>{H();({Mixin:rt,Data:{Criteria:st}}=Shopware),lt={template:Q,inject:["repositoryFactory"],mixins:[rt.getByName("listing")],data(){return{isLoading:!1,items:null,sortBy:"createdAt",criteriaLimit:500,criteriaPage:1,limit:500}},metaInfo(){return{title:this.$createTitle()}},computed:{notificationTargetColumns(){return[{dataIndex:"url",property:"url",label:"payonePayment.notificationTarget.columns.url",primary:!0},{dataIndex:"isBasicAuth",property:"isBasicAuth",label:"payonePayment.notificationTarget.columns.isBasicAuth"},{property:"txactions",label:"payonePayment.notificationTarget.columns.txactions"}]},repository(){return this.repositoryFactory.create("payone_payment_notification_target")},criteria(){return new st(this.criteriaPage,this.criteriaLimit)}},created(){this.createdComponent()},methods:{renderTxactions(e){return e===null||!e.length?"":e.join(", ")},createdComponent(){this.getList()},getList(){this.isLoading=!0;let e={...Shopware.Context.api,inheritance:!0};return this.repository.search(this.criteria,e).then(t=>{this.total=t.total,this.items=t,this.isLoading=!1})},onDelete(e){this.$refs.listing.deleteItem(e),this.getList()}}}});var te,ee=i(()=>{te=`{% block payone_payment_payment_details %}
    <div class="payone-capture-button">
        <sw-container v-tooltip="{message: $tc('sw-order.payone-payment.capture.tooltips.impossible'), disabled: buttonEnabled}" :key="buttonEnabled">
            <sw-button :disabled="!buttonEnabled" @click="openCaptureModal">
                {{ $tc('sw-order.payone-payment.capture.buttonTitle') }}
            </sw-button>
        </sw-container>

        <sw-modal v-if="showCaptureModal" @modal-close="closeCaptureModal" :title="$tc(\`sw-order.payone-payment.modal.capture.title\`)" class="payone-payment-detail--capture-modal">
            <payone-order-items :order="order" :items="items"></payone-order-items>

            <div class="payone-payment-detail--capture-modal--content">
                <sw-container columns="1fr 1fr" gap="0 32px">
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.orderAmount')" :value="currencyFilter(transaction.amount.totalPrice, order.currency.shortName)"></sw-text-field>
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.capture.captured')" :value="currencyFilter(capturedAmount, order.currency.shortName)"></sw-text-field>
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.remainingAmount')" :value="currencyFilter(remainingAmount, order.currency.shortName)"></sw-text-field>
                    <sw-number-field required="required" numberType="float" :digits="decimalPrecision" :label="$tc('sw-order.payone-payment.modal.capture.amount')"
                                     v-model:value="captureAmount"
                                     :min="0"
                                     :max="remainingAmount"></sw-number-field>
                </sw-container>
            </div>

            <template #modal-footer>
                <sw-button :disabled="isLoading" @click="closeCaptureModal">
                    {{ $tc('sw-order.payone-payment.modal.close') }}
                </sw-button>

                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading || captureAmount <= 0" variant="primary" @click="captureOrder">
                    {{ $tc('sw-order.payone-payment.modal.capture.submit') }}
                </sw-button-process>

                <sw-button-process :isLoading="isLoading" :processSuccess="isCaptureSuccessful" @process-finish="onCaptureFinished()" :disabled="isLoading || remainingAmount <= 0" variant="primary" @click="captureFullOrder">
                    {{ $tc('sw-order.payone-payment.modal.capture.fullSubmit') }}
                </sw-button-process>
            </template>
        </sw-modal>
    </div>
{% endblock %}
`});var ae=i(()=>{});var ie={};r(ie,{default:()=>pt});var dt,ne,pt,oe=i(()=>{ee();ae();({Mixin:dt,Filter:ne}=Shopware),pt={template:te,mixins:[dt.getByName("notification")],inject:["PayonePaymentService"],props:{order:{type:Object,required:!0},transaction:{type:Object,required:!0}},data(){return{isLoading:!1,hasError:!1,showCaptureModal:!1,isCaptureSuccessful:!1,captureAmount:0,includeShippingCosts:!1,items:[]}},computed:{currencyFilter(){return ne.getByName("currency")},payoneCurrencyFilter(){return ne.getByName("payone_currency")},orderTotalPrice(){return this.transaction.amount.totalPrice},decimalPrecision(){if(!this.order||!this.order.currency)return 2;if(this.order.currency.decimalPrecision)return this.order.currency.decimalPrecision;if(this.order.currency.itemRounding)return this.order.currency.itemRounding.decimals},capturedAmount(){return this.toFixedPrecision((this.transaction?.extensions?.payonePaymentOrderTransactionData?.capturedAmount??0)/100)},remainingAmount(){return this.toFixedPrecision(this.orderTotalPrice-this.capturedAmount)},buttonEnabled(){return this.transaction?.extensions?.payonePaymentOrderTransactionData?this.remainingAmount>0||this.transaction.extensions.payonePaymentOrderTransactionData.allowCapture:!1},selectedItems(){return this.items.filter(e=>e.selected&&e.quantity>0)},hasRemainingShippingCosts(){return this.order.shippingCosts.totalPrice<=0?!1:this.toFixedPrecision(this.capturedAmount+this.order.shippingCosts.totalPrice)<=this.orderTotalPrice}},methods:{toFixedPrecision(e){return Math.round(e*10**this.decimalPrecision)/10**this.decimalPrecision},calculateActionAmount(){let e=0;this.selectedItems.forEach(t=>{e+=t.unit_price*t.quantity}),this.captureAmount=this.toFixedPrecision(e>this.remainingAmount?this.remainingAmount:e)},openCaptureModal(){this.showCaptureModal=!0,this.isCaptureSuccessful=!1,this.initItems()},initItems(){this.items=this.order.lineItems.map(e=>{let t=e.quantity-(e.customFields?.payone_captured_quantity??0);return{id:e.id,quantity:t,maxQuantity:t,unit_price:e.unitPrice,selected:!1,product:e.label,disabled:t<=0}}),this.order.shippingCosts.totalPrice>0&&this.items.push({id:"shipping",quantity:1,maxQuantity:1,unit_price:this.order.shippingCosts.totalPrice,selected:!1,disabled:!1,product:this.$tc("sw-order.payone-payment.modal.shippingCosts")})},closeCaptureModal(){this.showCaptureModal=!1},onCaptureFinished(){this.isCaptureSuccessful=!1},captureOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.captureAmount,orderLines:[],complete:this.captureAmount===this.remainingAmount,includeShippingCosts:!1};this.isLoading=!0,this.selectedItems.forEach(t=>{if(t.id==="shipping")e.includeShippingCosts=!0;else{let a=this.order.lineItems.find(n=>n.id===t.id);if(a){let n={...a};n.quantity=t.quantity,n.total_amount=n.unit_price*n.quantity,n.total_tax_amount=n.total_amount-n.total_amount/(1+n.tax_rate/100),e.orderLines.push(n)}}}),this.remainingAmount<e.amount&&(e.amount=this.remainingAmount),this.executeCapture(e)},captureFullOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.remainingAmount,orderLines:[],complete:!0,includeShippingCosts:this.hasRemainingShippingCosts};this.isLoading=!0,e.orderLines=this.order.lineItems.map(t=>({id:t.id,quantity:t.quantity-(t.customFields?.payone_captured_quantity??0),unit_price:t.unitPrice,selected:!1})),this.executeCapture(e)},executeCapture(e){this.PayonePaymentService.capturePayment(e).then(()=>{this.createNotificationSuccess({title:this.$tc("sw-order.payone-payment.capture.successTitle"),message:this.$tc("sw-order.payone-payment.capture.successMessage")}),this.isCaptureSuccessful=!0}).catch(t=>{this.createNotificationError({title:this.$tc("sw-order.payone-payment.capture.errorTitle"),message:t.message}),this.isCaptureSuccessful=!1}).finally(()=>{this.isLoading=!1,this.closeCaptureModal(),this.$nextTick().then(()=>{this.$emit("reload")})})}},watch:{items:{handler(){this.calculateActionAmount()},deep:!0}}}});var se,re=i(()=>{se=`{% block payone_payment_details %}
    <div class="payone-order-items">
        <sw-data-grid
            :dataSource="items"
            :columns="orderItemColumns"
            :showActions="false"
            :showSelection="true"
            @selection-change="updateSelection"
            :isRecordSelectable="(item) => !item.disabled"
        >
            <template #column-quantity="{ item, isInlineEdit }">
                <sw-number-field
                    v-model:value="item.quantity"
                    type="number"
                    :step="1"
                    :min="0"
                    :disabled="item.disabled || !item.selected"
                    :max="item.maxQuantity"
                    slot="inline-edit"
                    size="small"
                    placeholder="0"
                ></sw-number-field>
            </template>

            <template #column-price="{ item }">
                {{ currencyFilter(item.unit_price, order.currency.shortName) }}
            </template>
        </sw-data-grid>
    </div>
{% endblock %}
`});var le=i(()=>{});var ce={};r(ce,{default:()=>yt});var mt,yt,de=i(()=>{re();le();({Filter:mt}=Shopware),yt={template:se,props:{items:{type:Array,required:!0},order:{type:Object,required:!0}},computed:{currencyFilter:()=>mt.getByName("currency"),orderItemColumns(){return[{property:"product",label:this.$tc("sw-order.payone-payment.modal.columns.product"),rawData:!0},{property:"quantity",label:this.$tc("sw-order.payone-payment.modal.columns.quantity"),rawData:!0},{property:"price",label:this.$tc("sw-order.payone-payment.modal.columns.price"),rawData:!0}]}},methods:{updateSelection(e){let t=Object.keys(e);this.items.forEach(a=>{a.selected=t.includes(a.id)})}}}});var ue,pe=i(()=>{ue=`{% block payone_payment_management %}
    <div class="payone-payment-management">
        <template v-for="(transaction, index) in payoneTransactions">
            <sw-card class="payone-payment-management-card" position-identifier="payone-payment-management-card" :title="index === 0 ? $tc('sw-order.payone-payment.general.cardTitle') : ''">
                <sw-container columns="1fr 1fr">
                    <sw-container>
                        <sw-description-list>
                            <dt>{{ $tc('sw-order.payone-payment.paymentMethod') }}</dt>
                            <dd class="sw-order-base__label-sales-channel">{{ transaction.paymentMethod.translated.distinguishableName }}</dd>

                            <template v-if="getPayoneCardType(transaction)">
                                <dt>{{ $tc('sw-order.payone-payment.creditCard.cardTypeLabel') }}</dt>
                                <dd class="sw-order-base__label-sales-channel">{{ getPayoneCardType(transaction) }}</dd>
                            </template>

                            <dt>{{ $tc('sw-order.payone-payment.txid') }}</dt>
                            <dd class="sw-order-base__label-sales-channel">{{ transaction.extensions.payonePaymentOrderTransactionData.transactionId }}</dd>

                            <dt>{{ $tc('sw-order.payone-payment.sequenceNumber.label') }}</dt>
                            <dd class="sw-order-base__label-sales-channel">
                                    <span v-if="transaction.extensions.payonePaymentOrderTransactionData.sequenceNumber == -1">
                                        {{ $tc('sw-order.payone-payment.sequenceNumber.empty') }}
                                    </span>
                                <span v-else>
                                        {{ transaction.extensions.payonePaymentOrderTransactionData.sequenceNumber }}
                                    </span>
                            </dd>

                            <dt>{{ $tc('sw-order.payone-payment.transactionState') }}</dt>
                            <dd class="sw-order-base__label-sales-channel" v-if="isActiveTransaction(transaction)">{{ transaction.extensions.payonePaymentOrderTransactionData.transactionState }}</dd>
                            <dd class="sw-order-base__label-sales-channel" v-else>{{ $tc('sw-order.payone-payment.transactionCancelled') }}</dd>
                        </sw-description-list>
                    </sw-container>

                    <sw-container gap="30px" v-if="isActiveTransaction(transaction) && can('Payone.payone_order_management')">
                        <payone-capture-button :order="order" :transaction="transaction" v-on:reload="reloadEntityData"></payone-capture-button>
                        <payone-refund-button :order="order" :transaction="transaction" v-on:reload="reloadEntityData"></payone-refund-button>
                    </sw-container>
                </sw-container>

                <sw-container v-if="hasNotificationForwards(transaction)" gap="10px" class="payone-payment-management-notification-forwards">
                    <b>{{ $tc('payonePayment.notificationTarget.list.title') }}</b>

                    <sw-data-grid
                            :dataSource="notificationForwards"
                            :selectable="false"
                            :isFullpage="false"
                            :showSelection="false"
                            :compactMode="true"
                            :showActions="true"
                            :allowInlineEdit="false"
                            :columns="notificationTargetColumns"
                            :plainAppearance="true"
                            :showHeader="false"
                            table>

                        <template #column-updatedAt="{ item }">
                            {{ dateFilter(item.updatedAt, { hour: '2-digit', minute: '2-digit' }) }}
                        </template>

                        <template #actions="{ item }">
                            <sw-context-menu-item
                                    @click="requeue(item, transaction)">
                                {{ $tc('payonePayment.notificationTarget.actions.requeue') }}
                            </sw-context-menu-item>
                        </template>

                    </sw-data-grid>
                </sw-container>
            </sw-card>
        </template>
    </div>
{% endblock %}
`});var me=i(()=>{});var ye={};r(ye,{default:()=>bt});var ft,gt,m,bt,he=i(()=>{pe();me();({Mixin:ft,Filter:gt}=Shopware),{Criteria:m}=Shopware.Data,bt={template:ue,inject:["acl","PayonePaymentService","repositoryFactory"],mixins:[ft.getByName("notification")],props:{order:{type:Object,required:!0}},data(){return{notificationForwards:null}},computed:{dateFilter(){return gt.getByName("date")},payoneTransactions:function(){return this.order.transactions.filter(e=>this.isPayoneTransaction(e)).sort((e,t)=>e.createdAt<t.createdAt?1:e.createdAt>t.createdAt?-1:0)},notificationForwardRepository(){return this.repositoryFactory.create("payone_payment_notification_forward")},notificationTargetColumns(){return[{property:"txaction",type:"text",width:"100px"},{property:"notificationTarget.url",type:"text"},{property:"response",width:"100px"},{property:"updatedAt",align:"right",type:"date"}]}},methods:{isPayoneTransaction(e){return!e.extensions||!e.extensions.payonePaymentOrderTransactionData||!e.extensions.payonePaymentOrderTransactionData.transactionId?!1:e.extensions.payonePaymentOrderTransactionData.transactionId},isActiveTransaction(e){return e.stateMachineState.technicalName!=="cancelled"},hasNotificationForwards(e){return this.notificationForwards===null?(this.getNotificationForwards(e),!1):this.notificationForwards.length>0},getNotificationForwards(e){let t=new m;return t.addAssociation("notificationTarget"),t.addSorting(m.sort("updatedAt","DESC",!0)),t.addFilter(m.equals("transactionId",e.id)),t.setLimit(500),this.notificationForwardRepository.search(t,Shopware.Context.api).then(a=>{this.notificationForwards=a})},requeue(e,t){let a={notificationForwardId:e.id};this.PayonePaymentService.requeueNotificationForward(a).then(()=>{this.createNotificationSuccess({title:this.$tc("payonePayment.notificationTarget.actions.requeue"),message:this.$tc("payonePayment.notificationTarget.messages.success")}),this.getNotificationForwards(t)}).catch(n=>{this.createNotificationError({title:this.$tc("payonePayment.notificationTarget.actions.requeue"),message:n.message})}).finally(()=>{this.$nextTick().then(()=>{this.$emit("reload")})})},can(e){try{return this.acl.can(e)}catch{return!0}},reloadEntityData(){this.$emit("reload-entity-data")},getPayoneCardType(e){let t=e.extensions.payonePaymentOrderTransactionData?.additionalData?.card_type;return t?this.$tc("sw-order.payone-payment.creditCard.cardTypes."+t):null}}}});var ge,fe=i(()=>{ge=`{% block payone_payment_order_action_log %}
    <sw-card
            class="payone-payment-order-action-log-card"
            position-identifier="payone-payment-order-action-log-card"
            :title="$tc('sw-order.payone-payment.orderActionLog.cardTitle')"
    >
        <sw-data-grid
                v-if="orderActionLogs.length > 0"
                :showSelection="false"
                :dataSource="orderActionLogs"
                :columns="orderActionLogColumns"
                :isLoading="isLoading"
        >
            <template #column-amount="{ item }">
                {{ currencyFilter(item.amount / 100, order.currency.isoCode) }}
            </template>

            <template #column-requestDateTime="{ item }">
                {{ dateFilter(item.requestDateTime, { hour: '2-digit', minute: '2-digit' }) }}
            </template>

            <template #actions="{ item }">
                <sw-context-menu-item @click="openRequest(item)">
                    {{ $tc('sw-order.payone-payment.orderActionLog.contextMenu.openRequestDetails') }}
                </sw-context-menu-item>
                <sw-context-menu-item @click="openResponse(item)">
                    {{ $tc('sw-order.payone-payment.orderActionLog.contextMenu.openResponseDetails') }}
                </sw-context-menu-item>
            </template>

            <template #action-modals="{ item }">
                <sw-modal
                        v-if="showRequestDetails"
                        :title="$tc('sw-order.payone-payment.orderActionLog.requestDetailsModal.title')"
                        variant="large"
                        @modal-close="onCloseRequestModal"
                >
                    <sw-button variant="primary" @click="downloadAsTxt(showRequestDetails, 'request', item.transactionId)">
                        {{ $tc('sw-order.payone-payment.orderActionLog.requestDetailsModal.downloadButton') }}
                    </sw-button>
                    <sw-data-grid
                            :showSelection="false"
                            :showActions="false"
                            :dataSource="toKeyValueSource(showRequestDetails)"
                            :columns="keyValueColumns"
                            :isLoading="isLoading"
                    >
                    </sw-data-grid>
                </sw-modal>
                <sw-modal
                        v-if="showResponseDetails"
                        :title="$tc('sw-order.payone-payment.orderActionLog.responseDetailsModal.title')"
                        variant="large"
                        @modal-close="onCloseResponseModal"
                >
                    <sw-button variant="primary" @click="downloadAsTxt(showResponseDetails, 'response', item.transactionId)">
                        {{ $tc('sw-order.payone-payment.orderActionLog.responseDetailsModal.downloadButton') }}
                    </sw-button>
                    <sw-data-grid
                            :showSelection="false"
                            :showActions="false"
                            :dataSource="toKeyValueSource(showResponseDetails)"
                            :columns="keyValueColumns"
                            :isLoading="isLoading"
                    >
                    </sw-data-grid>
                </sw-modal>
            </template>
        </sw-data-grid>

        <sw-empty-state
                v-else
                :absolute="false"
                :title="$tc('sw-order.payone-payment.orderActionLog.emptyState.title')"
                :subline="$tc('sw-order.payone-payment.orderActionLog.emptyState.subline')"
        >
            <template #icon>
                <img
                        :src="assetFilter('/administration/static/img/empty-states/order-empty-state.svg')"
                        :alt="$tc('sw-order.payone-payment.orderActionLog.emptyState.title')"
                >
            </template>
        </sw-empty-state>
    </sw-card>
{% endblock %}
`});var be={};r(be,{default:()=>_t});var y,d,_t,we=i(()=>{fe();({Criteria:y}=Shopware.Data),{Filter:d}=Shopware,_t={template:ge,inject:["repositoryFactory"],props:{order:{type:Object,required:!0}},data(){return{orderActionLogs:[],isLoading:!1,showRequestDetails:null,showResponseDetails:null}},computed:{orderActionLogRepository(){return this.repositoryFactory.create("payone_payment_order_action_log")},dateFilter(){return d.getByName("date")},currencyFilter(){return d.getByName("currency")},assetFilter(){return d.getByName("asset")},payoneCurrencyFilter(){return d.getByName("payone_currency")},orderActionLogColumns(){return[{property:"transactionId",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleTransactionId")},{property:"request",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleRequest")},{property:"response",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleResponse")},{property:"amount",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleAmount")},{property:"requestDateTime",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleRequestDateTime")}]},keyValueColumns(){return[{property:"key",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleKey")},{property:"value",label:this.$tc("sw-order.payone-payment.orderActionLog.columnTitleValue")}]}},created(){this.createdComponent()},methods:{createdComponent(){this.getOrderActionLogs()},reloadActionLogs(){this.getOrderActionLogs()},getOrderActionLogs(){let e=new y;return e.addFilter(y.equals("orderId",this.order.id)),e.addSorting(y.sort("requestDateTime","ASC",!0)),this.isLoading=!0,this.orderActionLogRepository.search(e,Shopware.Context.api).then(t=>{this.orderActionLogs=t,this.isLoading=!1})},openRequest(e){this.showRequestDetails=e.requestDetails},openResponse(e){this.showResponseDetails=e.responseDetails},onCloseRequestModal(){this.showRequestDetails=null},onCloseResponseModal(){this.showResponseDetails=null},toKeyValueSource(e){let t=[];for(let a in e)t.push({key:a,value:e[a]});return t.sort((a,n)=>a.key.localeCompare(n.key)),t},downloadAsTxt(e,t,a){let n=document.createElement("a");n.href="data:text/plain;charset=utf-8,"+encodeURIComponent(JSON.stringify(e,null,4)),n.download=`PAYONE-${t}-${a}.txt`,n.dispatchEvent(new MouseEvent("click")),n.remove()}}}});var Pe,_e=i(()=>{Pe=`{% block payone_payment_webhook_log %}
    <sw-card
            class="payone-payment-webhook-log-card"
            position-identifier="payone-payment-webhook-log-card"
            :title="$tc('sw-order.payone-payment.webhookLog.cardTitle')"
    >
        <sw-data-grid
                v-if="webhookLogs.length > 0"
                :showSelection="false"
                :dataSource="webhookLogs"
                :columns="webhookLogColumns"
                :isLoading="isLoading"
        >
            <template #column-webhookDateTime="{ item }">
                {{ dateFilter(item.webhookDateTime, { hour: '2-digit', minute: '2-digit' }) }}
            </template>

            <template #actions="{ item }">
                <sw-context-menu-item @click="openDetails(item)">
                    {{ $tc('sw-order.payone-payment.webhookLog.contextMenu.openWebhookDetails') }}
                </sw-context-menu-item>
            </template>

            <template #action-modals="{ item }">
                <sw-modal
                        v-if="showWebhookDetails"
                        :title="$tc('sw-order.payone-payment.webhookLog.webhookDetailsModal.title')"
                        variant="large"
                        @modal-close="onCloseWebhookModal"
                >
                    <sw-button variant="primary" @click="downloadAsTxt(showWebhookDetails, 'webhook', item.transactionId)">
                        {{ $tc('sw-order.payone-payment.webhookLog.webhookDetailsModal.downloadButton') }}
                    </sw-button>
                    <sw-data-grid
                            :showSelection="false"
                            :showActions="false"
                            :dataSource="toKeyValueSource(showWebhookDetails)"
                            :columns="keyValueColumns"
                            :isLoading="isLoading"
                    >
                    </sw-data-grid>
                </sw-modal>
            </template>
        </sw-data-grid>

        <sw-empty-state
                v-else
                :absolute="false"
                :title="$tc('sw-order.payone-payment.webhookLog.emptyState.title')"
                :subline="$tc('sw-order.payone-payment.webhookLog.emptyState.subline')"
        >
            <template #icon>
                <img
                        :src="assetFilter('/administration/static/img/empty-states/order-empty-state.svg')"
                        :alt="$tc('sw-order.payone-payment.webhookLog.emptyState.title')"
                >
            </template>
        </sw-empty-state>
    </sw-card>
{% endblock %}
`});var Ae={};r(Ae,{default:()=>kt});var h,ke,kt,ve=i(()=>{_e();({Criteria:h}=Shopware.Data),{Filter:ke}=Shopware,kt={template:Pe,inject:["repositoryFactory"],props:{order:{type:Object,required:!0}},data(){return{webhookLogs:[],isLoading:!1,showWebhookDetails:null}},computed:{webhookLogRepository(){return this.repositoryFactory.create("payone_payment_webhook_log")},assetFilter(){return ke.getByName("asset")},dateFilter(){return ke.getByName("date")},webhookLogColumns(){return[{property:"transactionId",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleTransactionId")},{property:"transactionState",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleTransactionState")},{property:"sequenceNumber",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleSequenceNumber")},{property:"clearingType",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleClearingType")},{property:"webhookDateTime",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleWebhookDateTime")}]},keyValueColumns(){return[{property:"key",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleKey")},{property:"value",label:this.$tc("sw-order.payone-payment.webhookLog.columnTitleValue")}]}},created(){this.createdComponent()},methods:{createdComponent(){this.getWebhookLogs()},reloadWebhookLogs(){this.getWebhookLogs()},getWebhookLogs(){let e=new h;return e.addFilter(h.equals("orderId",this.order.id)),e.addSorting(h.sort("webhookDateTime","ASC",!0)),this.isLoading=!0,this.webhookLogRepository.search(e,Shopware.Context.api).then(t=>{this.webhookLogs=t,this.isLoading=!1})},openDetails(e){this.showWebhookDetails=e.webhookDetails},onCloseWebhookModal(){this.showWebhookDetails=null},toKeyValueSource(e){let t=[];for(let a in e)t.push({key:a,value:e[a]});return t.sort((a,n)=>a.key.localeCompare(n.key)),t},downloadAsTxt(e,t,a){let n=document.createElement("a");n.href="data:text/plain;charset=utf-8,"+encodeURIComponent(JSON.stringify(e,null,4)),n.download=`PAYONE-${t}-${a}.txt`,n.dispatchEvent(new MouseEvent("click")),n.remove()}}}});var Te,Se=i(()=>{Te=`{% block payone_payment_payment_details %}
    <div class="payone-refund-button">
        <sw-container v-tooltip="{message: $tc('sw-order.payone-payment.refund.tooltips.impossible'), disabled: buttonEnabled}" :key="buttonEnabled">
            <sw-button :disabled="!buttonEnabled" @click="openRefundModal">
                {{ $tc('sw-order.payone-payment.refund.buttonTitle') }}
            </sw-button>
        </sw-container>

        <sw-modal v-if="showRefundModal" @modal-close="closeRefundModal" :title="$tc(\`sw-order.payone-payment.modal.refund.title\`)" class="payone-payment-detail--refund-modal">
            <payone-order-items :order="order" :items="items"></payone-order-items>

            <div class="payone-payment-detail--refund-modal--content">
                <sw-container columns="1fr 1fr" gap="0 32px">
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.orderAmount')" :value="currencyFilter(transaction.amount.totalPrice, order.currency.shortName)"></sw-text-field>
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.refund.refunded')" :value="payoneCurrencyFilter(refundedAmount, order.currency.shortName)"></sw-text-field>
                    <sw-text-field :disabled="true" :label="$tc('sw-order.payone-payment.modal.remainingAmount')" :value="payoneCurrencyFilter(remainingAmount, order.currency.shortName)"></sw-text-field>
                    <sw-number-field required="required" numberType="float" :digits="decimalPrecision" :label="$tc('sw-order.payone-payment.modal.refund.amount')"
                                     v-model:value="refundAmount"
                                     :min="0"
                                     :max="remainingAmount"></sw-number-field>
                </sw-container>
            </div>

            <template #modal-footer>
                <sw-button :disabled="isLoading" @click="closeRefundModal">
                    {{ $tc('sw-order.payone-payment.modal.close') }}
                </sw-button>

                <sw-button-process :isLoading="isLoading" :processSuccess="isRefundSuccessful" @process-finish="onRefundFinished()" :disabled="isLoading || refundAmount <= 0" variant="primary" @click="refundOrder">
                    {{ $tc('sw-order.payone-payment.modal.refund.submit') }}
                </sw-button-process>

                <sw-button-process :isLoading="isLoading" :processSuccess="isRefundSuccessful" @process-finish="onRefundFinished()" :disabled="isLoading" variant="primary" @click="refundFullOrder">
                    {{ $tc('sw-order.payone-payment.modal.refund.fullSubmit') }}
                </sw-button-process>
            </template>
        </sw-modal>
    </div>
{% endblock %}
`});var Ce=i(()=>{});var Ie={};r(Ie,{default:()=>St});var vt,xe,St,Ee=i(()=>{Se();Ce();({Mixin:vt,Filter:xe}=Shopware),St={template:Te,mixins:[vt.getByName("notification")],inject:["PayonePaymentService"],props:{order:{type:Object,required:!0},transaction:{type:Object,required:!0}},data(){return{isLoading:!1,hasError:!1,showRefundModal:!1,isRefundSuccessful:!1,refundAmount:0,includeShippingCosts:!1,items:[]}},computed:{currencyFilter(){return xe.getByName("currency")},payoneCurrencyFilter(){return xe.getByName("payone_currency")},orderTotalPrice(){return this.transaction.amount.totalPrice},decimalPrecision(){if(!this.order||!this.order.currency)return 2;if(this.order.currency.decimalPrecision)return this.order.currency.decimalPrecision;if(this.order.currency.itemRounding)return this.order.currency.itemRounding.decimals},transactionData(){return this.transaction?.extensions?.payonePaymentOrderTransactionData??{capturedAmount:0,refundedAmount:0,allowRefund:!1}},capturedAmount(){return this.toFixedPrecision((this.transaction?.extensions?.payonePaymentOrderTransactionData?.capturedAmount??0)/100)},remainingAmount(){return(this.transactionData.capturedAmount??0)-(this.transactionData.refundedAmount??0)},refundedAmount(){return this.transactionData.refundedAmount??0},buttonEnabled(){return this.transaction?.extensions?.payonePaymentOrderTransactionData?this.remainingAmount>0||this.transactionData.allowRefund:!1},selectedItems(){return this.items.filter(e=>e.selected&&e.quantity>0)},hasRemainingRefundableShippingCosts(){return this.order.shippingCosts.totalPrice<=0?!1:this.toFixedPrecision(this.refundedAmount+this.order.shippingCosts.totalPrice)<=this.capturedAmount}},methods:{toFixedPrecision(e){return Math.round(e*10**this.decimalPrecision)/10**this.decimalPrecision},calculateActionAmount(){let e=0;this.selectedItems.forEach(t=>{e+=t.unit_price*t.quantity}),this.refundAmount=this.toFixedPrecision(e>this.remainingAmount?this.remainingAmount:e)},openRefundModal(){this.showRefundModal=!0,this.isRefundSuccessful=!1,this.initItems()},initItems(){this.items=this.order.lineItems.map(e=>{let t=this.getRefundableQuantityOfItem(e);return{id:e.id,quantity:t,maxQuantity:t,unit_price:e.unitPrice,selected:!1,product:e.label,disabled:t<=0}}),this.order.shippingCosts.totalPrice>0&&this.items.push({id:"shipping",quantity:1,maxQuantity:1,unit_price:this.order.shippingCosts.totalPrice,selected:!1,disabled:!1,product:this.$tc("sw-order.payone-payment.modal.shippingCosts")})},closeRefundModal(){this.showRefundModal=!1},onRefundFinished(){this.isRefundSuccessful=!1},refundOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.refundAmount,orderLines:[],complete:this.refundAmount===this.maxRefundAmount,includeShippingCosts:!1};this.isLoading=!0,this.selectedItems.forEach(t=>{if(t.id==="shipping")e.includeShippingCosts=!0;else{let a=this.order.lineItems.find(n=>n.id===t.id);if(a){let n={...a};n.quantity=t.quantity,n.total_amount=n.unit_price*n.quantity,n.total_tax_amount=n.total_amount-n.total_amount/(1+n.tax_rate/100),e.orderLines.push(n)}}}),this.remainingAmount<e.amount&&(e.amount=this.remainingAmount),this.executeRefund(e)},getRefundableQuantityOfItem(e){return(e.customFields?.payone_captured_quantity??e.quantity)-(e.customFields?.payone_refunded_quantity??0)},refundFullOrder(){let e={orderTransactionId:this.transaction.id,payone_order_id:this.transaction.extensions.payonePaymentOrderTransactionData.transactionId,salesChannel:this.order.salesChannel,amount:this.maxRefundAmount,orderLines:[],complete:!0,includeShippingCosts:this.hasRemainingRefundableShippingCosts};this.isLoading=!0,e.orderLines=this.order.lineItems.map(t=>({id:t.id,quantity:this.getRefundableQuantityOfItem(t),unit_price:t.unitPrice,selected:!1})),this.executeRefund(e)},executeRefund(e){this.PayonePaymentService.refundPayment(e).then(()=>{this.createNotificationSuccess({title:this.$tc("sw-order.payone-payment.refund.successTitle"),message:this.$tc("sw-order.payone-payment.refund.successMessage")}),this.isRefundSuccessful=!0}).catch(t=>{this.createNotificationError({title:this.$tc("sw-order.payone-payment.refund.errorTitle"),message:t.message}),this.isRefundSuccessful=!1}).finally(()=>{this.isLoading=!1,this.closeRefundModal(),this.$nextTick().then(()=>{this.$emit("reload")})})}},watch:{items:{handler(){this.calculateActionAmount()},deep:!0}}}});var De,$e=i(()=>{De=`{% block sw_order_detail_payone %}
    <div class="sw-order-detail-payone" v-if="order">
        {% block sw_order_detail_payone_general %}
            <payone-payment-management
                    :order="order"
                    @reload-entity-data="reloadEntityData"
            />
        {% endblock %}

        {% block sw_order_detail_payone_order_action_log %}
            <payone-payment-order-action-log
                    ref="payoneOrderActionLogs"
                    :order="order"
                    @reload-entity-data="reloadEntityData"
            />
        {% endblock %}

        {% block sw_order_detail_payone_webhook_log %}
            <payone-payment-webhook-log
                    ref="payoneWebhookLogs"
                    :order="order"
                    @reload-entity-data="reloadEntityData"
            />
        {% endblock %}
    </div>
{% endblock %}
`});var Le={};r(Le,{default:()=>It});var Ct,xt,It,Ne=i(()=>{$e();({Component:Ct}=Shopware),{mapState:xt}=Ct.getComponentHelper(),It={template:De,props:{orderId:{type:String,required:!0}},computed:{...xt("swOrderDetail",["order"])},methods:{reloadEntityData(){this.$refs.payoneOrderActionLogs.reloadActionLogs(),this.$refs.payoneWebhookLogs.reloadWebhookLogs(),this.$emit("reload-entity-data")}}}});var Re,Oe=i(()=>{Re=`{% block sw_order_detail_content_tabs_extension %}
    {% parent %}

    {% block sw_order_detail_content_tabs_payone %}
        <sw-tabs-item
                v-if="order && hasPayoneTransaction(order)"
                class="sw-order-detail__tabs-tab-payone"
                :route="{ name: 'sw.order.detail.payone', params: { id: $route.params.id } }"
                :title="$tc('sw-order.detail.payone')"
        >
            {{ $tc('sw-order.detail.payone') }}
        </sw-tabs-item>
    {% endblock %}

{% endblock %}`});var Fe={};r(Fe,{default:()=>$t});var $t,Me=i(()=>{Oe();$t={template:Re,methods:{hasPayoneTransaction(e){let t=this,a=!1;return e.transactions?(e.transactions.map(function(n){t.isPayoneTransaction(n)&&t.isActiveTransaction(n)&&(a=!0)}),a):!1},isPayoneTransaction(e){return!e.extensions||!e.extensions.payonePaymentOrderTransactionData||!e.extensions.payonePaymentOrderTransactionData.transactionId?!1:e.extensions.payonePaymentOrderTransactionData.transactionId},isActiveTransaction(e){return e.stateMachineState.technicalName!=="cancelled"}}}});var w={"payone-payment":{title:"PAYONE",general:{mainMenuItemGeneral:"PAYONE",descriptionTextModule:"Einstellungen f\xFCr PAYONE",headlines:{ratepayProfiles:"Profile",ratepayProfileConfigurations:"Profile Konfigurationen"},label:{shopId:"Shop-ID",currency:"W\xE4hrung",error:"Status",invoiceCountry:"Rechnungsland",shippingCountry:"Lieferland",minBasket:"Min. Warenkorb",maxBasket:"Max. Warenkorb",reloadConfigInfo:"Profile-Konfigurationen werden beim Speichern der Plugin-Einstellungen automatisch aktualisiert."},actions:{addShop:"Shop-ID hinzuf\xFCgen"},errors:{existingShopId:"Die eingegebene ShopId existiert bereits.",emptyInputs:"Bitte f\xFCllen Sie alle Eingabefelder aus."}},settingsForm:{save:"Speichern",test:"API-Zugangsdaten testen",titleSuccess:"Erfolg",titleError:"Fehler",labelShowSpecificStatusMapping:"Statusmappingkonfiguration einblenden",helpTextShowSpecificStatusMapping:"Sie k\xF6nnen f\xFCr jede Zahlungsart ein spezifisches Statusmapping konfigurieren. Existiert eine solche Konfiguration nicht, wird auf die allgemeine Konfiguration zur\xFCckgegriffen.",messageSaveError:{ratepayProfilesUpdateFailed:"Mindestens ein Ratepay Profil konnte nicht erfolgreich gespeichert werden, bitte pr\xFCfen Sie Ihre Konfiguration."},messageTestSuccess:"Die API-Zugangsdaten wurden erfolgreich validiert.",messageTestNoTestedPayments:"Bei der Pr\xFCfung wurden keine Zahlarten getestet, weil keine der PAYONE Zahlarten aktiviert ist. Bitte aktivieren Sie mindestens eine PAYONE Zahlart unter Einstellungen --> Shop --> Zahlungsarten.",messageTestError:{general:"Die API-Zugangsdaten konnten nicht validiert werden.",creditCard:"Die API-Zugangsdaten f\xFCr PAYONE Kreditkarte sind nicht korrekt.",prepayment:"Die API-Zugangsdaten f\xFCr PAYONE Vorkasse sind nicht korrekt.",debit:"Die API-Zugangsdaten f\xFCr PAYONE Lastschrift sind nicht korrekt.",paypalExpress:"Die API-Zugangsdaten f\xFCr PAYONE PayPal Express sind nicht korrekt.",paypal:"Die API-Zugangsdaten f\xFCr PAYONE PayPal sind nicht korrekt.",payolutionInstallment:"Die API-Zugangsdaten f\xFCr PAYONE Unzer Ratenkauf sind nicht korrekt.",payolutionInvoicing:"Die API-Zugangsdaten f\xFCr PAYONE Unzer Rechnungskauf sind nicht korrekt.",payolutionDebit:"Die API-Zugangsdaten f\xFCr PAYONE Unzer Lastschrift sind nicht korrekt.",sofort:"Die API-Zugangsdaten f\xFCr PAYONE Sofort \xDCberweisung sind nicht korrekt.",eps:"Die API-Zugangsdaten f\xFCr PAYONE eps \xDCberweisung sind nicht korrekt.",iDeal:"Die API-Zugangsdaten f\xFCr PAYONE iDEAL sind nicht korrekt.",secureInvoice:"Die API-Zugangsdaten f\xFCr PAYONE Gesicherter Rechnungskauf sind nicht korrekt.",openInvoice:"Die API-Zugangsdaten f\xFCr PAYONE Rechnungskauf sind nicht korrekt.",paydirekt:"Die API-Zugangsdaten f\xFCr PAYONE paydirekt sind nicht korrekt.",trustly:"Die API-Zugangsdaten f\xFCr PAYONE Trustly sind nicht korrekt.",applePay:"Die API-Zugangsdaten f\xFCr PAYONE Apple Pay sind nicht korrekt.",bancontact:"Die API-Zugangsdaten f\xFCr PAYONE Bancontact sind nicht korrekt.",ratepayDebit:"Die API-Zugangsdaten f\xFCr PAYONE Ratepay Lastschrift sind nicht korrekt.",ratepayInstallment:"Die API-Zugangsdaten f\xFCr PAYONE Ratepay Ratenzahlung sind nicht korrekt.",ratepayInvoicing:"Die API-Zugangsdaten f\xFCr PAYONE Ratepay Rechnungskauf sind nicht korrekt.",klarnaInvoice:"Die API-Zugangsdaten f\xFCr PAYONE Klarna Rechnung sind nicht korrekt.",klarnaDirectDebit:"Die API-Zugangsdaten f\xFCr PAYONE Klarna Sofort bezahlen sind nicht korrekt.",klarnaInstallment:"Die API-Zugangsdaten f\xFCr PAYONE Klarna Ratenkauf sind nicht korrekt.",przelewy24:"Die API-Zugangsdaten f\xFCr PAYONE Przelewy24 sind nicht korrekt.",weChatPay:"Die API-Zugangsdaten f\xFCr PAYONE WeChat Pay sind nicht korrekt.",postfinanceCard:"Die API-Zugangsdaten f\xFCr PAYONE Postfinance (Card) sind nicht korrekt.",postfinanceWallet:"Die API-Zugangsdaten f\xFCr PAYONE Postfinance (Wallet) sind nicht korrekt.",alipay:"Die API-Zugangsdaten f\xFCr PAYONE Alipay sind nicht korrekt.",securedInvoice:"Die API-Zugangsdaten f\xFCr PAYONE Gesicherter Rechnungskauf sind nicht korrekt.",securedInstallment:"Die API-Zugangsdaten f\xFCr PAYONE Gesicherter Ratenkauf sind nicht korrekt.",securedDirectDebit:"Die API-Zugangsdaten f\xFCr PAYONE Gesicherte Lastschrift sind nicht korrekt.",amazonPay:"Die API-Zugangsdaten f\xFCr PAYONE Amazon Pay sind nicht korrekt.",amazonPayExpress:"Die API-Zugangsdaten f\xFCr PAYONE Amazon Pay sind nicht korrekt."}},supportModal:{menuButton:"Support",title:"Wie k\xF6nnen wir Ihnen helfen?",documentation:{description:"Lesen Sie unsere Online-Dokumentation",button:"Dokumentation"},support:{description:"Kontaktieren Sie unseren Support",button:"Technischer Support"},repository:{description:"Melden Sie Fehler und Verbesserungen",button:"GitHub"},testdata:{description:"Erstellen Sie hier Ihre pers\xF6nlichen Testdaten",button:"Testdaten"}},applePay:{cert:{notification:`F\xFCr die Nutzung von ApplePay ist ein Zertifikat/Key-Paar zur Authentifizierung des Merchants erforderlich. Die Anlage eines solchen Zertifikats wird hier beschrieben:<br />
                            <a href="https://docs.payone.com/display/public/PLATFORM/Special+Remarks+-+Apple+Pay#SpecialRemarks-ApplePay-Onboarding" target="_blank">https://docs.payone.com/display/public/PLATFORM/Special+Remarks+-+Apple+Pay#SpecialRemarks-ApplePay-Onboarding</a><br/><br/>

                            Erstellen Sie im Anschluss unter Verwendung des folgenden Befehls eine PEM-Datei des Zertifikates:<br />
                            <pre>openssl x509 -inform der -in merchant_id.cer -out merchant_id.pem</pre><br/>
                            Hinterlegen Sie das Zertifikat <b>(merchant_id.pem)</b> und den Key <b>(merchant_id.key)</b> in folgendem Verzeichnis:<br/>
                            <pre>%shopwareRoot%/config/apple-pay-cert</pre>`}},transitionActionNames:{cancel:"Stornieren",complete:"Abschlie\xDFen",pay:"Bezahlen",pay_partially:"Teilweise bezahlen",process:"Durchf\xFChren",refund:"R\xFCckerstatten",refund_partially:"Teilweise r\xFCckerstatten",remind:"Erinnern",reopen:"Wieder \xF6ffnen",retour:"Retoure",retour_partially:"Teilweise retounieren",ship:"Versenden",ship_partially:"Teilweise versenden"},messageNotBlank:"Dieser Wert darf nicht leer sein.",error:{transaction:{notFound:"Es wurde keine passende Transaktion gefundend",orderNotFound:"Es wurde keine passende Bestellung gefundend"}}},"sw-privileges":{additional_permissions:{Payone:{label:"PAYONE",payone_order_management:"PAYONE Transaktionsmanagement"}}}};var _={"payone-payment":{title:"PAYONE",general:{mainMenuItemGeneral:"PAYONE",descriptionTextModule:"Settings for PAYONE",headlines:{ratepayProfiles:"Profile",ratepayProfileConfigurations:"Profile configuration"},label:{shopId:"Shop-ID",currency:"Currency",error:"Status",invoiceCountry:"Invoice Country",shippingCountry:"Shipping Country",minBasket:"Min. Basket",maxBasket:"Max. Basket",reloadConfigInfo:"Profile-Configuration got automatically updated during saving the plugin configuration"},actions:{addShop:"Add Shop-ID"},errors:{existingShopId:"The entered shop-id already exists.",emptyInputs:"Please fill all input fields."}},settingsForm:{save:"Save",test:"Test API Credentials",titleSuccess:"Success",titleError:"Error",labelShowSpecificStatusMapping:"Display state mapping configuration",helpTextShowSpecificStatusMapping:"If not configured the general status mapping config will be applied.",messageSaveError:{ratepayProfilesUpdateFailed:"At least one Ratepay profile could not be saved successfully, please check your configuration."},messageTestSuccess:"The API credentials were verified successfully.",messageTestNoTestedPayments:"No payment methods were tested during the check because none of the PAYONE payment methods are activated. Please activate at least one PAYONE payment method under Settings --> Shop --> Payment.",messageTestError:{general:"The API credentials could not be verified successfully.",creditCard:"The API credentials for PAYONE Credit Card are not valid.",prepayment:"The API credentials for PAYONE Prepayment are not valid.",debit:"The API credentials for PAYONE Direct Debit are not valid.",paypalExpress:"The API credentials for PAYONE PayPal Express are not valid.",paypal:"The API credentials for PAYONE PayPal are not valid.",payolutionInstallment:"The API credentials for PAYONE Unzer Ratenkauf are not valid.",payolutionInvoicing:"The API credentials for PAYONE Unzer Rechnungskauf are not valid.",payolutionDebit:"The API credentials for PAYONE Unzer Lastschrift are not valid.",sofort:"The API credentials for PAYONE Sofort are not valid.",eps:"The API credentials for PAYONE eps are not valid.",iDeal:"The API credentials for PAYONE iDEAL are not valid.",secureInvoice:"The API credentials for PAYONE Secure Invoice are not valid.",openInvoice:"The API credentials for PAYONE Invoice are not valid.",paydirekt:"The API credentials for PAYONE paydirekt are not valid.",trustly:"The API credentials for PAYONE Trustly are not valid.",applePay:"The API credentials for PAYONE Apple Pay are not valid.",bancontact:"The API credentials for PAYONE Bancontact payment are not valid.",ratepayDebit:"The API credentials for PAYONE Ratepay Direct Debit payment are not valid.",ratepayInstallment:"The API credentials for PAYONE Ratepay Installments payment are not valid.",ratepayInvoicing:"The API credentials for PAYONE Ratepay Open Invoice payment are not valid.",klarnaInvoice:"The API credentials for PAYONE Klarna Rechnung are not valid.",klarnaDirectDebit:"The API credentials for PAYONE Klarna Sofort bezahlen are not valid.",klarnaInstallment:"The API credentials for PAYONE Klarna Ratenkauf are not valid.",przelewy24:"The API credentials for PAYONE Przelewy24 are not valid.",weChatPay:"The API credentials for PAYONE WeChat Pay are not valid.",postfinanceCard:"The API credentials for PAYONE Postfinance (Card) are not valid.",postfinanceWallet:"The API credentials for PAYONE Postfinance (Wallet) are not valid.",alipay:"The API credentials for PAYONE Alipay are not valid.",securedInvoice:"The API credentials for PAYONE Secured Invoice are not valid.",securedInstallment:"The API credentials for PAYONE Secured Installment are not valid.",securedDirectDebit:"The API credentials for PAYONE Secured Direct Debit are not valid.",amazonPay:"The API credentials for PAYONE Amazon pay are not valid.",amazonPayExpress:"The API credentials for PAYONE Amazon pay are not valid."}},supportModal:{menuButton:"Support",title:"How Can We Help You?",documentation:{description:"Read our online manual",button:"Online Manual"},support:{description:"Contact our technical support",button:"Tech Support"},repository:{description:"Report errors on GitHub",button:"GitHub"},testdata:{description:"Create your personal test data here",button:"Test Data"}},applePay:{cert:{notification:`The ApplePay merchant authentication requires a certificate/key-pair. Further information:<br />
                            <a href="https://docs.payone.com/display/public/PLATFORM/Special+Remarks+-+Apple+Pay#SpecialRemarks-ApplePay-Onboarding" target="_blank">https://docs.payone.com/display/public/PLATFORM/Special+Remarks+-+Apple+Pay#SpecialRemarks-ApplePay-Onboarding</a><br/><br/>

                            Create a pem-File afterwards by using the following command:<br />
                            <pre>openssl x509 -inform der -in merchant_id.cer -out merchant_id.pem</pre><br/>
                            Copy certificate <b>(merchant_id.pem)</b> and key <b>(merchant_id.key)</b> file into the following folder:<br/>
                            <pre>%shopwareRoot%/config/apple-pay-cert</pre>`}},transitionActionNames:{cancel:"Cancel",complete:"Complete",pay:"Pay",pay_partially:"Pay partially",process:"Process",refund:"Refund",refund_partially:"Refund partially",remind:"Remind",reopen:"Reopen",retour:"Retour",retour_partially:"Retour partially",ship:"Ship",ship_partially:"Ship partially"},messageNotBlank:"This field must not be empty.",error:{transaction:{notFound:"No matching transaction could be found",orderNotFound:"No matching order could be found"}}},"sw-privileges":{additional_permissions:{Payone:{label:"PAYONE",payone_order_management:"PAYONE transaction management"}}}};var{Filter:P}=Shopware;P.register("payone_currency",(e,t,a)=>e===null?"-":(e/=100,P.getByName("currency")(e,t,a)));var{Component:Ge,Utils:k}=Shopware;Ge.extend("payone-payment-settings","sw-system-config",{inject:["PayonePaymentSettingsService"],methods:{_getShowPaymentStatusFieldsFieldName(e){return`PayonePayment.settings.${e}_show_status_mapping`},async readConfig(){this.stateMaschineOptions=await this.PayonePaymentSettingsService.getStateMachineTransitionActions().then(e=>e.data.map(t=>{let a=`payone-payment.transitionActionNames.${t.label}`,n=this.$t(a);return n===a&&(n=t.label),{id:t.value,name:n}})),await this.$super("readConfig"),this.config.forEach(e=>{let t=e.name.match(/^payment_(.*)$/),a=t?t[1]:null;a&&(this.addApiConfigurationFieldsToPaymentSettingCard(e,a),this.addPaymentStatusFieldsToPaymentSettingCard(e,a)),(e.name.startsWith("payment_")||e.name==="status_mapping")&&(e.setShowFields=n=>{e.showFields=n,e.elements.forEach(o=>{o.hidden=!n}),this.showPaymentStatusFieldsBasedOnToggle(e)},e.setShowFields(!1))})},addApiConfigurationFieldsToPaymentSettingCard(e,t){let a=["merchantId","accountId","portalId","portalKey"],n=this.config.find(s=>s.name==="basic_configuration"),o=[];n.elements.forEach(s=>{let l=s.name.match(/\.([^.]+)$/),c=l?l[1]:null;if(!c||!a.includes(c))return;let u=k.object.cloneDeep(s);u.name=s.name.replace("."+c,"."+t+(c[0].toUpperCase()+c.slice(1))),u.config.helpText={"en-GB":"The basic configuration value is used, if nothing is entered here.","de-DE":"Es wird der Wert aus der Grundeinstellung verwendet, wenn hier kein Wert eingetragen ist."},o.push(u)}),e.elements=o.concat(e.elements)},addPaymentStatusFieldsToPaymentSettingCard(e,t){e.elements.push({config:{componentName:"sw-switch-field",label:{"en-GB":"Display state mapping configuration","de-DE":"Statusmappingkonfiguration einblenden"},helpText:{"en-GB":"If not configured the general status mapping config will be applied.","de-DE":"Sie k\xF6nnen f\xFCr jede Zahlungsart ein spezifisches Statusmapping konfigurieren. Existiert eine solche Konfiguration nicht, wird auf die allgemeine Konfiguration zur\xFCckgegriffen."}},name:this._getShowPaymentStatusFieldsFieldName(e.name)}),this.config.find(n=>n.name==="status_mapping").elements.forEach(n=>{let o=k.object.cloneDeep(n);o.name=n.name.replace(".paymentStatus",`.${t}PaymentStatus`),e.elements.push(o)})},getElementBind(e,t){let a=this.$super("getElementBind",e,t);return(e.name.includes("PaymentStatus")||e.name.includes(".paymentStatus"))&&(a.config.options=this.stateMaschineOptions),a},getInheritWrapperBind(e){let t=this.$super("getInheritWrapperBind",e);return t.hidden=e.hidden,t},showPaymentStatusFieldsBasedOnToggle(e){let t=this.actualConfigData[this.currentSalesChannelId];if(!t)return;let a=t[this._getShowPaymentStatusFieldsFieldName(e.name)];e.elements.forEach(n=>{n.name.includes("PaymentStatus")&&(n.hidden=!a)})},emitConfig(){this.config.forEach(e=>this.showPaymentStatusFieldsBasedOnToggle(e)),this.$super("emitConfig")}}});Shopware.Component.register("payone-payment-plugin-icon",()=>Promise.resolve().then(()=>(C(),T)));Shopware.Component.register("payone-ratepay-profile-configurations",()=>Promise.resolve().then(()=>(D(),$)));Shopware.Component.register("payone-ratepay-profiles",()=>Promise.resolve().then(()=>(F(),R)));Shopware.Component.register("payone-settings",()=>Promise.resolve().then(()=>(z(),W)));Shopware.Module.register("payone-payment",{type:"plugin",name:"PayonePayment",title:"payone-payment.general.mainMenuItemGeneral",description:"payone-payment.general.descriptionTextModule",version:"1.0.0",targetVersion:"1.0.0",icon:"regular-cog",snippets:{"de-DE":w,"en-GB":_},routeMiddleware(e,t){e(t)},routes:{index:{component:"payone-settings",path:"index",meta:{parentPath:"sw.settings.index"}}},settingsItem:[{name:"payone-payment",to:"payone.payment.index",label:"payone-payment.general.mainMenuItemGeneral",group:"plugins",iconComponent:"payone-payment-plugin-icon",backgroundEnabled:!1}]});var G={payonePayment:{notificationTarget:{module:{title:"PAYONE Webhook-Weiterleitungen",buttonTitle:"Webhook Weiterleitungen"},list:{title:"Webhook Weiterleitungen",empty:"Keine Eintr\xE4ge",buttonCreate:"Weiterleitungsziel anlegen"},detail:{headline:"Webhook-Weiterleitung",placeholder:{url:"Url",username:"Benutzer",password:"Passwort"},label:{url:"Url",isBasicAuth:"Basic Auth",txactions:"txactions",buttonSave:"Speichern",buttonCancel:"Abbrechen",username:"Benutzer",password:"Passwort"}},columns:{url:"Url",isBasicAuth:"Basic Auth",txactions:"txactions"},actions:{requeue:"Erneut senden"},messages:{success:"Die Weiterleitung wurde erfolgreich in Auftrag gegeben.",successfullySaved:"Das Weiterleitungsziel wurde erfolgreich gespeichert."}}}};var Z={payonePayment:{notificationTarget:{module:{title:"PAYONE webhook forward",buttonTitle:"Webhook forwards"},list:{title:"Webhook forward",empty:"No entries",buttonCreate:"Add new forward target"},detail:{headline:"Webhook forward",placeholder:{url:"Url",username:"Username",password:"Password"},label:{url:"Url",isBasicAuth:"Basic Auth",txactions:"txactions",buttonSave:"Save",buttonCancel:"Cancel",username:"Username",password:"Password"}},columns:{url:"Url",isBasicAuth:"Basic Auth",txactions:"txactions"},actions:{requeue:"Requeue"},messages:{success:"The notification forward has been successfully queued.",successfullySaved:"The forwarding target has been saved successfully."}}}};Shopware.Component.register("payone-notification-target-detail",Promise.resolve().then(()=>(j(),V)));Shopware.Component.register("payone-notification-target-list",Promise.resolve().then(()=>(X(),J)));Shopware.Module.register("payone-notification-target",{type:"plugin",name:"PayoneNotificationTarget",title:"payonePayment.notificationTarget.module.title",description:"payonePayment.notificationTarget.module.title",icon:"regular-cog",snippets:{"de-DE":G,"en-GB":Z},routes:{list:{component:"payone-notification-target-list",path:"list"},detail:{component:"payone-notification-target-detail",path:"detail/:id",props:{default(e){return{notificationTargetId:e.params.id}}},meta:{parentPath:"payone.notification.target.list"}},create:{component:"payone-notification-target-detail",path:"create",meta:{parentPath:"payone.notification.target.list"}}}});Shopware.Component.register("payone-capture-button",()=>Promise.resolve().then(()=>(oe(),ie)));Shopware.Component.register("payone-order-items",()=>Promise.resolve().then(()=>(de(),ce)));Shopware.Component.register("payone-payment-management",()=>Promise.resolve().then(()=>(he(),ye)));Shopware.Component.register("payone-payment-order-action-log",()=>Promise.resolve().then(()=>(we(),be)));Shopware.Component.register("payone-payment-webhook-log",()=>Promise.resolve().then(()=>(ve(),Ae)));Shopware.Component.register("payone-refund-button",()=>Promise.resolve().then(()=>(Ee(),Ie)));Shopware.Component.register("sw-order-detail-payone",()=>Promise.resolve().then(()=>(Ne(),Le)));Shopware.Component.override("sw-order-detail",()=>Promise.resolve().then(()=>(Me(),Fe)));Shopware.Module.register("sw-order-detail-tab-payone",{routeMiddleware(e,t){t.name==="sw.order.detail"&&t.children.push({name:"sw.order.detail.payone",path:"payone",component:"sw-order-detail-payone",meta:{parentPath:"sw.order.detail",meta:{parentPath:"sw.order.index",privilege:"order.viewer"}}}),e(t)}});var{Application:qe}=Shopware,p=Shopware.Classes.ApiService,f=class extends p{constructor(t,a,n="payone"){super(t,a,n)}requeueNotificationForward(t){let a=`_action/${this.getApiBasePath()}/requeue-forward`;return this.httpClient.post(a,t,{headers:this.getBasicHeaders()}).then(n=>p.handleResponse(n))}capturePayment(t){let a=`_action/${this.getApiBasePath()}/capture-payment`;return this.httpClient.post(a,t,{headers:this.getBasicHeaders()}).then(n=>p.handleResponse(n))}refundPayment(t){let a=`_action/${this.getApiBasePath()}/refund-payment`;return this.httpClient.post(a,t,{headers:this.getBasicHeaders()}).then(n=>p.handleResponse(n))}};qe.addServiceProvider("PayonePaymentService",e=>{let t=qe.getContainer("init");return new f(t.httpClient,e.loginService)});var{Application:Be}=Shopware,g=Shopware.Classes.ApiService,b=class extends g{constructor(t,a,n="payone_payment"){super(t,a,n)}validateApiCredentials(t){let a=this.getBasicHeaders();return this.httpClient.post(`_action/${this.getApiBasePath()}/validate-api-credentials`,{credentials:t},{headers:a}).then(n=>g.handleResponse(n))}getStateMachineTransitionActions(){let t=this.getBasicHeaders();return this.httpClient.get(`_action/${this.getApiBasePath()}/get-state-machine-transition-actions`,{headers:t}).then(a=>g.handleResponse(a))}hasApplePayCert(){let t=this.getBasicHeaders();return this.httpClient.get(`_action/${this.getApiBasePath()}/check-apple-pay-cert`,{headers:t}).catch(()=>!1).then(a=>!!a)}};Be.addServiceProvider("PayonePaymentSettingsService",e=>{let t=Be.getContainer("init");return new b(t.httpClient,e.loginService)});try{Shopware.Service("privileges").addPrivilegeMappingEntry({category:"additional_permissions",parent:null,key:"Payone",roles:{payone_order_management:{privileges:["order_transaction:update","order_line_item:update","state_machine_history:create",Shopware.Service("privileges").getPrivileges("order.viewer")],dependencies:[]}}})}catch{}})();
