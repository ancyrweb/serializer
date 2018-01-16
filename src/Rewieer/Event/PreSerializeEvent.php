<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Event;

use Rewieer\Serializer\Context;

/**
 * Class PreSerializeEvent
 * @package Rewieer\Serializer\Event
 */
class PreSerializeEvent {
  private $normalized;
  private $context;

  public function __construct(Context $context, array &$normalized) {
    $this->context = $context;
    $this->normalized = $normalized;
  }

  public function getValue($key) {
    if (array_key_exists($key, $this->normalized)) {
      return $this->normalized[$key];
    }

    return null;
  }

  public function setValue($key, $value) {
    $this->normalized[$key] = $value;
  }

  public function getData() {
    return $this->normalized;
  }
}