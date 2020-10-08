<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Models\Order\Order;
use Shopware_Components_Document;
use DateTime;

class DocumentRenderer implements AugmentedObject
{
    use ServicesAwareTrait;
    use ModelManagerAwareTrait;

    private $documentTypes = [
        'invoice'       => 1,
        'delivery_note' => 2,
        'credit'        => 3,
        'cancellation'  => 4,
    ];

    public function createDocument(Order $order, string $type, bool $forceCreation = false, DateTime $date = null, DateTime $deliveryDate = null)
    {
        $typeId = $this->documentTypes[$type];

        /** @var \Shopware\Models\Order\Document\Document[] $documents */
        $documents = $order->getDocuments();

        if ($forceCreation) {
            $this->renderDocument($order, $type, $date, $deliveryDate);
            return;
        }

        $alreadyCreated = false;
        foreach ($documents as $document) {
            if ($document->getTypeId() === $typeId) {
                $alreadyCreated = true;
                break;
            }
        }
        if ($alreadyCreated === false) {
            $this->renderDocument($order, $type, $date, $deliveryDate);
        }
    }

    public function renderDocument(Order $order, string $type, DateTime $date = null, DateTime $deliveryDate = null)
    {
        if ($date === null) {
            $date = date('d.m.Y');
        }
        if ($deliveryDate === null) {
            $deliveryDate = date('d.m.Y');
        }

        $type = $this->documentTypes[$type];

        $document = Shopware_Components_Document::initDocument($order->getId(), $type,
            [
                'netto'                   => $order->getTaxFree(),
                'date'                    => $date,
                'delivery_date'           => $deliveryDate,
                'shippingCostsAsPosition' => (int) $type !== 2,
                '_renderer'               => 'pdf',
                '_preview'                => false,
                'docComment'              => 'Automatically created.',
            ]
        );
        $document->render();
    }
}