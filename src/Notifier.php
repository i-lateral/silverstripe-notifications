<?php

namespace ilateral\SilverStripe\Notifier;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use ilateral\SilverStripe\Notifier\Model\Notification;

class Notifier
{
    use Injectable, Configurable;

    /**
     * Register data objects for notification and also
     * stipulate which notifications they can recieve.
     *
     * @var array
     */
    private static $registered_objects = [];

    /**
     * Process and send a list of notifications based on their provided type
     */
    public static function processNotifications(SS_List $notifications, DataObject $object)
    {
        /** @var Notification $notification */
        foreach ($notifications as $notification) {
            foreach ($notification->Types() as $notification_type) {                
                $notification_type->send($object);
            }
        }
    }
}
