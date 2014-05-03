<?php

namespace Sabre\Event;

/**
 * Event Emitter Trait
 *
 * This trait contains all the basic functions to implement an
 * EventEmitterInterface.
 *
 * Using the trait + interface allows you to add EventEmitter capabilities
 * without having to change your base-class.
 *
 * @copyright Copyright (C) 2013-2014 fruux GmbH. All rights reserved.
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/
 */
trait EventEmitterTrait {

    /**
     * The list of listeners
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * Subscribe to an event.
     *
     * @param string $eventName
     * @param callable $callBack
     * @param int $priority
     * @return void
     */
    public function on($eventName, callable $callBack, $priority = 100) {

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [
                true,  // If there's only one item, it's sorted
                [
                    [$priority, $callBack],
                ]
            ];
        } else {
            $this->listeners[$eventName][0] = false; // marked as unsorted
            $this->listeners[$eventName][1][] = [$priority, $callBack];
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
    public function once($eventName, callable $callBack, $priority = 100) {

        $wrapper = null;
        $wrapper = function() use ($eventName, $callBack, &$wrapper) {

            $this->removeListener($eventName, $wrapper);
            $result = call_user_func_array($callBack, func_get_args());

        };

        $this->on($eventName, $wrapper);

    }

    /**
     * Emits an event.
     *
     * This method will return true if 0 or more listeners were succesfully
     * handled. false is returned if one of the events broke the event chain.
     *
     * @param string $eventName
     * @param array $arguments
     * @return bool
     */
    public function emit($eventName, array $arguments = []) {

        foreach($this->listeners($eventName) as $listener) {

            $result = call_user_func_array($listener, $arguments);
            if ($result === false) {
                return false;
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
     * @return callable[] 
     */
    public function listeners($eventName) {

        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        // The list is not sorted
        if (!$this->listeners[$eventName][0]) {

            // Sorting
            usort($this->listeners[$eventName][1], function($a, $b) {

                return $a[0]-$b[0];

            });

            // Marking the listeners as sorted
            $this->listeners[$eventName][0] = true;
        }

        $result = [];
        foreach($this->listeners[$eventName][1] as $listener) {
            $result[] = $listener[1];
        }
        return $result;

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
    public function removeListener($eventName, callable $listener) {

        if (!isset($this->listeners[$eventName])) {
            return false;
        }
        foreach($this->listeners[$eventName][1] as $index => $check) {
            if ($check[1] === $listener) {
                unset($this->listeners[$eventName][1][$index]);
                return true;
            }
        }
        return false;

    }

    /**
     * Removes all listeners from the specified event.
     *
     * @param string $eventName
     * @return void
     */
    public function removeAllListeners($eventName) {

        unset($this->listeners[$eventName]);

    }

}
