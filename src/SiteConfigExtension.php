<?php

namespace ilateral\SilverStripe\Notifier;

use ilateral\SilverStripe\Notifier\Model\Notification;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Notifications',
            GridField::create(
                'Notifications',
                '',
                Notification::get(),
                GridFieldConfig_RecordEditor::create()
            )
        );
    }
}
