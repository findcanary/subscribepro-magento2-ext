<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\OrderDetails\OrderDetailsInterface;

/**
 * @method \SubscribePro\Service\OrderDetails\OrderDetailsService getService($websiteId = null)
 */
class OrderDetails extends AbstractService
{
    /**
     * @param array $orderDetails
     * @param int|null $websiteId
     * @return \SubscribePro\Service\DataInterface
     */
    public function createOrderDetails(array $orderDetailsData = [], $websiteId = null): OrderDetailsInterface
    {
        return $this->getService($websiteId)->createOrderDetails($orderDetailsData);
    }

    /**
     * @param \SubscribePro\Service\OrderDetails\OrderDetailsInterface $orderDetails
     * @param int|null $websiteId
     * @return \SubscribePro\Service\DataInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveOrderDetails(OrderDetailsInterface $orderDetails, $websiteId = null): OrderDetailsInterface
    {
        return $this->getService($websiteId)->saveNewOrderDetails($orderDetails);
    }
}
