<?php

namespace ilateral\SilverStripe\Notifier\Middleware;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;

class NotificationMiddleware implements HTTPMiddleware
{
    /**
     * Setup extensions for any registered objects
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        $registered = Config::inst()->get(Notifier::class, 'registered_objects');

        foreach ($registered as $classname) {
            Config::modify()->set($classname, 'extensions', [DataObjectExtension::class]);
        }

        return $delegate($request);
    }
}