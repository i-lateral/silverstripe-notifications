<?php

namespace ilateral\SilverStripe\Notifier;

use ilateral\SilverStripe\Notifier\Model\Notification;
use ilateral\SilverStripe\Notifier\Model\NotificationRule;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;

class DataObjectExtension extends DataExtension
{
    /**
     * Check if this object has been changed and
     * notify if needed
     */
    public function onAfterWrite()
    {
        /** @var DataObject */
        $owner = $this->getOwner();
        $changed_fields = $owner->getChangedFields(true, DataObject::CHANGE_VALUE);
        $new_values = [];

        foreach ($changed_fields as $key => $value) {
            $new_values[$key] = $value['after'];
        }

        $notifications = Notification::get()
            ->filter('BaseClassName', $owner->ClassName);

        // if created, shortcut below logic
        if (in_array('ID', array_keys($changed_fields))) {
            Notifier::processNotifications(
                $notifications->filter(Notification::STATE_CREATED, true),
                $owner
            );
            return;
        }

        // If no fields were changed, then no need to continue
        if (count(array_keys($changed_fields)) == 0) {
            return;
        }

        // Get a list of all relevent rules
        $rules = NotificationRule::get()->filter([
            'Notification.BaseClassName' => $owner->ClassName,
            'FieldName' => array_keys($changed_fields),
            'Value:not' => null
        ]);

        $rule_ids = $rules->filterByCallback(
            function($item, $list) use ($new_values) {
                if (in_array($item->FieldName, array_keys($new_values))
                    && $new_values[$item->FieldName] == $item->Value) {
                        return true;
                }
            }
        )->column('ID');

        if (count($rule_ids) > 0) {
            $notifications = $notifications->filter('Rules.ID', $rule_ids);
        }

        $notifications = $notifications->filterByCallback(
            function($item, $list) use ($changed_fields) {
                /**
                 * @var Notification $item
                 * @var DataList $list
                 */
                if ($item->dbObject(Notification::STATE_UPDATED)->getValue()) {
                    return true;
                }

                foreach ($item->Rules() as $rule) {
                    /** @var NotificationRule $rule */
                    if (in_array($rule->FieldName, array_keys($changed_fields))
                        && $rule->WasChanged == true) {
                            return true;
                    }

                    if (in_array($rule->FieldName, array_keys($changed_fields))
                        && isset($changed_fields[$rule->FieldName]['after'])
                        && $changed_fields[$rule->FieldName]['after'] == $rule->Value
                    ) {
                            return true;
                    }
                }

                return false;
            }
        );

        Notifier::processNotifications($notifications, $owner);
    }

    /**
     * Check if this object has been deleted and
     * notify if needed
     */
    public function onAfterDelete()
    {
        /** @var DataObject */
        $owner = $this->getOwner();

        $notifications = Notification::get()->filter(
            [
                'BaseClassName' => $owner->ClassName,
                Notification::STATE_DELETED => true
            ]
        );

        Notifier::processNotifications($notifications, $owner);
    }
}
