import template from './payone-notification-target-detail.html.twig';

const { Mixin } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel'
    },

    props: {
        notificationTargetId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            notificationTarget: null,
            isLoading: false,
            isSaveSuccessful: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier)
        };
    },

    computed: {
        notificationTargetIsLoading() {
            return this.isLoading || this.notificationTarget == null;
        },

        notificationTargetRepository() {
            return this.repositoryFactory.create('payone_payment_notification_target');
        },

        resendNotificationOptionValues() {
            return [
                {value: 100, label: '(Http status = 100) Continue'},
                {value: 101, label: '(Http status = 101) Switching Protocols'},
                {value: 102, label: '(Http status = 102) Processing'},            // RFC2518
                {value: 103, label: '(Http status = 103) Early Hints'},
                {value: 200, label: '(Http status = 200) OK'},
                {value: 201, label: '(Http status = 201) Created'},
                {value: 202, label: '(Http status = 202) Accepted'},
                {value: 203, label: '(Http status = 203) Non-Authoritative Information'},
                {value: 204, label: '(Http status = 204) No Content'},
                {value: 205, label: '(Http status = 205) Reset Content'},
                {value: 206, label: '(Http status = 206) Partial Content'},
                {value: 207, label: '(Http status = 207) Multi-Status'},          // RFC4918
                {value: 208, label: '(Http status = 208) Already Reported'},      // RFC5842
                {value: 226, label: '(Http status = 226) IM Used'},               // RFC3229
                {value: 300, label: '(Http status = 300) Multiple Choices'},
                {value: 301, label: '(Http status = 301) Moved Permanently'},
                {value: 302, label: '(Http status = 302) Found'},
                {value: 303, label: '(Http status = 303) See Other'},
                {value: 304, label: '(Http status = 304) Not Modified'},
                {value: 305, label: '(Http status = 305) Use Proxy'},
                {value: 307, label: '(Http status = 307) Temporary Redirect'},
                {value: 308, label: '(Http status = 308) Permanent Redirect'},    // RFC7238
                {value: 400, label: '(Http status = 400) Bad Request'},
                {value: 401, label: '(Http status = 401) Unauthorized'},
                {value: 402, label: '(Http status = 402) Payment Required'},
                {value: 403, label: '(Http status = 403) Forbidden'},
                {value: 404, label: '(Http status = 404) Not Found'},
                {value: 405, label: '(Http status = 405) Method Not Allowed'},
                {value: 406, label: '(Http status = 406) Not Acceptable'},
                {value: 407, label: '(Http status = 407) Proxy Authentication Required'},
                {value: 408, label: '(Http status = 408) Request Timeout'},
                {value: 409, label: '(Http status = 409) Conflict'},
                {value: 410, label: '(Http status = 410) Gone'},
                {value: 411, label: '(Http status = 411) Length Required'},
                {value: 412, label: '(Http status = 412) Precondition Failed'},
                {value: 413, label: '(Http status = 413) Content Too Large'},                                           // RFC-ietf-httpbis-semantics
                {value: 414, label: '(Http status = 414) URI Too Long'},
                {value: 415, label: '(Http status = 415) Unsupported Media Type'},
                {value: 416, label: '(Http status = 416) Range Not Satisfiable'},
                {value: 417, label: '(Http status = 417) Expectation Failed'},
                {value: 418, label: '(Http status = 418) I\'m a teapot'},                                               // RFC2324
                {value: 421, label: '(Http status = 421) Misdirected Request'},                                         // RFC7540
                {value: 422, label: '(Http status = 422) Unprocessable Content'},                                       // RFC-ietf-httpbis-semantics
                {value: 423, label: '(Http status = 423) Locked'},                                                      // RFC4918
                {value: 424, label: '(Http status = 424) Failed Dependency'},                                           // RFC4918
                {value: 425, label: '(Http status = 425) Too Early'},                                                   // RFC-ietf-httpbis-replay-04
                {value: 426, label: '(Http status = 426) Upgrade Required'},                                            // RFC2817
                {value: 428, label: '(Http status = 428) Precondition Required'},                                       // RFC6585
                {value: 429, label: '(Http status = 429) Too Many Requests'},                                           // RFC6585
                {value: 431, label: '(Http status = 431) Request Header Fields Too Large'},                             // RFC6585
                {value: 451, label: '(Http status = 451) Unavailable For Legal Reasons'},                               // RFC7725
                {value: 500, label: '(Http status = 500) Internal Server Error'},
                {value: 501, label: '(Http status = 501) Not Implemented'},
                {value: 502, label: '(Http status = 502) Bad Gateway'},
                {value: 503, label: '(Http status = 503) Service Unavailable'},
                {value: 504, label: '(Http status = 504) Gateway Timeout'},
                {value: 505, label: '(Http status = 505) HTTP Version Not Supported'},
                {value: 506, label: '(Http status = 506) Variant Also Negotiates'},                                     // RFC2295
                {value: 507, label: '(Http status = 507) Insufficient Storage'},                                        // RFC4918
                {value: 508, label: '(Http status = 508) Loop Detected'},                                               // RFC5842
                {value: 510, label: '(Http status = 510) Not Extended'},                                                // RFC2774
                {value: 511, label: '(Http status = 511) Network Authentication Required'}
            ]
        }
    },

    watch: {
        notificationTargetId() {
            this.createdComponent();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        updateSelection(value) {
            this.notificationTarget.txactions = value;
        },

        updateResendNotificationTime(value) {
            this.notificationTarget.resendNotificationTime = value;
        },

        updateResendNotificationStatus(value) {
            this.notificationTarget.resendNotificationStatus = value;
        },

        createdComponent() {
            if(this.notificationTargetId) {
                this.loadEntityData();
                return;
            }

            Shopware.State.commit('context/resetLanguageToDefault');
            this.notificationTarget = this.notificationTargetRepository.create(Shopware.Context.api);
        },

        loadEntityData() {
            this.isLoading = true;

            this.notificationTargetRepository.get(this.notificationTargetId, Shopware.Context.api).then((notificationTarget) => {
                this.isLoading = false;

                this.notificationTarget = notificationTarget;

                if(null === notificationTarget.txactions) {
                    return;
                }

                if(!notificationTarget.txactions.length) {
                    this.notificationTarget.txactions = null;
                }
            });
        },

        isInvalid() {
            let errorMessages = [];
            if(this.notificationTarget.isBasicAuth === true) {
                if(!this.notificationTarget.username || !this.notificationTarget.password) {
                    errorMessages.push(this.$tc('payonePayment.notificationTarget.detail.messages.notificationSaveErrorMessageRequiredUserPassInvalid'));
                }
            }

            if(this.notificationTarget.resendNotification === true) {
                if(this.notificationTarget.resendNotificationTime.length === 0) {
                    errorMessages.push(this.$tc('payonePayment.notificationTarget.detail.messages.notificationSaveErrorMessageRequiredResendNotificationTimeInvalid'));
                }
                if(this.notificationTarget.resendNotificationStatus.length === 0) {
                    errorMessages.push(this.$tc('payonePayment.notificationTarget.detail.messages.notificationSaveErrorMessageRequiredResendNotificationStatusInvalid'));
                }
            }

            if(errorMessages.length > 0) {
                errorMessages.forEach((message) => {
                    this.createNotificationError({
                        message: message
                    });
                });

                return true;
            }
            return false;
        },

        onSave() {
            if(this.isInvalid()) {
                return;
            }

            this.isLoading = true;

            this.notificationTargetRepository.save(this.notificationTarget, Shopware.Context.api).then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
                if (this.notificationTargetId === null) {
                    this.$router.push({ name: 'payone.notification.target.detail', params: { id: this.notificationTarget.id } });
                    return;
                }

                this.loadEntityData();
            }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    message: this.$tc(
                        'global.notification.notificationSaveErrorMessageRequiredFieldsInvalid'
                    )
                });
                throw exception;
            });
        },

        onCancel() {
            this.$router.push({ name: 'payone.notification.target.list' });
        },

        getTimeValueTranslate(value, type) {
            if(value === null || value === undefined) {
                return '';
            }

            if(type === 'minutes') {
                return this.$tc('payonePayment.notificationTarget.detail.resendNotificationTimeValues.xMinutes', 0, {value: value})
            }

            if(type === 'hours') {
                return this.$tc('payonePayment.notificationTarget.detail.resendNotificationTimeValues.xHours', 0, {value: value})
            }
        }
    }
};
