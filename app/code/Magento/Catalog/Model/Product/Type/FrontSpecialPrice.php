<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Product\Type;

use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Product\Price\SpecialPrice;
use Magento\Catalog\Api\Data\SpecialPriceInterface;

/**
 * Product special price model.
 */
class FrontSpecialPrice extends Price
{
    /**
     * @var SpecialPrice
     */
    private $specialPrice;

    /**
     * @param \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param SpecialPrice $specialPrice
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        SpecialPrice $specialPrice
    ) {
        $this->specialPrice = $specialPrice;
        parent::__construct(
            $ruleFactory,
            $storeManager,
            $localeDate,
            $customerSession,
            $eventManager,
            $priceCurrency,
            $groupManagement,
            $tierPriceFactory,
            $config
        );
    }

    /**
     * @inheritdoc
     */
    protected function _applySpecialPrice($product, $finalPrice)
    {
        $specialPrices = $this->getSpecialPrices($product);
        $specialPrice = !(empty($specialPrices)) ? min($specialPrices) : $product->getSpecialPrice();

        $specialPrice =  $this->calculateSpecialPrice(
            $finalPrice,
            $specialPrice,
            $product->getSpecialFromDate(),
            $product->getSpecialToDate(),
            $product->getStore()
        );
        $product->setData('special_price', $specialPrice);

        return $specialPrice;
    }

    /**
     * Get special prices.
     *
     * @param mixed $product
     * @return array
     */
    private function getSpecialPrices($product): array
    {
        $allSpecialPrices = $this->specialPrice->get([$product->getSku()]);
        $specialPrices = [];
        foreach ($allSpecialPrices as $price) {
            if ($this->isSuitableSpecialPrice($product, $price)) {
                $specialPrices[] = $price['value'];
            }
        }

        return $specialPrices;
    }

    /**
     * Price is suitable from default and current store + start and end date are equal.
     *
     * @param mixed $product
     * @param array $price
     * @return bool
     */
    private function isSuitableSpecialPrice($product, array $price): bool
    {
        $priceStoreId = $price[Store::STORE_ID];
        if (($priceStoreId == Store::DEFAULT_STORE_ID || $product->getStoreId() == $priceStoreId)
            && $price[SpecialPriceInterface::PRICE_FROM] == $product->getSpecialFromDate()
            && $price[SpecialPriceInterface::PRICE_TO] == $product->getSpecialToDate()) {
            return true;
        }

        return false;
    }
}
