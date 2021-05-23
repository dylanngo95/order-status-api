<?php

declare(strict_types=1);

namespace QT\OrderStatusApi\Api;


/**
 * Interface OrderStatusServiceInterface
 * @package QT\OrderStatusApi\Api
 */
interface OrderStatusServiceInterface
{
    /**
     * @param \QT\OrderStatusApi\Api\Data\OrderStatusInterface[] $orderStatus
     * @return \QT\OrderStatusApi\Api\Data\OrderStatusResponseInterface
     */
    public function update(array $orderStatus): \QT\OrderStatusApi\Api\Data\OrderStatusResponseInterface;
}
