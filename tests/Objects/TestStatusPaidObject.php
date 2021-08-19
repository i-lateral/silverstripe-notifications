<?php

namespace ilateral\SilverStripe\Notifier\Tests\Objects;

use ilateral\SilverStripe\Notifier\DataObjectExtension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestStatusPaidObject extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar',
        'Status' => 'Varchar'
    ];

    private static $extensions = [
        DataObjectExtension::class
    ];
}
