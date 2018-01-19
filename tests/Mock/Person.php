<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Mock;

class Person {
  public $name;
  public $job;
  public $friend;

  public function __construct($name, $job, $friend = null) {
    $this->name = $name;
    $this->job = $job;
    $this->friend = $friend;
  }
}
