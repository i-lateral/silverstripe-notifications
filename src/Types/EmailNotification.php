<?php

namespace ilateral\SilverStripe\Notifier\Types;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

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

    public function send($custom_recipient = null)
    {
        $recipient = (empty($custom_recipent)) ? $this->Recipient : $custom_recipient;
        $from = (empty($this->From)) ? Email::config()->admin_email : $this->From;

        $email = Email::create();
        $email
            ->setTo($recipient)
            ->setFrom($from)
            ->setSubject($this->Subject)
            ->setData([
                'Content' => $this->Content,
            ])->setHTMLTemplate($this->config()->template)
            ->send();
        
        return;
    }
}
