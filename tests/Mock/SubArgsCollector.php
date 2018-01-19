<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Mock;

use Rewieer\Serializer\Event\EventSubscriberInterface;
use Rewieer\Serializer\Event\SerializerEvents;

class SubArgsCollector implements EventSubscriberInterface {
  public $calls;
  public function preSerialize($event) {
    $this->calls[] = $event;
  }

  public function preNormalize($event) {
    $this->calls[] = $event;
  }

  public function postDeserialize($event) {
    $this->calls[] = $event;
  }

  public function postDenormalize($event) {
    $this->calls[] = $event;
  }

  public static function getEvents(): array {
    return [
      SerializerEvents::PRE_NORMALIZE => "preNormalize",
      SerializerEvents::POST_NORMALIZE => "preSerialize",
      SerializerEvents::PRE_DENORMALIZE => "postDeserialize",
      SerializerEvents::POST_DENORMALIZE => "postDenormalize",
    ];
  }
}
