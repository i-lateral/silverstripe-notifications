<?php

namespace ilateral\SilverStripe\Notifier\Model;

use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use ilateral\SilverStripe\Notifier\Notifier;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use ilateral\SilverStripe\Notifier\Types\NotificationType;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;

/**
 * @param string BaseClassName
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
        'BaseClassName' => 'Varchar',
        'StateCreated' => 'Boolean',
        'StateUpdated' => 'Boolean',
        'StateDeleted' => 'Boolean'
    ];

    private static $has_many = [
        'Rules' => NotificationRule::class,
        'Types' => NotificationType::class
    ];

    private static $field_labels = [
        'ObjectType' => 'Object to monitor',
        'BaseClassName' => 'Object to Monitor',
        'StateCreated' => 'Notify when created',
        'StateUpdated' => 'Notify when updated',
        'StateDeleted' => 'Notify when deleted',
        'Rules' => 'Modification Rules',
        'Rules.Count' => '# of Rules',
        'Types' => 'Notification Types'
    ];

    private static $summary_fields = [
        'ObjectType',
        'StateCreated',
        'StateUpdated',
        'StateDeleted',
        'Rules.Count'
    ];

    public function getObjectType(): string
    {
        $class = $this->BaseClassName;

        if (!empty($class) && class_exists($class)) {
            return singleton($class)->i18n_singular_name();
        }

        return "";
    }

    public function getCMSFields()
    {
        $self = $this;

        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                /** @var FieldList $fields */
                $fields->replaceField(
                    'BaseClassName',
                    DropdownField::create(
                        'BaseClassName',
                        $self->fieldLabel('BaseClassName'),
                        Notifier::getRegisteredObjectsArray()
                    )
                );

                /** @var GridField */
                $rules_field = $fields->dataFieldByName('Rules');

                if (!empty($rules_field)) {
                    $config = $rules_field->getConfig();

                    $fields->addFieldToTab('Root.Main', $rules_field);
                }

                /** @var GridField */
                $types_field = $fields->dataFieldByName('Types');

                if (!empty($types_field)) {
                    $config = $types_field->getConfig();
                    $classes = array_values(
                        ClassInfo::subclassesFor(
                            NotificationType::class,
                            false
                        )
                    );
                    $type = new GridFieldAddNewMultiClass();
                    $type->setClasses($classes);

                    $config
                        ->removeComponentsByType(GridFieldAddNewButton::class)
                        ->addComponent($type);
                    
                    $types_field->setConfig($config);

                    $fields->addFieldToTab('Root.Main', $types_field);
                }
            }
        );

        return parent::getCMSFields();
    }
}
