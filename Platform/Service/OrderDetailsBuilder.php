<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Platform\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use SubscribePro\Service\OrderDetails\OrderDetailsInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class OrderDetailsBuilder
{
    /**
     * Address data fields
     */
    const FIRST_NAME = 'firstName';
    const LAST_NAME = 'lastName';
    const COMPANY = 'company';
    const STREET_1 = 'street1';
    const STREET_2 = 'street2';
    const STREET_3 = 'street3';
    const CITY = 'city';
    const REGION = 'region';
    const POSTCODE = 'postcode';
    const COUNTRY = 'country';
    const COUNTRY_NAME = 'countryName';
    const PHONE = 'phone';

    /**
     * Item data fields
     */
    const PLATFORM_ORDER_ITEM_ID = 'platformOrderItemId';
    const SKU = 'productSku';
    const SHORT_DESCRIPTION = 'shortDescription';
    const PRODUCT_NAME = 'productName';
    const QTY = 'qty';
    const UNIT_PRICE = 'unitPrice';
    const DISCOUNT_TOTAL = 'discountTotal';
    const SHIPPING_TOTAL = 'shippingTotal';
    const LINE_TOTAL = 'lineTotal';
    const REQUIRES_SHIPPING = 'requiresShipping';
    const SUBSCRIPTION_ID = 'subscriptionId';

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    private $platformCustomerManager;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildOrderDetailsData(OrderInterface $order): array
    {
        $result = [
            // customer id in Subscribe Pro
            OrderDetailsInterface::CUSTOMER_ID => $this->getPlatformCustomerId($order),
            OrderDetailsInterface::CUSTOMER_EMAIL => $order->getCustomerEmail(),
            // customer id in Magento
            OrderDetailsInterface::PLATFORM_CUSTOMER_ID => (string)$order->getCustomerId(),
            OrderDetailsInterface::PLATFORM_ORDER_ID => $order->getEntityId(),
            OrderDetailsInterface::ORDER_NUMBER => $order->getIncrementId(),
            OrderDetailsInterface::SALES_ORDER_TOKEN => $this->getSalesOrderToken($order),
            OrderDetailsInterface::ORDER_STATUS => $order->getStatus(),
            OrderDetailsInterface::ORDER_STATE => $order->getState(),
            OrderDetailsInterface::ORDER_DATE_TIME => date('c', strtotime($order->getCreatedAt())),
            OrderDetailsInterface::CURRENCY => $order->getBaseCurrencyCode(),
            OrderDetailsInterface::DISCOUNT_TOTAL => (string)abs($order->getBaseDiscountAmount()),
            OrderDetailsInterface::SHIPPING_TOTAL => (string)$order->getBaseShippingAmount(),
            OrderDetailsInterface::TAX_TOTAL => (string)$order->getBaseTaxAmount(),
            OrderDetailsInterface::ORDER_TOTAL => (string)$order->getBaseGrandTotal(),
            OrderDetailsInterface::BILLING_ADDRESS => $this->buildAddressData($order->getBillingAddress()),
            OrderDetailsInterface::ITEMS => $this->buildOrderItemData($order)
        ];
        if ($this->getShippingAddress($order)) {
            $result[OrderDetailsInterface::SHIPPING_ADDRESS] = $this->getShippingAddress($order);
        }
        return $result;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPlatformCustomerId(OrderInterface $order): string
    {
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $customerId = (int)$order->getCustomerId();
        $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId, false, $websiteId);
        return (string)$platformCustomer->getId();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    private function getSalesOrderToken(OrderInterface $order): string
    {
        $payment = $order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        return ($additionalInformation[TransactionInterface::TOKEN]) ?? '';
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function buildOrderItemData(OrderInterface $order): array
    {
        $result = [];
        /** @var \Magento\Sales\Api\Data\OrderItemInterface  $orderItem*/
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $buyRequestData = $orderItem->getProductOptionByCode('info_buyRequest');
            $subscriptionId = $buyRequestData['subscription_option']['subscription_id'] ?? null;

            $result[] = [
                self::PLATFORM_ORDER_ITEM_ID => $orderItem->getId(),
                self::SKU => $orderItem->getSku(),
                self::PRODUCT_NAME => $orderItem->getName(),
                self::SHORT_DESCRIPTION => (string)$orderItem->getDescription(),
                self::QTY => (string)$orderItem->getQtyOrdered(),
                self::UNIT_PRICE => (string)$orderItem->getBasePrice(),
                self::DISCOUNT_TOTAL => (string)$orderItem->getBaseDiscountAmount(),
                self::REQUIRES_SHIPPING => !$orderItem->getIsVirtual(),
                self::LINE_TOTAL => (string)$orderItem->getBaseRowTotal(),
                self::SUBSCRIPTION_ID => (string)$subscriptionId
            ];
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array|null
     */
    private function getShippingAddress(OrderInterface $order): ?array
    {
        return $order->getShippingAddress()
            ? $this->buildAddressData($order->getShippingAddress())
            : null;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
     * @return array
     */
    private function buildAddressData(OrderAddressInterface $orderAddress): array
    {
        return [
            self::FIRST_NAME => $orderAddress->getFirstname(),
            self::LAST_NAME => $orderAddress->getLastname(),
            self::COMPANY => $orderAddress->getCompany(),
            self::STREET_1 => $orderAddress->getStreetLine(1),
            self::STREET_2 => $orderAddress->getStreetLine(2),
            self::STREET_3 => $orderAddress->getStreetLine(3),
            self::CITY => $orderAddress->getCity(),
            self::REGION => $orderAddress->getRegionCode(),
            self::POSTCODE => $orderAddress->getPostcode(),
            self::COUNTRY => $orderAddress->getCountryId(),
            self::PHONE => $orderAddress->getTelephone(),
        ];
    }
}
