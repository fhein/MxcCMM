<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;

class OrderTool implements AugmentedObject
{
    use DatabaseAwareTrait;

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

    public function getOrderIdsByStatus(int $statusId)
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

    public function setOrderStatus(int $orderId, int $statusId)
    {
        $this->db->executeUpdate(
            'UPDATE s_order o SET o.status = :status WHERE o.id = :id',
            ['status' => $statusId, 'id' => $orderId]
        );
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
}