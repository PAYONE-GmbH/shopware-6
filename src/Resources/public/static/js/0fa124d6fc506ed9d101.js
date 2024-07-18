"use strict";(window["webpackJsonpPluginpayone-payment"]=window["webpackJsonpPluginpayone-payment"]||[]).push([[301],{301:function(t,n,e){e.r(n),e.d(n,{default:function(){return i}});let{Mixin:a}=Shopware;var i={template:'{% block payone_notification_target_detail %}\n    <sw-page class="payone-notification-target-detail">\n\n        {% block payone_notification_target_detail_header %}\n            <template #smart-bar-header>\n                <h2>{{ $tc(\'payonePayment.notificationTarget.detail.headline\') }}</h2>\n            </template>\n        {% endblock %}\n\n        {% block payone_notification_target_detail_actions %}\n            <template #smart-bar-actions>\n\n                {% block payone_notification_target_detail_actions_abort %}\n                    <sw-button :disabled="notificationTargetIsLoading" @click="onCancel">\n                        {{ $tc(\'payonePayment.notificationTarget.detail.label.buttonCancel\') }}\n                    </sw-button>\n                {% endblock %}\n\n                {% block payone_notification_target_detail_actions_save %}\n                    <sw-button-process\n                        class="payone-notification-target-detail__save-action"\n                        :isLoading="isLoading"\n                        v-model="isSaveSuccessful"\n                        :disabled="isLoading"\n                        variant="primary"\n                        :process-success="processSuccess"\n                        @click.prevent="onSave">\n                        {{ $tc(\'payonePayment.notificationTarget.detail.label.buttonSave\') }}\n                    </sw-button-process>\n                {% endblock %}\n\n            </template>\n        {% endblock %}\n\n        <template #content>\n            {% block payone_notification_target_detail_content %}\n                <sw-card-view>\n\n                    {% block payone_notification_target_detail_base_basic_info_card %}\n                        <sw-card position-identifier="payone-notification-target-detail-content"\n                                 :title="$tc(\'payonePayment.notificationTarget.detail.headline\')"\n                                 :isLoading="notificationTargetIsLoading">\n                            <template v-if="!notificationTargetIsLoading">\n                                <sw-container class="payone-notification-target-detail__container"\n                                              columns="repeat(auto-fit, minmax(250px, 1fr))"\n                                              gap="0 30px">\n                                    <div class="payone-notification-target-detail__base-info-wrapper">\n\n                                        {% block payone_notification_target_detail_base_info_field_url %}\n                                            <sw-text-field\n                                                      :label="$tc(\'payonePayment.notificationTarget.detail.label.url\')"\n                                                      :placeholder="$tc(\'payonePayment.notificationTarget.detail.placeholder.url\')"\n                                                      name="url"\n                                                      validation="required"\n                                                      required\n                                                      v-model:value="notificationTarget.url">\n                                            </sw-text-field>\n                                        {% endblock %}\n\n                                        {% block payone_notification_target_detail_base_info_field_is_basic_auth %}\n                                            <sw-checkbox-field :label="$tc(\'payonePayment.notificationTarget.detail.label.isBasicAuth\')"\n                                                      name="isBasicAuth"\n                                                      v-model:value="notificationTarget.isBasicAuth">\n                                            </sw-checkbox-field>\n                                        {% endblock %}\n\n                                        {% block payone_notification_target_detail_base_info_field_is_basic_auth_fields %}\n                                            <sw-text-field v-if="notificationTarget.isBasicAuth"\n                                                      :label="$tc(\'payonePayment.notificationTarget.detail.label.username\')"\n                                                      :placeholder="$tc(\'payonePayment.notificationTarget.detail.placeholder.username\')"\n                                                      name="username"\n                                                      required\n                                                      v-model:value="notificationTarget.username">\n                                            </sw-text-field>\n\n                                            <sw-password-field v-if="notificationTarget.isBasicAuth"\n                                                      :label="$tc(\'payonePayment.notificationTarget.detail.label.password\')"\n                                                      :placeholder="$tc(\'payonePayment.notificationTarget.detail.placeholder.password\')"\n                                                      name="password"\n                                                      required\n                                                      v-model:value="notificationTarget.password">\n                                            </sw-password-field>\n                                        {% endblock %}\n\n                                        {% block payone_notification_target_detail_base_info_field_txactions %}\n                                            <sw-multi-select\n                                                :label="$tc(\'payonePayment.notificationTarget.detail.label.txactions\')"\n                                                :options="[\n                                                    { value: \'appointed\', label: \'appointed\' },\n                                                    { value: \'capture\', label: \'capture\' },\n                                                    { value: \'paid\', label: \'paid\' },\n                                                    { value: \'underpaid\', label: \'underpaid\' },\n                                                    { value: \'cancelation\', label: \'cancelation\' },\n                                                    { value: \'refund\', label: \'refund\' },\n                                                    { value: \'debit\', label: \'debit\' },\n                                                    { value: \'transfer\', label: \'transfer\' },\n                                                    { value: \'reminder\', label: \'reminder\' },\n                                                    { value: \'vauthorization\', label: \'vauthorization\' },\n                                                    { value: \'vsettlement\', label: \'vsettlement\' },\n                                                    { value: \'invoice\', label: \'invoice\' },\n                                                    { value: \'failed\', label: \'failed\' }\n                                                ]"\n                                                v-model:value="notificationTarget.txactions">\n                                            </sw-multi-select>\n                                        {% endblock %}\n\n                                    </div>\n                                </sw-container>\n                            </template>\n                        </sw-card>\n                    {% endblock %}\n                </sw-card-view>\n            {% endblock %}\n        </template>\n\n    </sw-page>\n{% endblock %}\n',inject:["repositoryFactory"],mixins:[a.getByName("notification")],shortcuts:{"SYSTEMKEY+S":"onSave",ESCAPE:"onCancel"},props:{notificationTargetId:{type:String,required:!1,default:null}},data(){return{notificationTarget:null,isLoading:!1,isSaveSuccessful:!1,processSuccess:!1}},metaInfo(){return{title:this.$createTitle(this.identifier)}},computed:{notificationTargetIsLoading(){return this.isLoading||null==this.notificationTarget},notificationTargetRepository(){return this.repositoryFactory.create("payone_payment_notification_target")}},watch:{notificationTargetId(){this.createdComponent()}},created(){this.createdComponent()},methods:{createdComponent(){if(this.notificationTargetId){this.loadEntityData();return}Shopware.State.commit("context/resetLanguageToDefault"),this.notificationTarget=this.notificationTargetRepository.create(Shopware.Context.api)},loadEntityData(){this.isLoading=!0,this.notificationTargetRepository.get(this.notificationTargetId,Shopware.Context.api).then(t=>{this.isLoading=!1,this.notificationTarget=t,null!==t.txactions&&(t.txactions.length||(this.notificationTarget.txactions=null))})},isInvalid(){return!0===this.notificationTarget.isBasicAuth&&(!this.notificationTarget.username||!this.notificationTarget.password)&&(this.createNotificationError({message:this.$tc("global.notification.notificationSaveErrorMessageRequiredFieldsInvalid")}),!0)},onSave(){this.isInvalid()||(this.isLoading=!0,this.notificationTargetRepository.save(this.notificationTarget,Shopware.Context.api).then(()=>{if(this.isLoading=!1,this.isSaveSuccessful=!0,null===this.notificationTargetId){this.$router.push({name:"payone.notification.target.detail",params:{id:this.notificationTarget.id}});return}this.loadEntityData()}).catch(t=>{throw this.isLoading=!1,this.createNotificationError({message:this.$tc("global.notification.notificationSaveErrorMessageRequiredFieldsInvalid")}),t}))},onCancel(){this.$router.push({name:"payone.notification.target.list"})}}}}}]);