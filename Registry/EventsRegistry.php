<?php
/**
 * @category Chiron
 * @package Chiron_Sirio
 * @version 0.1.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Chiron\Sirio\Registry;

/**
 * Class EventsRegistry
 * @package Chiron\Sirio\Registry
 */
class EventsRegistry
{
    /**
     * @var
     */
    private $eventData;

    /**
     * @param $event
     * Set the eventData to the event that is passed in.
     */
    public function set($event)
    {
        $this->eventData = $event;
    }

    /**
     * @return bool|mixed
     * return the event data if it exists, othewise just return false
     */
    public function get()
    {
        return $this->eventData ?? false;
    }
}
