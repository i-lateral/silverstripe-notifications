<?php

namespace ilateral\SilverStripe\Notifier\Tests;

use SilverStripe\Dev\TestMailer;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Core\Injector\Injector;
use ilateral\SilverStripe\Notifier\Model\Notification;
use ilateral\SilverStripe\Notifier\DataObjectExtension;
use ilateral\SilverStripe\Notifier\Model\NotificationRule;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestRule;
use ilateral\SilverStripe\Notifier\Types\NotificationType;
use ilateral\SilverStripe\Notifier\Types\EmailNotification;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestCreateObject;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestDeleteObject;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestUpdateObject;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestSubjectObject;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestChangeNameObject;
use ilateral\SilverStripe\Notifier\Tests\Objects\TestStatusPaidObject;

class NotificationsTest extends SapphireTest
{
    protected static $fixture_file = 'NotificationsTests.yml';

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestCreateObject::class,
        TestUpdateObject::class,
        TestDeleteObject::class,
        TestChangeNameObject::class,
        TestStatusPaidObject::class,
        TestSubjectObject::class
    ];

    public function testSuccessfullyRegistered()
    {
        $this->assertContains(
            DataObjectExtension::class,
            Config::inst()->get(TestCreateObject::class, 'extensions')
        );
        $this->assertContains(
            DataObjectExtension::class,
            Config::inst()->get(TestUpdateObject::class, 'extensions')
        );
        $this->assertContains(
            DataObjectExtension::class,
            Config::inst()->get(TestDeleteObject::class, 'extensions')
        );
    }

    public function testGetAllowedRules()
    {
        /** @var Notification */
        $notification = Injector::inst()->get(Notification::class, true);

        $rules = $notification->getAllowedRules();

        $this->assertContains(NotificationRule::class, $rules);
        $this->assertContains(TestRule::class, $rules);
        $this->assertTrue($notification->isRuleAllowed(NotificationRule::class));
        $this->assertTrue($notification->isRuleAllowed(TestRule::class));

        Config::modify()->set(
            Notification::class,
            'disallow_rules',
            [TestRule::class]
        );

        $rules = $notification->getAllowedRules();

        $this->assertContains(NotificationRule::class, $rules);
        $this->assertNotContains(TestRule::class, $rules);
        $this->assertTrue($notification->isRuleAllowed(NotificationRule::class));
        $this->assertFalse($notification->isRuleAllowed(TestRule::class));
    }

    public function testGetAllowedTypes()
    {
        /** @var Notification */
        $notification = Injector::inst()->get(Notification::class, true);

        $types = $notification->getAllowedTypes();

        $this->assertNotContains(NotificationType::class, $types);
        $this->assertContains(EmailNotification::class, $types);
        $this->assertFalse($notification->isTypeAllowed(NotificationType::class));
        $this->assertTrue($notification->isTypeAllowed(EmailNotification::class));

        Config::modify()->set(
            Notification::class,
            'disallow_types',
            [EmailNotification::class]
        );

        $types = $notification->getAllowedTypes();

        $this->assertContains(NotificationType::class, $types);
        $this->assertNotContains(EmailNotification::class, $types);
        $this->assertTrue($notification->isTypeAllowed(NotificationType::class));
        $this->assertFalse($notification->isTypeAllowed(EmailNotification::class));
    }

    public function testCreatedEmails()
    {
        TestCreateObject::create()->write();

        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Created Subject'
        );
    }

    public function testUpdatedEmails()
    {
        /** @var TestMailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $object = TestUpdateObject::create();
        $object->write();

        // ensure no test emails were sent on creation
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->Name = "Test name";
        $object->write();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Updated Subject'
        );
    }

    public function testChangeFieldEmails()
    {
        /** @var TestMailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $object = TestChangeNameObject::create();
        $object->write();

        // ensure no test emails were sent on creation
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->Name = "Test name";
        $object->write();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Updated Name'
        );

        $object->Name = "Different name";
        $object->write();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Updated Name'
        );

        $mailer->clearEmails();
        $object->Status = "paid";
        $object->write();

        // ensure no test emails were sent on change of unchecked field
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));
    }

    public function testChangeFieldValueEmails()
    {
        /** @var TestMailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $object = TestStatusPaidObject::create();
        $object->write();

        // ensure no test emails were sent on creation
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->Name = "Test name";
        $object->write();

        // ensure no emails were sent on invalid field change
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->Status = "paid";
        $object->write();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Updated To Status Paid'
        );

        $mailer->clearEmails();
        $object->Status = "cancelled";
        $object->write();

        // ensure no emails were sent on invalid status change
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));
    }

    public function testDeletedEmails()
    {
        /** @var TestMailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $object = TestDeleteObject::create();
        $object->write();

        // ensure no test emails were sent on creation
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->Name = "Test name";
        $object->write();

        // ensure no test emails were sent on update
        $this->assertNull($mailer->findEmail('recipient@ilateral.co.uk'));

        $object->delete();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Deleted Subject'
        );
    }

    public function testSubjectRendered()
    {
        $object = TestSubjectObject::create();
        $object->write();

        $object->Name = "Test name";
        $object->write();

        // Ensure update email was sent
        $this->assertEmailSent(
            'recipient@ilateral.co.uk',
            'sender@ilateral.co.uk',
            'Field Changed To Test name'
        );
    }
}