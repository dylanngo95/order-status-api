<?php

declare(strict_types=1);

namespace QT\OrderStatusApi\Service;

use Magento\Sales\Api\OrderRepositoryInterface;
use QT\OrderStatusApi\Api\Data\OrderStatusResponseInterfaceFactory;
use QT\OrderStatusApi\Api\OrderStatusServiceInterface;
use QT\OrderStatusApi\Model\Data\OrderStatusResponse;
use QT\OrderStatusApi\Model\OrderFetcher;
use QT\OrderStatusApi\Model\ResourceModel\OrderStatus\Collection as OrderStatusCollection;
use QT\OrderStatusApi\Model\ResourceModel\OrderState\Collection as OrderStateCollection;

/**
 * Class OrderStatusService
 * @package QT\OrderStatusApi\Service
 */
class OrderStatusService implements OrderStatusServiceInterface
{
    const MAX_PAGE_SIZE = 1000;

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

    /**
     * @var OrderStatusCollection
     */
    private OrderStatusCollection $orderStatusCollection;

    /**
     * @var OrderStateCollection
     */
    private OrderStateCollection $orderStateCollection;

    public function __construct(
        OrderFetcher $orderFetcher,
        OrderRepositoryInterface $orderRepository,
        OrderStatusResponseInterfaceFactory $orderStatusResponseFactory,
        OrderStatusCollection $orderStatusCollection,
        OrderStateCollection $orderStateCollection
    ) {
        $this->orderFetcher = $orderFetcher;
        $this->orderRepository = $orderRepository;
        $this->orderStatusResponseFactory = $orderStatusResponseFactory;
        $this->orderStatusCollection = $orderStatusCollection;
        $this->orderStateCollection = $orderStateCollection;
    }

    /**
     * @param \QT\OrderStatusApi\Api\Data\OrderStatusRequestInterface[] $orderStatus
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
                    $order->getStatus(),
                    'Status update from integration'
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

    /**
     * @return \QT\OrderStatusApi\Api\Data\OrderStatusInterface[]|null
     */
    public function getList(): array
    {
        return $this->orderStatusCollection
            ->setPageSize(self::MAX_PAGE_SIZE)
            ->getItems();
    }

    /**
     * @return \QT\OrderStatusApi\Api\Data\OrderStateInterface[]|null
     */
    public function getStateList(): array
    {
        return $this->orderStateCollection
            ->setPageSize(self::MAX_PAGE_SIZE)
            ->getItems();
    }
}
