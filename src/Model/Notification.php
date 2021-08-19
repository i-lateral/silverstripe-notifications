<?php

namespace ilateral\SilverStripe\Notifier\Model;

use ilateral\SilverStripe\Notifier\Types\NotificationType;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * @param string BaseClass
 * @param bool StateCreated
 * @param bool StateUpdated
 * @param bool StateDeleted
 *
 * @method HasManyList Rules
 * @method HasManyList Types
 */
class Notification extends DataObject
{
    const STATE_CREATED = 'StateCreated';

    const STATE_UPDATED = 'StateUpdated';

    const STATE_DELETED = 'StateDeleted';

    private static $table_name = 'Notifications_Notification';

    private static $db = [
        'BaseClass' => 'Varchar',
        'StateCreated' => 'Boolean',
        'StateUpdated' => 'Boolean',
        'StateDeleted' => 'Boolean'
    ];

    private static $has_many = [
        'Rules' => NotificationRule::class,
        'Types' => NotificationType::class
    ];
}
