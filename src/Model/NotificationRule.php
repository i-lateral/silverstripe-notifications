<?php

namespace ilateral\SilverStripe\Notifier\Model;

use SilverStripe\ORM\DataObject;

/**
 * @param string FieldName
 * @param string Value
 * @param bool WasChanged
 *
 * @method Notification Notification
 */
class NotificationRule extends DataObject
{
    private static $table_name = 'Notifications_NotificationRule';

    private static $db = [
        'FieldName' => 'Varchar',
        'Value' => 'Varchar',
        'WasChanged' => 'Boolean'
    ];

    private static $has_one = [
        'Notification' => Notification::class
    ];

    private static $field_labels = [
        'FieldName' => 'Field Name',
        'WasChanged' => 'Was the field changed at all',
        'Value' => 'Value is equal to'
    ];
}