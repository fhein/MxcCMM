<?php

namespace MxcCommons\Toolbox\Shopware;

use Enlight_Components_Mail;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use Shopware\Bundle\MailBundle\Service\LogEntryBuilder;
use Zend_Mime;
use Zend_Mime_Part;

class MailTool implements AugmentedObject
{
    use DatabaseAwareTrait;
    use ServicesAwareTrait;

    /** @var DocumentRenderer */
    protected $documentRenderer;

    public function __construct(DocumentRenderer $documentRenderer)
    {
        $this->documentRenderer = $documentRenderer;
    }

    public function sendStatusMail(int $orderId, int $statusId, array $documentAttachments = [])
    {
        $mail = $this->renderStatusMail($orderId, $statusId);
        foreach ($documentAttachments as $typeId) {
            $this->attachOrderDocument($mail, $orderId, $typeId);
        }
        $this->swSendStatusMail($mail);
    }

    public function sendNotificationMail(int $orderId, array $context, array $addresses, array $documentAttachments = [])
    {
        $mail = Shopware()->TemplateMail()->createMail($context['mailTemplate'], $context);
        foreach ($documentAttachments as $typeId) {
            $this->attachOrderDocument($mail, $orderId, $typeId);
        }
        foreach ($addresses['to'] as $to) {
            $mail->addTo($to);
        }
        $mail->clearFrom();
        $from = $addresses['from'];
        $mail->setFrom($from['email'], $from['name']);
        if (isset($context['mailSubject'])) {
            $mail->clearSubject();
            $mail->setSubject($context['mailSubject']);
        }
        $mail->send();
    }

    public function renderStatusMail(int $orderId, int $statusId)
    {
        $mail = null;
        $orderManager = Shopware()->Modules()->Order();
        if ($statusId > 0) {
            $mail = $orderManager->createStatusMail($orderId, $statusId);
            $mail->setAssociation(LogEntryBuilder::ORDER_ID_ASSOCIATION, $orderId);
        }
        return $mail;
    }

    public function attachOrderDocument(Enlight_Components_Mail $mail, int $orderId, int $typeId, bool $forceCreate = false)
    {
        $this->documentRenderer->createDocument($orderId, $typeId, $forceCreate);
        $mail->addAttachment($this->createAttachment($orderId, $typeId));
    }

    public function swSendStatusMail(Enlight_Components_Mail $mail)
    {
        $orderManager = Shopware()->Modules()->Order();
        return $orderManager->sendStatusMail($mail);
    }

    public function getDocumentPath(int $orderId, int $typeId)
    {
        $sql = "SELECT hash FROM s_order_documents WHERE orderID=? AND type=? ORDER BY date DESC LIMIT 1";
        $hash = Shopware()->Db()->fetchOne($sql, [$orderId, $typeId]);
        return sprintf('%sfiles/documents/%s.pdf', Shopware()->DocPath(), $hash);
    }

    public function createAttachment(int $orderId, int $typeId)
    {
        $content = file_get_contents($this->getDocumentPath($orderId, $typeId));
        $attachment = new Zend_Mime_Part($content);
        $attachment->type = 'application/pdf';
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Zend_Mime::ENCODING_BASE64;
        $attachment->filename = $this->getFileName($orderId, $typeId);

        return $attachment;
    }

    private function getFileName(int $orderId, int $typeId, $fileExtension = '.pdf')
    {
        $localeId = $this->getOrderLocaleId($orderId);

        $translationReader = $this->services->get('translation');
        $translations = $translationReader->read($localeId, 'documents', $typeId, true);

        if (empty($translations) || empty($translations['name'])) {
            return $this->getDefaultName($typeId) . $fileExtension;
        }

        return $translations['name'] . $fileExtension;
    }

    private function getOrderLocaleId(int $orderId)
    {
        return $this->db->fetchOne('SELECT language FROM s_order WHERE id = :orderId', ['orderId' => $orderId]);
    }

    private function getDefaultName(int $typeId)
    {
        return $this->db->fetchOne('SELECT name FROM s_core_documents WHERE id = :typeId', ['typeId' => $typeId]);
    }
}