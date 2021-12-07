<?php

namespace ilateral\SilverStripe\Notifier\Types;

use LogicException;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\SSViewer;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\ViewableData;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Forms\ToggleCompositeField;
use ilateral\SilverStripe\Notifier\Model\Notification;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\HTML;

/**
 * Base Object for sending notifications
 *
 * @property string From
 * @property string AltFrom
 * @property string Recipient
 * @property string AltRecipient
 * @property string Content
 * @property string Type
 * @property string RenderedContent
 * 
 * @method Notification Notification
 */
class NotificationType extends DataObject
{
    private static $table_name = 'Notifications_NotificationType';

    /**
     * Template used for rendering this notification
     *
     * @var string
     */
    private static $template;

    /**
     * List of objects that this notification is allowed
     * to send to. If null, then all notifications can be sent to all
     * registered objects
     */
    private static $allowed_objects;

    /**
     * Alternate fields on an object that can be used for the from
     * field on this notification. Provided in the format of
     * the classname as the key and an array of field names as the value.
     * EG:
     * 
     * App\Model\MyObject:
     *   - FieldName
     *   - Relation.FieldName
     * 
     * @var array
     */
    private static $alt_from_fields = [];

    /**
     * Alternate fields on an object that can be used for the recipient
     * field on this notification. Provided in the format of
     * the classname as the key and an array of field names as the value.
     * EG:
     * 
     * App\Model\MyObject:
     *   - FieldName
     *   - Relation.FieldName
     * 
     * @var array
     */
    private static $alt_recipient_fields = [];

    /**
     * The current object instance that is notifying
     *
     * @var DataObject
     */
    protected $object;

    /**
     * Extra vars to be used when rendering
     *
     * @var array
     */
    protected $extra_vars = [];

    private static $db = [
        'From' => 'Varchar',
        'AltFrom' => 'Varchar',
        'Recipient' => 'Varchar',
        'AltRecipient' => 'Varchar',
        'Content' => 'Text'
    ];

    private static $has_one = [
        'Notification' => Notification::class
    ];

    private static $casting = [
        'Type' => 'Varchar',
        'RenderedContent' => 'Text',
        'Summary' => 'Varchar'
    ];

    private static $summary_fields = [
        'Type',
        'From',
        'Recipient'
    ];

    private static $field_labels = [
        'AltFrom' => 'Send notification from Alternate Field',
        'AltRecipient' => 'Send notification to Alternate Field'
    ];

    public function getType()
    {
        return $this->i18n_singular_name();
    }

    /**
     * Attempt to generate a summary of this rule
     *
     * @return string
     */
    public function getSummary(): string
    {
        return $this->fieldLabel('Type') . ': ' . $this->Type;
    }

    /**
     * Return a rendered version of this notification's content using the
     * current object as a base
     *
     * @return string
     */
    public function getRenderedContent(): string
    {
        return $this->renderString((string) $this->Content);
    }

    /**
     * Get a list of possible alternate fields that can be used for the
     * from address
     *
     * @return array
     */
    protected function getAltFromFields(): array
    {
        $alt_fields = $this->config()->alt_from_fields;
        $notification_class = $this->Notification()->BaseClassName;
        $return = [];

        if (!is_array($alt_fields) || !array_key_exists($notification_class, $alt_fields)) {
            return $return;
        }

        foreach ($alt_fields[$notification_class] as $field) {
            $return[$field] = $field;
        }

        return $return;
    }

    /**
     * Get a list of possible alternate fields that can be used for the
     * recipient address
     *
     * @return array
     */
    protected function getAltRecipientFields(): array
    {
        $alt_fields = $this->config()->alt_recipient_fields;
        $notification_class = $this->Notification()->BaseClassName;
        $return = [];

        if (!is_array($alt_fields) || !array_key_exists($notification_class, $alt_fields)) {
            return [];
        }

        foreach ($alt_fields[$notification_class] as $field) {
            $return[$field] = $field;
        }

        return $return;
    }

    /**
     * Get a list of recipients to recieve this notification.
     * If only one is available then will be an array with
     * a single item
     *
     * @return array
     */
    protected function getRecipients(): array
    {
        $object = $this->getObject();
        $recipients = empty($this->Recipient) ? [] : explode(',', $this->Recipient);

        // If we arent declaring an alternate recipient
        // then return
        if (empty($this->AltRecipient)) {
            return $recipients;
        }

        // Try and resolve alternate recipients to a field
        // directly
        $recipient = $object->relField($this->AltRecipient);

        if (!empty($recipient)) {
            return array_merge(
                $recipients,
                explode(',', $recipient)
            );
        }

        // If alt recipient was null, try and see if it
        // resolves to a list
        $component = $object;
        $fieldName = null;

        if (($pos = strrpos($this->AltRecipient, '.')) !== false) {
            $relation = substr($this->AltRecipient, 0, $pos);
            $fieldName = substr($this->AltRecipient, $pos + 1);
            $component = $object->relObject($relation);
        }

        if (!empty($fieldName) && $component instanceof SS_List) {
            $recipients = array_merge(
                $recipients,
                $component->column($fieldName)
            );
        }

        return $recipients;
    }

    /** 
     * Try and find the correct default sender
     *
     * @return string
     */
    protected function getSender(): string
    {
        if (!empty($this->AltFrom)) {
            $sender = $this->AltFrom;
        } else {
            $sender = $this->From;
        }

        return (string)$sender;
    }

    protected function getFieldsFromClass(string $class): array
    {
        $result = [];

        $fields = [
            'db' => (array)Config::inst()->get(
                $class,
                'db',
                Config::UNINHERITED
            ),
            'casting' => (array)Config::inst()->get(
                $class,
                'casting',
                Config::UNINHERITED
            )
        ];

        foreach (array_values($fields) as $attrs) {
            foreach (array_keys($attrs) as $name) {
                $result[] = $name;
            }
        }

        return $result;
    }

    /**
     * Attempt to generate a list possible template
     * variables that can be used in the subject and
     * content fields.
     *
     * @return array
     */
    protected function compilePossibleTemplateVars(): array
    {
        $result = [];
        $base_class = $this->Notification()->BaseClassName;

        if (!class_exists($base_class)) {
            return $result;
        }

        // get all translated static properties as defined in i18nCollectStatics()
        $ancestry = ClassInfo::ancestry($base_class);
        $ancestry = array_reverse($ancestry);

        foreach ($ancestry as $ancestorClass) {
            // Finish at ViewableData
            if ($ancestorClass === ViewableData::class) {
                break;
            }

            $fields = $this->getFieldsFromClass($ancestorClass);

            foreach ($fields as $name) {
                $result[$name] = '{$' . $name . '}';
            }

            $relations = (array)Config::inst()->get(
                $ancestorClass,
                'has_one',
                Config::UNINHERITED
            );

            foreach ($relations as $name => $related_class) {
                $fields = $this->getFieldsFromClass($related_class);
        
                foreach (array_values($fields) as $related_name) {
                    $result[$name . '.' . $related_name] = '{$' . $name . '.' . $related_name . '}';
                }
            }
        }

        return $result;
    }

    /**
     * Generate a string of possible template vars
     *
     * @return string
     */
    protected function getRenderedTemplateVars(): string
    {
        $vars = $this->compilePossibleTemplateVars();
        return implode('<br/>', array_values($vars));
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $alt_from_fields = $this->getAltFromFields();
            $alt_recipient_fields = $this->getAltRecipientFields();

            if (count($alt_from_fields) > 0) {
                $fields->replaceField(
                    'AltFrom',
                    DropdownField::create(
                        'AltFrom',
                        $this->fieldLabel('AltFrom'),
                        $alt_from_fields
                    )->setEmptyString(_t(
                        __CLASS__ . '.AltFromEmptyString',
                        'Select a field to send from'
                    ))
                );
            } else {
                $fields->removeByName('AltFrom');
            }

            if (count($alt_recipient_fields) > 0) {
                $fields->replaceField(
                    'AltRecipient',
                    DropdownField::create(
                        'AltRecipient',
                        $this->fieldLabel('AltRecipient'),
                        $alt_recipient_fields
                    )->setEmptyString(_t(
                        __CLASS__ . '.AltRecipientEmptyString',
                        'Select a recipient'
                    ))
                );
            } else {
                $fields->removeByName('AltRecipient');
            }

            // Add a list of possible variables to use in
            // subject and content
            $vars_field = LiteralField::create(
                'TemplateVars',
                HTML::createTag(
                    'div',
                    ['class' => 'px-4 py-2'],
                    $this->getRenderedTemplateVars()
                )
            );

            $fields->addFieldToTab(
                'Root.Main',
                ToggleCompositeField::create(
                    'TemplateVarsComposite',
                    _t(__CLASS__ . ".TemplateVars", 'Possible Template Variables'),
                    $vars_field
                )
            );
        });

        return parent::getCMSFields();
    }

    /**
     * Ensure that a sender and recipient are set
     *
     * @return ValidationResult
     */
    public function validate()
    {
        $result = ValidationResult::create();

        if (empty($this->From) && empty($this->AltFrom)) {
            $result->addError(
                _t(__CLASS__ . '.NoSender', 'You have not set a sender')
            );
        }

        if (empty($this->Recipient) && empty($this->AltRecipient)) {
            $result->addError(
                _t(__CLASS__ . '.NoRecipient', 'You have not set a recipient')
            );
        }

        $this->extend('validate', $result);
        return $result;
    }

    public function send(array $custom_recipients = [])
    {
        throw new LogicException('You must implement your own send method');
    }

    /**
     * Get the current object instance that is notifying
     *
     * @return DataObject
     */ 
    public function getObject(): DataObject
    {
        return $this->object;
    }

    /**
     * Set the current object instance that is notifying
     *
     * @param DataObject $object
     *
     * @return self
     */ 
    public function setObject(DataObject $object): self
    {
        $base_class = $this->Notification()->BaseClassName;

        if (!is_a($object, $base_class)) {
            throw new LogicException('Object must be of type: ' . $base_class);
        }

        $this->object = $object;
        return $this;
    }

    /**
     * Take the passed string and render it using SSViewer
     *
     * @param string string
     *
     * @return string
     */
    protected function renderString(string $string): string
    {
        $object = $this->getObject();

        if (empty($object)) {
            throw new LogicException('You must set a base object via setObject');
        }

        $viewer = SSViewer::fromString($string);

        $vars = array_merge(
            [ 'CurrType' => $this ],
            $this->getExtraVars()
        );

        return $viewer->process(
            $object,
            $vars
        );
    }

    /**
     * Get extra vars to be used when rendering
     *
     * @return array
     */ 
    public function getExtraVars(): array
    {
        return $this->extra_vars;
    }

    /**
     * Set extra vars to be used when rendering
     *
     * @param array $extra_vars
     *
     * @return self
     */ 
    public function setExtraVars(array $extra_vars): self
    {
        $this->extra_vars = $extra_vars;
        return $this;
    }

    /**
     * Add a variable to be used when rendering
     *
     * @return self
     */ 
    public function addExtraVar(string $name, mixed $value): self
    {
        $this->extra_vars[$name] = $value;
        return $this;
    }

    /**
     * Remove a variable to be used when rendering
     *
     * @return self
     */ 
    public function removeExtraVar(string $name): self
    {
        if (array_key_exists($name, $this->extra_vars)) {
            unset($this->extra_vars[$name]);
        }

        return $this;
    }
}
