<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Mock;

class ArrayFoo implements \IteratorAggregate {
  private $container;

  public function __construct(array $container = []) {
    $this->container = $container;
  }

 public function getIterator() {
   return new \ArrayIterator($this->container);
 }
}
