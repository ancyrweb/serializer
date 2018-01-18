<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Exception;

use Throwable;

/**
 * Class PrivatePropertyException
 * @package Rewieer\Serializer\Exception
 */
class PrivatePropertyException extends \Exception {
  public function __construct(string $property, string $class, Throwable $previous = null) {
    parent::__construct(
      sprintf(
        "Cannot access property. Please define accessor such as get%s/set%s or get_%s/set_%s for class %s",
        ucfirst($property),
        ucfirst($property),
        $property,
        $property,
        $class
      ),
      0,
      $previous);
  }
}