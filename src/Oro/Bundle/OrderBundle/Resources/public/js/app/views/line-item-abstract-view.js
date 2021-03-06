define(function(require) {
    'use strict';

    var LineItemAbstractView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var ProductUnitComponent = require('oroproduct/js/app/components/product-unit-component');

    /**
     * @export oroorder/js/app/views/line-item-abstract-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.LineItemAbstractView
     */
    LineItemAbstractView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                productSelector: '.order-line-item-type-product [data-name="field__product"]',
                quantitySelector: '.order-line-item-quantity input',
                unitSelector: '.order-line-item-quantity select',
                productSku: '.order-line-item-sku .order-line-item-type-product'
            },
            disabled: false,
            freeFormUnits: null
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {Object}
         */
        fieldsByName: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
            this.delegate('click', '.removeLineItem', this.removeRow);
        },

        /**
         * Initialize unit loader component
         *
         * @param {Object} options
         */
        initializeUnitLoader: function(options) {
            var defaultOptions = {
                _sourceElement: this.$el,
                productSelector: this.options.selectors.productSelector,
                quantitySelector: this.options.selectors.quantitySelector,
                unitSelector: this.options.selectors.unitSelector,
                loadingMaskEnabled: false,
                dropQuantityOnLoad: false,
                defaultValues: this.options.freeFormUnits
            };

            this.subview('productUnitComponent', new ProductUnitComponent(_.extend({}, defaultOptions, options || {})));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            this.initProduct();
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
        },

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            _.each($fields, function(field) {
                $(field).attr('data-entry-point-trigger', true);
            });

            mediator.trigger('entry-point:order:init');
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();

            mediator.trigger('entry-point:order:trigger');
        },

        resetData: function() {
            if (this.fieldsByName.hasOwnProperty('quantity')) {
                this.fieldsByName.quantity.val(1);
            }
        },

        initProduct: function() {
            if (this.fieldsByName.product) {
                this.fieldsByName.product.change(_.bind(function() {
                    this.resetData();

                    var data = this.fieldsByName.product.inputWidget('data') || {};
                    this.$el.find(this.options.selectors.productSku).html(data.sku || null);
                }, this));
            }
        }
    });

    return LineItemAbstractView;
});
