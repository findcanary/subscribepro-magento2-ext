<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class TokenAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var string
     */
    private $paymentMethodCode = '';

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    private $logger;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param string $paymentMethodCode
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Psr\Log\LoggerInterface $logger,
        string $paymentMethodCode = ''
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->logger = $logger;
        $this->paymentMethodCode = $paymentMethodCode;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $paymentProfileId = $additionalData['profile_id'] ?? null;
        $this->logger->debug('paymentProfileId: ' . $paymentProfileId);
        if (empty($paymentProfileId)) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Payment $paymentModel */
        $paymentModel = $this->readPaymentModelArgument($observer);
        $this->logger->debug('InstanceOf: ' . ($paymentModel instanceof QuotePayment));
        if (!$paymentModel instanceof QuotePayment) {
            return;
        }

        $quote = $paymentModel->getQuote();
        $customerId = $quote->getCustomer()->getId();
        $this->logger->debug('customerId: ' . $customerId);
        if ($customerId === null) {
            return;
        }
        $this->logger->debug('getting stuff in PaymentTokenManagement: ' . $paymentProfileId . '/' . $this->paymentMethodCode . '/' .  $customerId);
        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $paymentProfileId,
            $this->paymentMethodCode,
            $customerId
        );
        $this->logger->debug('paymentToken: ' . $paymentToken);
        if ($paymentToken === null) {
            return;
        }

        $paymentModel->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $customerId);
        $paymentModel->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $paymentToken->getPublicHash());

        if (!empty($additionalData[TransactionInterface::UNIQUE_ID])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::UNIQUE_ID,
                $additionalData[TransactionInterface::UNIQUE_ID]
            );
        }

        if (!empty($additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN,
                $additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN]
            );
        }
    }
}
