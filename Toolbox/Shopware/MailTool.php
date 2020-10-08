<?php

namespace MxcCommons\Toolbox\Shopware;

use Enlight_Components_Mail;
use MxcCommons\MxcCommons;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Bundle\MailBundle\Service\LogEntryBuilder;
use Shopware\Models\Order\Order;
use Zend_Mime;
use Zend_Mime_Part;

class MailTool implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use ServicesAwareTrait;

    private $documentTypes = [
        'invoice'       => 1,
        'delivery_note' => 2,
        'credit'        => 3,
        'cancellation'  => 4,
    ];

    /** @var DocumentRenderer */
    protected $documentRenderer;

    public function __construct(DocumentRenderer $documentRenderer)
    {
        $this->documentRenderer = $documentRenderer;
    }

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

    public function attachOrderDocument(Enlight_Components_Mail $mail, Order $order, string $type, bool $forceCreate = false)
    {
        $this->documentRenderer->createDocument($order, 'invoice', $forceCreate);
        $mail->addAttachment($this->createAttachment($order, 'invoice'));
    }

    public function sendStatusMail(Enlight_Components_Mail $mail)
    {
        $orderManager = Shopware()->Modules()->Order();
        $orderManager->sendStatusMail($mail);
    }

    public function getDocumentPath(Order $order, string $type)
    {
        $sql = "SELECT hash FROM s_order_documents WHERE orderID=? AND type=? ORDER BY date DESC LIMIT 1";
        $hash = Shopware()->Db()->fetchOne($sql, [$order->getId(), $this->documentTypes[$type]]);
        return sprintf('%sfiles/documents/%s.pdf', Shopware()->DocPath(), $hash);
    }

    public function createAttachment(Order $order, string $type)
    {
        $content = file_get_contents($this->getDocumentPath($order, $type));
        $attachment = new Zend_Mime_Part($content);
        $attachment->type = 'application/pdf';
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Zend_Mime::ENCODING_BASE64;
        $attachment->filename = $this->getFileName($order->getId(), $this->documentTypes[$type]);

        return $attachment;
    }

    private function getFileName($orderId, $typeId, $fileExtension = '.pdf')
    {
        $localeId = $this->getOrderLocaleId($orderId);

        $translationReader = $this->services->get('translation');
        $translations = $translationReader->read($localeId, 'documents', $typeId, true);

        if (empty($translations) || empty($translations['name'])) {
            return $this->getDefaultName($typeId) . $fileExtension;
        }

        return $translations['name'] . $fileExtension;
    }

    private function getOrderLocaleId($orderId)
    {
        $queryBuilder = $this->services->get('dbal_connection')->createQueryBuilder();

        return $queryBuilder->select('language')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()
            ->fetchColumn();
    }

    private function getDefaultName($typeId)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->services->get('dbal_connection')->createQueryBuilder();

        return $queryBuilder->select('name')
            ->from('s_core_documents')
            ->where('`id` = :typeId')
            ->setParameter('typeId', $typeId)
            ->execute()
            ->fetchColumn();
    }
}