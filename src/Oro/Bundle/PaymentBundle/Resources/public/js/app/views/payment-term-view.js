define(function(require) {
    'use strict';

    var PaymentTermView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oropayment/js/app/views/payment-term-view
     * @extends oroui.app.views.base.View
     * @class oropayment.app.views.PaymentTermView
     */
    PaymentTermView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectionTemplate: ''
        },

        /**
         * @property {jQuery}
         */
        $input: null,

        /**
         * @property {jQuery}
         */
        inputChanged: false,

        /**
         * @property {number|null}
         */
        accountPaymentTerm: null,

        /**
         * @property {number|null}
         */
        accountGroupPaymentTerm: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$input = this.$el.find('[data-name="field__payment-term"]');
            this.selectionTemplate = _.template(this.options.selectionTemplate);

            this.configureInput();

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            var self = this;

            this.$input.change(function() {
                self.inputChanged = true;
            });
        },

        configureInput: function() {
            var self = this;

            this.accountPaymentTerm = this.parseInt(this.$input.data('account-payment-term'));
            this.accountGroupPaymentTerm = this.parseInt(this.$input.data('account-group-payment-term'));

            var configs = this.$input.data('pageComponentOptions').configs;
            configs.selection_template = configs.result_template = function(data) {
                data.isAccountDefault = data.id === self.accountPaymentTerm;
                data.isAccountGroupDefault = data.id === self.accountGroupPaymentTerm;

                return self.selectionTemplate(data);
            };
        },

        parseInt: function(val) {
            return val ? parseInt(val, 10) : null;
        },

        /**
         * Set payment term value from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {

            this.accountPaymentTerm = this.parseInt(response.accountPaymentTerm || null);
            this.accountGroupPaymentTerm = this.parseInt(response.accountGroupPaymentTerm || null);

            var paymentTermKeys = ['accountPaymentTerm', 'accountGroupPaymentTerm'];
            var paymentTerm;
            for (var i = 0, iMax = paymentTermKeys.length; i < iMax; i++) {
                paymentTerm = response[paymentTermKeys[i]] || null;
                if (paymentTerm) {
                    break;
                }
            }

            if (!paymentTerm || this.inputChanged) {
                paymentTerm = this.$input.val();
            }

            this.$input.inputWidget('val', paymentTerm);
        }
    });

    return PaymentTermView;
});
