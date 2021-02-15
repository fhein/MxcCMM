<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderTool implements AugmentedObject
{
    use DatabaseAwareTrait;
    use ModelManagerAwareTrait;

    public function getOrdersByOrderStatus(int $statusId)
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order o 
            LEFT JOIN 
                s_order_attributes oa ON oa.orderID = o.id 
            WHERE 
                o.status = :orderStatus 
        ', [
            'orderStatus' => $statusId,
        ]);
    }

    public function getOrdersByPaymentStatus(int $statusId)
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order o 
            LEFT JOIN 
                s_order_attributes oa ON oa.orderID = o.id 
            WHERE 
                o.cleared = :paymentStatus 
        ', [
            'paymentStatus' => $statusId,
        ]);
    }

    public function getOrdersByStatus(int $statusId)
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order o 
            LEFT JOIN 
                s_order_attributes oa ON oa.orderID = o.id 
            WHERE 
                o.cleared = :status OR o.status = :status 
        ', [
            'status' => $statusId,
        ]);
    }

    public function getOrderIdsByOrderStatus(int $statusId)
    {
        return $this->db->fetchCol(
            'SELECT o.id FROM s_order o WHERE o.status = :orderStatus',
            [ 'orderStatus' => $statusId,]
        );
    }

    public function getOrderIdsByPaymentStatus(int $statusId)
    {
        return $this->db->fetchCol(
            'SELECT o.id FROM s_order o WHERE o.cleared = :paymentStatus',
            [ 'paymentStatus' => $statusId,]
        );
    }

    public function getOrderIdsByStatus(int $statusId)
    {
        return $this->db->fetchCol(
            'SELECT o.id FROM s_order o WHERE o.cleared = :status OR o.status = :status',
            [ 'status' => $statusId,]
        );
    }


    public function getOrder(int $orderId)
    {
        return $this->db->fetchRow(
            'SELECT * FROM s_order o LEFT JOIN s_order_attributes oa ON oa.orderID = o.id WHERE o.id = :orderId',
            ['orderId' => $orderId]
        );
    }

    public function getOrderAttributes(int $orderId)
    {
        return $this->db->fetchRow('SELECT * FROM s_order_attributes oa WHERE oa.orderID = :orderId',
            [ 'orderId' => $orderId,]
        );
    }

    public function getOrderDetails(int $orderId)
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order_details od 
            LEFT JOIN 
                s_order_details_attributes oda ON oda.detailID = od.id 
            WHERE 
                od.orderID = :orderId 
        ', [
            'orderId' => $orderId,
        ]);
    }

    public function getOrderDetailAttributes(int $detailId)
    {
        return $this->db->fetchRow('SELECT * FROM s_order_details_attributes oda WHERE oda.detailID = :detailId',
            ['detailId' => $detailId]
        );
    }

    public function setOrderStatusDoctrine(int $orderId, int $statusId)
    {
        $order = $this->modelManager->getRepository(Order::class)->find($orderId);
        $status = $this->modelManager->getReference(Status::class, $statusId);
        $order->setOrderStatus($status);
        $this->modelManager->flush($order);
    }

    public function setOrderStatus(int $orderId, int $statusId)
    {
        $this->db->executeUpdate(
            'UPDATE s_order o SET o.status = :status WHERE o.id = :id',
            ['status' => $statusId, 'id' => $orderId]
        );
    }

    public function setPaymentStatusDoctrine(int $orderId, int $statusId)
    {
        $order = $this->modelManager->getRepository(Order::class)->find($orderId);
        $status = $this->modelManager->getReference(Status::class, $statusId);
        $order->setPaymentStatus($status);
        $this->modelManager->flush($order);
    }

    public function setPaymentStatus(int $orderId, int $statusId)
    {
        $this->db->executeUpdate(
            'UPDATE s_order o SET o.cleared = :status WHERE o.id = :id',
            ['status' => $statusId, 'id' => $orderId]
        );
    }

    public function isPrepayment(int $paymentId)
    {
        $payment = $this->db->fetchRow('SELECT * FROM s_core_paymentmeans p WHERE p.id = :paymentId',
            ['paymentId' => $paymentId]
        );
        return $payment['name'] == 'prepayment';
    }

    public function isPaypal(int $paymentId)
    {
        $payment = $this->db->fetchRow('SELECT * FROM s_core_paymentmeans p WHERE p.id = :paymentId',
            ['paymentId' => $paymentId]
        );
        return $payment['name'] == 'SwagPaymentPayPalUnified';
    }
}