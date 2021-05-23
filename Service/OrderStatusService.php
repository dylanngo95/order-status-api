<?php

declare(strict_types=1);

namespace QT\OrderStatusApi\Service;


use Magento\Sales\Api\OrderRepositoryInterface;
use QT\OrderStatusApi\Api\Data\OrderStatusInterface;
use QT\OrderStatusApi\Api\Data\OrderStatusResponseInterfaceFactory;
use QT\OrderStatusApi\Api\OrderStatusServiceInterface;
use QT\OrderStatusApi\Model\Data\OrderStatusResponse;
use QT\OrderStatusApi\Model\OrderFetcher;

/**
 * Class OrderStatusService
 * @package QT\OrderStatusApi\Service
 */
class OrderStatusService implements OrderStatusServiceInterface
{
    /**
     * @var OrderFetcher
     */
    private OrderFetcher $orderFetcher;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var OrderStatusResponseInterfaceFactory
     */
    private OrderStatusResponseInterfaceFactory $orderStatusResponseFactory;

    public function __construct(
        OrderFetcher $orderFetcher,
        OrderRepositoryInterface $orderRepository,
        OrderStatusResponseInterfaceFactory $orderStatusResponseFactory
    )
    {
        $this->orderFetcher = $orderFetcher;
        $this->orderRepository = $orderRepository;
        $this->orderStatusResponseFactory = $orderStatusResponseFactory;
    }

    /**
     * @param \QT\OrderStatusApi\Api\Data\OrderStatusInterface[] $orderStatus
     * @return \QT\OrderStatusApi\Api\Data\OrderStatusResponseInterface
     */
    public function update(array $orderStatus): \QT\OrderStatusApi\Api\Data\OrderStatusResponseInterface
    {
        $orderUpdateSuccess = [];
        $orderUpdateError = [];

        foreach ($orderStatus as $item) {
            $orderId = $item->getOrderId();
            try {
                $order = $this->orderFetcher->fetchById($orderId);
                if (!$order) {
                    $orderUpdateError[] = $orderId;
                    continue;
                }
                if ($item->getStatus()) {
                    $order->setStatus($item->getStatus());
                }
                if ($item->getState()) {
                    $order->setState($item->getState());
                }
                $order->addStatusToHistory(
                    $order->getStatus(), 'Status update from integration'
                );
                $this->orderRepository->save($order);
                $orderUpdateSuccess[] = $orderId;
            } catch (\Exception $e) {
                $orderUpdateError[] = $orderId;
            }
        }

        /** @var OrderStatusResponse $orderStatusResponse */
        $orderStatusResponse = $this->orderStatusResponseFactory->create();
        $orderStatusResponse->setOrderIdUpdateSuccess($orderUpdateSuccess);
        $orderStatusResponse->setOrderIdUpdateError($orderUpdateError);

        return $orderStatusResponse;
    }
}
