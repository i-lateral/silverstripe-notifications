<?php

namespace ilateral\SilverStripe\Notifier;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use ilateral\SilverStripe\Notifier\Model\Notification;
use ilateral\SilverStripe\Notifier\Types\NotificationType;
use SilverStripe\Core\Injector\Injector;

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
     * Generate a list of reguistered objects with the object's
     * short name as value
     */
    public static function getRegisteredObjectsArray(): array
    {
        $list = self::config()->get('registered_objects');
        $return = [];

        foreach ($list as $classname) {
            /** @var DataObject */
            $obj = Injector::inst()->get($classname);
            $return[$classname] = $obj->i18n_singular_name();
        }

        return $return;
    }

    /**
     * Process and send a list of notifications based on their provided type
     */
    public static function processNotifications(SS_List $notifications, DataObject $object)
    {
        /** @var Notification $notification */
        foreach ($notifications as $notification) {
            foreach ($notification->Types() as $notification_type) {
                /** @var NotificationType $notification_type  */
                $notification_type->setObject($object);
                $notification_type->send();
            }
        }
    }
}
