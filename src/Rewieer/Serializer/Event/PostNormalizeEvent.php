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
class PostNormalizeEvent {
  private $entity;
  private $normalized;
  private $context;

  public function __construct(Context $context, $entity, &$normalized) {
    $this->context = $context;
    $this->entity = $entity;
    $this->normalized = $normalized;
  }

  public function getValue($key = null) {
    if (is_scalar($this->normalized)) {
      return $this->normalized;
    }

    if (array_key_exists($key, $this->normalized)) {
      return $this->normalized[$key];
    }

    return null;
  }

  public function setValue($key, $value) {
    if (is_scalar($this->normalized)) {
      $this->normalized = $value;
      return;
    }

    $this->normalized[$key] = $value;
  }

  public function getData() {
    return $this->normalized;
  }

  /**
   * @return mixed
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return Context
   */
  public function getContext(): Context {
    return $this->context;
  }
}