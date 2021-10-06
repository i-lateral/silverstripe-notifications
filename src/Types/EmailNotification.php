<?php

namespace ilateral\SilverStripe\Notifier\Types;

use SilverStripe\Control\Email\Email;

/**
 * Simple wrapper for SilverStripe email notoifications
 */
class EmailNotification extends NotificationType
{
    private static $table_name = "Notifications_EmailNotification";

    private static $singular_name = 'Email Notification';

    private static $plural_name = 'Email Notifications';

    private static $template = self::class;

    private static $db = [
        'Subject' => 'Varchar'
    ];

    private static $casting = [
        'RenderedSubject' => 'Varchar'
    ];

    /**
     * Return a rendered version of this notification's subject using the
     * current object as a base
     *
     * @return string
     */
    public function getRenderedSubject(): string
    {
        return $this->renderString((string) $this->Subject);
    }

    public function send(array $custom_recipients = [])
    {
        $recipients = array_merge(
            $this->getRecipients(),
            $custom_recipients
        );

        $from = $this->getSender();

        if (empty($from)) {
            $from =  Email::config()->admin_email;
        }

        foreach ($recipients as $recipient) {
            // If recipient is blank for some reason
            // then skip sending
            $recipient = trim($recipient);

            if (empty($recipient)) {
                continue;
            }

            $email = Email::create();
            $email
                ->setTo($recipient)
                ->setFrom($from)
                ->setSubject($this->getRenderedSubject())
                ->setData([
                    'Content' => $this->getRenderedContent(),
                ])->setHTMLTemplate($this->config()->template)
                ->send();
        }
        
        return;
    }
}
