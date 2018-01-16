<?php
/*
 * (c) Antonny Cyrille <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Event;

interface EventSubscriberInterface {
  /**
   * Return a list of event the user wishes to be notified about
   * Events in this case are particular in the sense that they allow to modify the
   * serialization / deserialization process. They are powerful as they are both subscribers
   * and middlewares.
   * @return array
   */
  public static function getEvents() : array;
}