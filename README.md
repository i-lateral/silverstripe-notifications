# SilverStripe Notifications System

Module to allow creation of custom notifications for object
changes that can be managed via the admin.

## Install

Install this module via composer:

    composer require i-lateral/silverstripe-notifier

Once installed run `dev/build` to add additional database tables

## Configuration and Setup

Once installed, you will have to register the DataObjects you want
to be monitored for notification with `Notifier`. This can be done
via fairly simple config:

    ---
    Name: notificationsconfig
    ---
    # allow configuring of notifications to users and a custom page type
    ilateral\SilverStripe\Notifier\Notifier:
      registered_objects:
        - SilverStripe\Security\Member
        - App\Model\MyCustomPage

Once this configuration is added (and you flush the cache), navigate to
SiteConfig ("Settings" in the admin).

Now there will be a "notifications" tab allowing you to add new
notifications

## Adding notifications

When you add a new notification you will be able to choose the object
to monitor (from the list of registered objects above). You can then
also choose:

`StateCreated`: Should a notification be sent on object creation

`StateUpdated`: Should a notification be sent if an object is ever updated

`StateDeleted`: Should a notification be sent if an object is deleted

### Adding Rules (optional)

You can also add more granular rules for each notification. These allow you
to specify:

`FieldName`: The name of the data field on the object that will be checked

`Value`: If set, when an object has a field that is set to this value, send
         a notification

`WasChanged`: If you leave `Value` blank, then you can check `WasChanged` and
              then any change to this field will send a notification

### Adding Notification Types

Once you have specified an object to monitor and what will trigger notifications
you then need to specify how these notifications will be sent.

By default this module includes an `EmailNotification`, which will send an email
to the desired recipient using the provided subject and content.

You can add multiple versions of the each `NotificationType` to the same
`Notification` (for example, if you want to email multiple people in the event
of a change).

### Content Rendering

Notification content (and email subjects) support using SilverStripe variables
When a notification is sent, the context with regards to variables will be the
current object that is being monitored.

*EXAMPLE* If sending an email when a new `Member` is created, you can add
`{$FirstName}`, `{$Surname}`, `{$Email}` etc to your Content area, and these
will be rendered using the newly created `Member`.

## New Notification Types

The system is designed to fairly easily add new notification types (for example
SMS notifications) You simply have to extend `NotificationType` and add your own
`send` method to handle sending the notification.

## Dynamic senders and recipients

Instead of using a predefined sender and recipients, you can instead specify fields on the monitored object to use.

Before you can do this though, you have to inform Notifier
that you want to use custom fields. For example, if you have
the following object:

```php
class MyMonitoredObject extends DataObject
{
  private static $db = [
    'Sender' => 'Email',
    'Recipient' => 'Email'
  ];

  private static $extensions = [
    'ilateral\SilverStripe\Notifier'
  ];
}
```

You can add custom config:

```yml
ilateral\SilverStripe\Notifier\Types\NotificationType:
  alt_from_fields:
    MyMonitoredObject:
      - Sender
  alt_recipient_fields:
    MyMonitoredObject:
      - Recipient
```

Now, when you log into your admin area and visit:
`Settings > Notifications` and setup a new notification
you will see alternate dropdowns that let you select the
field you would like yo use.