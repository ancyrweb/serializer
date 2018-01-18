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
 * Class MethodException
 * @package Rewieer\Serializer\Exception
 */
class MethodException extends \Exception {
  public function __construct(string $method, string $className, string $propertyName, Throwable $previous = null) {
    parent::__construct(
      sprintf(
        "Method %s for property %s:%s doesn't exist or is not public",
        $method,
        $className,
        $propertyName
      ),
      0,
      $previous);
  }
}