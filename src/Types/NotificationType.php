<?php

namespace ilateral\SilverStripe\Notifier\Types;

use ilateral\SilverStripe\Notifier\Model\Notification;
use LogicException;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\SSViewer;

/**
 * Base Object for sending notifications
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
     * The current object instance that is notifying
     *
     * @var DataObject
     */
    protected $object;

    private static $db = [
        'From' => 'Varchar',
        'Recipient' => 'Varchar',
        'Content' => 'Text'
    ];

    private static $has_one = [
        'Notification' => Notification::class
    ];

    private static $casting = [
        'RenderedContent' => 'Text'
    ];

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

    public function send($custom_recipient = null)
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
        $base_class = $this->Notification()->BaseClass;

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
        return $viewer->process(
            $object,
            [ 'CurrType' => $this ]
        );
    }
}
