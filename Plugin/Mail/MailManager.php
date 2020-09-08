<?php

namespace MxcCommons\Plugin\Mail;

use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Config\Config;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Mail\Mail;
use Shopware\Models\Order\Status;

class MailManager implements AugmentedObject
{
    use ModelManagerAwareTrait;

    CONST MODE_ALL = 0;
    const MODE_STATUS = 1;

    protected $config;
    protected $repository;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function install(InstallContext $context)
    {
        foreach ($this->config as $templateDefinition) {
            $this->installMailTemplate($templateDefinition);
        }
        $this->modelManager->flush();
    }

    // get the configuration data of a mail template
    //
    //  param $mail    string|Mail
    //
    public function getMailTemplate($mail, bool $ignoreContext = true)
    {
        if (empty($mail)) return null;
        if (is_string($mail)) {
            $mail = $this->getRepository()->findOneBy(['name' => $mail]);
            if ($mail === null)  return null;
        }
        if (! $mail instanceof Mail) return null;

        $template = [
            'name'         => $mail->getName(),
            'type'         => $mail->getMailtype(),
            'is_html'      => $mail->isHtml(),
            'content_text' => $mail->getContent(),
            'content_html' => $mail->getContentHtml(),
            'from_mail'    => $mail->getFromMail(),
            'from_name'    => $mail->getFromName(),
            'subject'      => $mail->getSubject(),
            'context'      => $mail->getContext(),
        ];
        $status = $mail->getStatus();
        if ($status) {
            $template['status'] = $status->getId();
        }
        if ($ignoreContext) {
            unset($template['context']);
        }
        return $template;
    }

    public function _getMailTemplates(int $mode, bool $ignoreContext = true)
    {
        $mails = $this->getRepository()->findAll();
        $templates = null;
        foreach ($mails as $mail) {
            if ($mode === self::MODE_STATUS && ! $mail->getStatus()) continue;
            $name = $mail->getName();
            $template = $this->getMailTemplate($mail, $ignoreContext);
            if ($template !== null) {
                $templates[$name] = $template;
            }
        }
        return $templates;
    }


    public function getStatusMailTemplates(bool $ignoreContext = true)
    {
        return $this->_getMailTemplates(self::MODE_STATUS, $ignoreContext);
    }

    public function getMailTemplates(bool $ignoreContext = true)
    {
        return $this->_getMailTemplates(self::MODE_ALL, $ignoreContext);
    }

    public function setMailTemplates(array $templates)
    {
        foreach ($templates as $templateDefinition) {
            $this->installMailTemplate($templateDefinition);
        }
        $this->modelManager->flush();
    }

    public function uninstall(UninstallContext $context)
    {
        if ($context->keepUserData()) {
            return true;
        }

        foreach ($this->config as $templateDefinition) {
            $name = @$templateDefinition['name'];
            if (empty($name)) {
                continue;
            }
            $mail = $this->getRepository()->findOneBy(['name' => $name]);
            if ($mail !== null) {
                $this->modelManager->remove($mail);
            }
        }
        $this->modelManager->flush();
    }

    protected function installMailTemplate($templateDefinition)
    {
        $name = @$templateDefinition['name'];
        if (empty($name)) return;

        $mail = $this->getRepository()->findOneBy(['name' => $name]);
        if (!$mail) {
            $mail = new Mail();
            $this->modelManager->persist($mail);
            $mail->setName($name);
        }
        $setting = @$templateDefinition['from_mail'] ?? '{config name=mail}';
        $mail->setFromMail($setting);
        $setting = @$templateDefinition['from_name'] ?? '{config name=shopName}';
        $mail->setFromName($setting);
        $setting = @$templateDefinition['subject'] ?? @$templateDefinition['name'];
        $mail->setSubject($setting);
        $setting = @$templateDefinition['content_text'] ?? '';
        $mail->setContent($setting);
        $setting = @$templateDefinition['content_html'] ?? '';
        $mail->setContentHtml($setting);
        $setting = @$templateDefinition['is_html'] ?? false;
        $mail->setIsHtml($setting);
        $setting = @$templateDefinition['type'] ?? Mail::MAILTYPE_USER;
        $mail->setMailType($setting);
        $setting = @$templateDefinition['context'] ?? null;
        $mail->setContext($setting);
        $statusId = @$templateDefinition['status'] ?? null;
        if ($statusId !== null) {
            /** @var Status $status */
            $status = $this->modelManager->getRepository(Status::class)->find($statusId);
            // note: status will be null, if Status object does not exist
            $mail->setStatus([$status]);
        }
    }

    protected function getRepository()
    {
        return $this->repository ?? $this->repository = $this->modelManager->getRepository(Mail::class);
    }
}