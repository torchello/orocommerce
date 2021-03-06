define(function(require) {
    'use strict';

    var FrontendLineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var ProductPricesComponent = require('oropricing/js/app/components/product-prices-component');
    var LineItemAbstractView = require('oroorder/js/app/views/line-item-abstract-view');

    /**
     * @export oroorder/js/app/views/frontend-line-item-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.LineItemView
     */
    FrontendLineItemView = LineItemAbstractView.extend({
        /**
         * @property {jQuery}
         */
        $priceValueText: null,

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                currency: null,
                unitLoaderRouteName: 'oro_pricing_frontend_units_by_pricelist',
                selectors: {
                    priceValueText: 'div.order-line-item-price-value'
                }
            }, this.options);

            FrontendLineItemView.__super__.initialize.apply(this, arguments);

            var currencyRouteParameter = {currency: this.options.currency};
            var unitLoaderOptions = {
                routingParams: currencyRouteParameter
            };
            if (_.has(this.options, 'unitLoaderRouteName') && this.options.unitLoaderRouteName) {
                unitLoaderOptions.routeName = this.options.unitLoaderRouteName;
            }
            this.initializeUnitLoader(unitLoaderOptions);

            var $product = this.$el.find(this.options.selectors.productSelector);
            var additionalParameters = $product.data('select2_query_additional_params');
            additionalParameters = _.extend(additionalParameters, currencyRouteParameter);
            $product.data('select2_query_additional_params', additionalParameters);
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function() {
            this.$priceValueText = $(this.$el.find(this.options.selectors.priceValueText));

            FrontendLineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.subtotalFields([
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit
            ]);

            this.initPrices();
        },

        initPrices: function() {
            this.subview('productPricesComponents', new ProductPricesComponent({
                _sourceElement: this.$el,
                $product: this.fieldsByName.product,
                $priceValue: this.$priceValueText,
                $productUnit: this.fieldsByName.productUnit,
                $quantity: this.fieldsByName.quantity,
                $currency: this.options.currency,
                disabled: this.options.disabled
            }));
        },

        /**
         * @inheritDoc
         */
        resetData: function() {
            FrontendLineItemView.__super__.resetData.apply(this, arguments);

            this.$priceValueText.data('price', null);
            this.$priceValueText.html('');
        }
    });

    return FrontendLineItemView;
});
