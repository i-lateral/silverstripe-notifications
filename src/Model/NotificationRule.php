<?php

namespace ilateral\SilverStripe\Notifier\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use ilateral\SilverStripe\Notifier\Model\Notification;
use SilverStripe\Forms\DropdownField;

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

    /**
     * Get a list of valid field names and their labels
     *
     * @return array
     */
    public function getValidFields(): array
    {
        $fields = [];
        $class = $this->Notification()->BaseClassName;

        if (!empty($class) && class_exists($class)) {
            $obj = singleton($class);

            foreach ($obj::config()->get('db') as $field => $type) {
                $fields[$field] = $obj->fieldLabel($field);
            }
        }

        return $fields;
    }

    public function getCMSFields()
    {
        $self = $this;
        $this->beforeUpdateCMSFields(
            function (FieldList $fields) use ($self) {
                $fields->replaceField(
                    'FieldName',
                    DropdownField::create(
                        'FieldName',
                        $this->fieldLabel('FieldName'),
                        $this->getValidFields()
                    )
                );
            }
        );

        return parent::getCMSFields();
    }
}
