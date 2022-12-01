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
use LogicException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;

/**
 * @property string BaseClassName
 * @property bool StateCreated
 * @property bool StateUpdated
 * @property bool StateDeleted
 * @property string NotificationName
 * @property string ObjectType
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

    private static $cascade_deletes = [
        'Rules',
        'Types'
    ];

    private static $casting = [
        'NotificationName' => 'Varchar',
        'ObjectType' => 'Varchar',
        'Summary' => 'Varchar'
    ];

    private static $field_labels = [
        'NotificationName' => 'Notification',
        'ObjectType' => 'Object to monitor',
        'BaseClassName' => 'Object to Monitor',
        'StateCreated' => 'Notify when created',
        'StateUpdated' => 'Notify on any update',
        'StateDeleted' => 'Notify when deleted',
        'Rules' => 'Modification Rules',
        'Rules.Count' => '# of Rules',
        'Types' => 'Notification Types'
    ];

    private static $summary_fields = [
        'NotificationName',
        'ObjectType',
        'Summary',
        'Rules.Count'
    ];

    /**
     * Add to this list of classnames to disallow particular rules
     * from being selectable
     *
     * @var array[string]
     */
    private static $disallow_rules = [];


    /**
     * Add to this list of classnames to disallow particular types
     * from being selectable
     *
     * @var array[string]
     */
    private static $disallow_types = [
        NotificationType::class
    ];

    public function getNotificationName(): string
    {
        return $this->i18n_singular_name();
    }

    public function getObjectType(): string
    {
        $class = $this->BaseClassName;

        if (!empty($class) && class_exists($class)) {
            return singleton($class)->i18n_singular_name();
        }

        return "";
    }

    public function getAllowedRules(): array
    {
        return $this->getAllowed(NotificationRule::class, 'disallow_rules');
    }

    public function isRuleAllowed(string $classname): bool
    {
        $rules = $this->getAllowedRules();
        return in_array($classname, $rules);
    }

    public function getAllowedTypes(): array
    {
        return $this->getAllowed(NotificationType::class, 'disallow_types');
    }

    public function isTypeAllowed(string $classname): bool
    {
        $types = $this->getAllowedTypes();
        return in_array($classname, $types);
    }

    /**
     * Attempt to generate a summary of this notification
     * and its rules
     *
     * @return string
     */
    public function getSummary(): string
    {
        $results = [];

        if ($this->StateCreated == true) {
            $results[] = _t(__CLASS__ . '.OnCreated', 'On Created');
        }

        if ($this->StateUpdated == true) {
            $results[] = _t(__CLASS__ . '.AnyUpdate', 'Any Update');
        }

        if ($this->StateDeleted == true) {
            $results[] =  _t(__CLASS__ . '.OnDeleted', 'On Deleted');
        }

        foreach ($this->Rules() as $rule) {
            /** @var NotificationRule $rule */
            $results[] = $rule->Summary;
        }

        foreach ($this->Types() as $type) {
            /** @var NotificationType $type */
            $results[] = $type->Summary;
        }

        return implode('; ', $results);
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
                    $classes = $this->getAllowedRules();
                    
                    $rules = new GridFieldAddNewMultiClass("buttons-before-right");
                    $rules->setClasses($classes);

                    $config
                        ->removeComponentsByType(GridFieldAddNewButton::class)
                        ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
                        ->addComponent($rules);

                    $rules_field->setConfig($config);

                    $fields->addFieldToTab('Root.Main', $rules_field);
                }

                /** @var GridField */
                $types_field = $fields->dataFieldByName('Types');

                if (!empty($types_field)) {
                    $config = $types_field->getConfig();
                    $classes = $this->getAllowedTypes();

                    $type = new GridFieldAddNewMultiClass("buttons-before-right");
                    $type->setClasses($classes);

                    $config
                        ->removeComponentsByType(GridFieldAddNewButton::class)
                        ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
                        ->addComponent($type);
                    
                    $types_field->setConfig($config);

                    $fields->addFieldToTab('Root.Main', $types_field);
                }
            }
        );

        return parent::getCMSFields();
    }

    /**
     * Return a list of allowed (rules or types) for this 
     */
    protected function getAllowed(string $classname, string $setting): array
    {
        $possible = ClassInfo::subclassesFor($classname, true);
        $disallow = Config::inst()->get(self::class, $setting);

        if (!is_array($disallow)) {
            throw new LogicException('Disallow Types must be an array');
        }

        $classes = [];
        
        foreach (array_values($possible) as $class) {
            if (!in_array($class, $disallow)) {
                $classes[] = $class;
            }
        }
            
        return $classes;
    }
}
