<?php

namespace Sabre\Event;

/**
 * Event Emitter class, stolen from sabre/event.
 * This is a class with the same public API but with PHP5.3 compat!
 *
 * https://github.com/fruux/sabre-event
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */
class EventEmitter implements EventEmitterInterface {

    /**
     * The list of listeners
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Subscribe to an event.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function on($eventName, $callBack, $priority = 100) {

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = array(
                true,  // If there's only one item, it's sorted
                array($priority),
                array($callBack)
            );
        } else {
            $this->listeners[$eventName][0] = false; // marked as unsorted
            $this->listeners[$eventName][1][] = $priority;
            $this->listeners[$eventName][2][] = $callBack;
        }

    }

    /**
     * Subscribe to an event exactly once.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function once($eventName, $callBack, $priority = 100) {

        $wrapper = null;
        $wrapper = function() use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            return call_user_func_array($callBack, func_get_args());

        };

        $this->on($eventName, $wrapper, $priority);

    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were succesfully
     * handled. false is returned if one of the events broke the event chain.
     *
     * If the continueCallBack is specified, this callback will be called every
     * time before the next event handler is called.
     *
     * If the continueCallback returns false, event propagation stops. This
     * allows you to use the eventEmitter as a means for listeners to implement
     * functionality in your application, and break the event loop as soon as
     * some condition is fulfilled.
     *
     * Note that returning false from an event subscriber breaks propagation
     * and returns false, but if the continue-callback stops propagation, this
     * is still considered a 'successful' operation and returns true.
     *
     * Lastly, if there are 5 event handlers for an event. The continueCallback
     * will be called at most 4 times.
     *
     * @param string $eventName
     * @param array $arguments
     * @param callback $continueCallBack
     * @return bool
     */
    public function emit($eventName, array $arguments = array(), $continueCallBack = null) {

        if (is_null($continueCallBack)) {

            foreach($this->listeners($eventName) as $listener) {

                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }
            }

        } else {

            $listeners = $this->listeners($eventName);
            $counter = count($listeners);

            foreach($listeners as $listener) {

                $counter--;
                $result = call_user_func_array($listener, $arguments);
                if ($result === false) {
                    return false;
                }

                if ($counter>0) {
                    if (!$continueCallBack()) break;
                }

            }

        }

        return true;

    }

    /**
     * Returns the list of listeners for an event.
     *
     * The list is returned as an array, and the list of events are sorted by
     * their priority.
     *
     * @param string $eventName
     * @return array
     */
    public function listeners($eventName) {

        if (!isset($this->listeners[$eventName])) {
            return array();
        }

        // The list is not sorted
        if (!$this->listeners[$eventName][0]) {

            // Sorting
            array_multisort($this->listeners[$eventName][1], SORT_NUMERIC, $this->listeners[$eventName][2]);

            // Marking the listeners as sorted
            $this->listeners[$eventName][0] = true;
        }

        return $this->listeners[$eventName][2];

    }

    /**
     * Removes a specific listener from an event.
     *
     * If the listener could not be found, this method will return false. If it
     * was removed it will return true.
     *
     * @param string $eventName
     * @param callable $listener
     * @return bool
     */
    public function removeListener($eventName, $listener) {

        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        foreach($this->listeners[$eventName][2] as $index => $check) {
            if ($check === $listener) {
                unset($this->listeners[$eventName][1][$index]);
                unset($this->listeners[$eventName][2][$index]);
                return true;
            }
        }
        return false;

    }

    /**
     * Removes all listeners.
     *
     * If the eventName argument is specified, all listeners for that event are
     * removed. If it is not specified, every listener for every event is
     * removed.
     *
     * @param string $eventName
     * @return void
     */
    public function removeAllListeners($eventName = null) {

        if (!is_null($eventName)) {
            unset($this->listeners[$eventName]);
        } else {
            $this->listeners = array();
        }

    }

}
