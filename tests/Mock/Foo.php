<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Mock;

class Foo {
  private $var;

  public function __construct($var) {
    $this->var = $var;
  }

  /**
   * @return mixed
   */
  public function getVar() {
    return $this->var;
  }

  /**
   * @param mixed $var
   * @return Foo
   */
  public function setVar($var) {
    $this->var = $var;
    return $this;
  }
}
