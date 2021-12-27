<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

class DetailsCreator
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\OrderDetails
     */
    private $orderDetailsService;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\OrderDetailsBuilder
     */
    private $orderDetailsBuilder;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\OrderDetails $orderDetailsService
     * @param \Swarming\SubscribePro\Platform\Service\OrderDetailsBuilder $orderDetailsBuilder
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\OrderDetails $orderDetailsService,
        \Swarming\SubscribePro\Platform\Service\OrderDetailsBuilder $orderDetailsBuilder,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->orderDetailsService = $orderDetailsService;
        $this->orderDetailsBuilder = $orderDetailsBuilder;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    public function createOrderDetails(OrderInterface $order): void
    {
        try {
            /** @var \SubscribePro\Service\OrderDetails\OrderDetailsInterface $orderDetails */
            $orderDetails = $this->orderDetailsService->createOrderDetails();
            $orderDetails->setOrderDetails(
                $this->orderDetailsBuilder->buildOrderDetailsData($order)
            );

            $this->orderDetailsService->saveOrderDetails($orderDetails);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
