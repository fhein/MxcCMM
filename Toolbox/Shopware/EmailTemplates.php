<?php

namespace MxcCommons\Toolbox\Shopware;

use Shopware\Models\Mail\Mail;

class EmailTemplates
{
    public static function getAll()
    {
        $models = Shopware()->Container()->get('models');
        $mails = $models->getRepository(Mail::class)->findAll();

        $templates = [];
        /** @var Mail $mail */
        foreach ($mails as $mail) {
            $attr = [
                'type'     => $mail->getMailtype(),
                'isHtml'   => $mail->isHtml(),
                'content'  => $mail->getContent(),
                'html'     => $mail->getContentHtml(),
                'fromMail' => $mail->getFromMail(),
                'fromName' => $mail->getFromName(),
                'subject'  => $mail->getSubject(),
                // @todo: Should we ignore context??
                'context'  => $mail->getContext(),
            ];
            $templates[$mail->getName()] = $attr;
        }
        return $templates;
    }

    public static function setAll(array $templates, bool $replace = false) {
        $models = Shopware()->Container()->get('models');
        $mailRepository = $models->getRepository(Mail::class);

        foreach ($templates as $name => $config) {
            /** @var Mail $mail */
            $mail = $mailRepository->findOneBy([ 'name' => $name]);
            if ($mail === null) {
                $mail = new Mail();
                $models->persist($mail);
            }
            $mail->setMailtype($config['type']);
            $mail->setIsHtml($config['isHtml']);
            $mail->setContent($config['content']);
            $mail->setContentHtml($config['html']);
            $mail->setFromMail($config['fromMail']);
            $mail->setFromName($config['fromName']);
            $mail->setSubject($config['subject']);
            $mail->setContext($config['context']);
        }
        $models->flush();
    }

}