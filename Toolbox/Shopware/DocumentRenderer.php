<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware_Components_Document;
use DateTime;

class DocumentRenderer implements AugmentedObject
{
    use DatabaseAwareTrait;

    const DOC_TYPE_INVOICE       = 1;
    const DOC_TYPE_DELIVERY_NOTE = 2;
    const DOC_TYPE_CREDIT        = 3;
    const DOC_TYPE_CANCELLATION  = 4;

    public function createDocument(int $orderId, int $typeId, bool $forceCreation = false, DateTime $date = null, DateTime $deliveryDate = null)
    {
        if ($forceCreation) {
            $this->renderDocument($orderId, $typeId, $date, $deliveryDate);
            return;
        }
        $hasDocument = $this->db->fetchOne(
            'SELECT count(id) FROM s_order_documents WHERE orderID = :orderId AND type = :typeId',
            [ 'orderId' => $orderId, 'typeId' => $typeId]);
        if ($hasDocument) return;
        $this->renderDocument($orderId, $typeId, $date, $deliveryDate);
    }

    public function renderDocument(int $orderId, int $typeId, DateTime $date = null, DateTime $deliveryDate = null)
    {
        if ($date === null) {
            $date = date('d.m.Y');
        }
        if ($deliveryDate === null) {
            $deliveryDate = date('d.m.Y');
        }

        $document = Shopware_Components_Document::initDocument($orderId, $typeId,
            [
                'netto'                   => false,
                'date'                    => $date,
                'delivery_date'           => $deliveryDate,
                'shippingCostsAsPosition' => (int) $typeId !== 2,
                '_renderer'               => 'pdf',
                '_preview'                => false,
                'docComment'              => 'Automatically created.',
            ]
        );
        $document->render();
    }
}