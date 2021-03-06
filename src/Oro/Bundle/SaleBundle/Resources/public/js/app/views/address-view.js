define(function(require) {
    'use strict';

    var AddressView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orosale/js/app/views/address-view
     * @extends oroui.app.views.base.View
     * @class orosale.app.views.AddressView
     */
    AddressView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            enterManuallyValue: '0',
            type: '',
            selectors: {
                address: '',
                subtotalsFields: []
            }
        },

        events: {
            'click [name="oro_sale_quote[shippingAddress][accountAddress]"]': 'addressFormChange',
        },

        /**
         * @property {String}
         */
        ftid: '',

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {jQuery}
         */
        $address: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            this.loadingMaskView = new LoadingMaskView({container: this.$el});

            mediator.on('quote:load:related-data', this.loadingStart, this);
            mediator.on('quote:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.ftid = this.$el.find('div[data-ftid]:first').data('ftid');

            this.setAddress(this.$el.find(this.options.selectors.address));

            this.$fields = this.$el.find(':input[data-ftid]').filter(':not(' + this.options.selectors.address + ')');
            this.fieldsByName = {};
        },

        /**
         * Loading form after on select address click
         */
        addressFormChange: function() {
            var self = this;
            this.$fields.each(function() {
                var $field = $(this);
                var name = self.normalizeName($field.data('ftid').replace(self.ftid + '_', ''));
                self.fieldsByName[name] = $field;
            });

            this.accountAddressChange();
        },

        /**
         * Convert name with "_" to name with upper case, example: some_name > someName
         *
         * @param {String} name
         *
         * @returns {String}
         */
        normalizeName: function(name) {
            name = name.split('_');
            for (var i = 1, iMax = name.length; i < iMax; i++) {
                name[i] = name[i][0].toUpperCase() + name[i].substr(1);
            }
            return name.join('');
        },

        /**
         * Set new address element and bind events
         *
         * @param {jQuery} $address
         */
        setAddress: function($address) {
            this.$address = $address;

            var self = this;
            this.$address.change(function(e) {
                self.accountAddressChange(e);
            });
        },

        /**
         * Implement account address change logic
         */
        accountAddressChange: function() {
            if (this.$address.val() !== this.options.enterManuallyValue) {
                this.$fields.each(function() {
                    var $field = $(this);

                    $field.prop('readonly', true).inputWidget('refresh');
                });

                var address = this.$address.data('addresses')[this.$address.val()] || null;
                if (address) {
                    var self = this;

                    _.each(address, function(value, name) {
                        if (_.isObject(value)) {
                            value = _.first(_.values(value));
                        }
                        var $field = self.fieldsByName[self.normalizeName(name)] || null;
                        if ($field) {
                            $field.val(value);
                            if ($field.data('select2')) {
                                $field.data('selected-data', value).change();
                            }
                        }
                    });
                }
            } else {
                this.$fields.each(function() {
                    var $field = $(this);

                    $field.prop('readonly', false).inputWidget('refresh');
                });
            }
        },

        /**
         * Show loading view
         */
        loadingStart: function() {
            this.loadingMaskView.show();
        },

        /**
         * Hide loading view
         */
        loadingEnd: function() {
            this.loadingMaskView.hide();
        },

        /**
         * Set account address choices from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {
            var address = response[this.options.type + 'Address'] || null;
            if (!address) {
                this.loadingEnd();
                return;
            }

            var $oldAddress = this.$address;
            this.setAddress($(address));
            this.$fields.each(function() {
                var $field = $(this);
                $field.val('');
                $field.prop('readonly', true).inputWidget('refresh');
                if ($field.data('select2')) {
                    $field.data('selected-data', '').change();
                }
            });
            $oldAddress.parent().trigger('content:remove');
            $oldAddress.inputWidget('dispose');
            $oldAddress.replaceWith(this.$address);

            this.initLayout().done(_.bind(this.loadingEnd, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:load:related-data', this.loadingStart, this);
            mediator.off('order:loaded:related-data', this.loadedRelatedData, this);

            AddressView.__super__.dispose.call(this);
        }
    });

    return AddressView;
});
