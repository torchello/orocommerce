<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Formatter\UnitValueFormatter;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class FrontendProductPriceDatagridListener
{
    const COLUMN_PRICES = 'prices';
    const COLUMN_MINIMUM_PRICE = 'minimum_price';
    const JOIN_ALIAS_PRICE = '_min_product_price';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @var UnitLabelFormatter
     */
    protected $unitLabelFormatter;

    /**
     * @var UnitValueFormatter
     */
    protected $unitValueFormatter;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var CombinedPriceList
     */
    protected $priceList;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param NumberFormatter $numberFormatter
     * @param UnitLabelFormatter $unitLabelFormatter
     * @param UnitValueFormatter $unitValueFormatter
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        NumberFormatter $numberFormatter,
        UnitLabelFormatter $unitLabelFormatter,
        UnitValueFormatter $unitValueFormatter,
        UserCurrencyManager $currencyManager
    ) {
        $this->translator = $translator;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->numberFormatter = $numberFormatter;
        $this->unitLabelFormatter = $unitLabelFormatter;
        $this->unitValueFormatter = $unitValueFormatter;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        if (count($records) === 0) {
            return;
        }

        $priceList = $this->getPriceList();
        if (!$priceList) {
            return;
        }

        $productIds = array_map(
            function (ResultRecord $record) {
                return $record->getValue('id');
            },
            $records
        );

        $currency = $this->currencyManager->getUserCurrency();

        $em = $event->getQuery()->getEntityManager();
        /** @var CombinedProductPriceRepository $repository */
        $repository = $em->getRepository('OroPricingBundle:CombinedProductPrice');
        $combinedPrices = $repository->getPricesForProductsByPriceList($priceList, $productIds, $currency);

        $resultProductPrices = [];
        foreach ($combinedPrices as $price) {
            $index = sprintf('%s_%s', $price->getProductUnitCode(), $price->getQuantity());

            $productId = $price->getProduct()->getId();
            if (isset($resultProductPrices[$productId][$index])) {
                continue;
            }

            $resultProductPrices[$productId][$index] = $this->prepareResultPrice($price);
        }

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $resultProductPrices)) {
                $record->addData([self::COLUMN_PRICES => $resultProductPrices[$productId]]);
            } else {
                $record->addData([self::COLUMN_PRICES => []]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $currency = $this->currencyManager->getUserCurrency();
        if (!$currency) {
            return;
        }

        $this->addConfigProductPriceJoin($config, $currency);

        $selectPattern = '%s.value as %s';
        $select = sprintf($selectPattern, self::JOIN_ALIAS_PRICE, self::COLUMN_MINIMUM_PRICE);

        $config->offsetAddToArrayByPath('[source][query][select]', [$select]);
        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_PRICES => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                self::COLUMN_MINIMUM_PRICE=> [
                    'label' => $this->translator->trans(
                        'oro.pricing.productprice.price_in_%currency%',
                        ['%currency%' => $currency]
                    )
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[sorters][columns]',
            [
                self::COLUMN_MINIMUM_PRICE =>[
                    'data_name' => self::COLUMN_MINIMUM_PRICE,
                    'type' => PropertyInterface::TYPE_CURRENCY,
                ]
            ]
        );
        $config->offsetAddToArrayByPath(
            '[filters][columns]',
            [
                self::COLUMN_MINIMUM_PRICE => [
                    'type' => 'frontend-product-price',
                    'data_name' => $currency
                ]
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     */
    protected function addConfigProductPriceJoin(DatagridConfiguration $config, $currency)
    {
        $joinAlias = self::JOIN_ALIAS_PRICE;
        $priceList = $this->getPriceList();
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())));

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => 'OroPricingBundle:MinimalProductPrice',
                    'alias' => $joinAlias,
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$joinExpr,
                ]
            ]
        );
    }

    /**
     * @param CombinedProductPrice $price
     * @return array
     */
    protected function prepareResultPrice(CombinedProductPrice $price)
    {
        $priceValue = $price->getPrice()->getValue();
        $unitCode = $price->getUnit()->getCode();
        $quantity = $price->getQuantity();
        $currencyIsoCode = $price->getPrice()->getCurrency();

        $resultPrices = [
            'price' => $priceValue,
            'currency' => $currencyIsoCode,
            'formatted_price' => $this->numberFormatter->formatCurrency($priceValue, $currencyIsoCode),
            'unit' => $unitCode,
            'formatted_unit' => $this->unitLabelFormatter->format($unitCode),
            'quantity' => $quantity,
            'quantity_with_unit' => $this->unitValueFormatter->formatCode($quantity, $unitCode)
        ];

        return $resultPrices;
    }

    /**
     * @return CombinedPriceList
     */
    protected function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->priceListRequestHandler->getPriceListByAccount();
        }

        return $this->priceList;
    }
}
