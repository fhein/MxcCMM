<?php

namespace MxcCommons\Toolbox\Shopware;

use Enlight_Components_Mail;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use Shopware\Bundle\MailBundle\Service\LogEntryBuilder;
use Shopware\Models\Order\Order;

class MailTool implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    public function renderStatusMail(Order $order, int $statusId = null)
    {
        $mail = null;
        $orderManager = Shopware()->Modules()->Order();
        $statusId = $statusId ?? $order->getOrderStatus()->getId();
        $orderId = $order->getId();
        if ($statusId > 0) {
            $mail = $orderManager->createStatusMail($orderId, $statusId);
            $mail->setAssociation(LogEntryBuilder::ORDER_ID_ASSOCIATION, $order->getId());
        }
        return $mail;
    }

    public function sendStatusMail(Enlight_Components_Mail $mail)
    {
        $orderManager = Shopware()->Modules()->Order();
        $orderManager->sendStatusMail($mail);
    }
}