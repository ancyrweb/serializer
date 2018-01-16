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
 * Class PreNormalizeEvent
 * @package Rewieer\Serializer\Event
 */
class PreNormalizeEvent {
  private $entity;
  private $context;

  public function __construct(Context $context, $entity) {
    $this->context = $context;
    $this->entity = $entity;
  }

  public function getEntity() {
    return $this->entity;
  }
}