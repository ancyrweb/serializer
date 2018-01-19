<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Mock;

class Dummy {
  private $foo;
  private $bar;
  private $isOk;
  private $customGetBarCallCount = 0;

  public function __construct($foo = null, $bar = null, $isOk = true) {
    $this->foo = $foo;
    $this->bar = $bar;
    $this->isOk = $isOk;
  }

  public function getFoo() {
    return $this->foo;
  }

  public function setFoo($foo): void {
    $this->foo = $foo;
  }

  public function getBar() {
    return $this->bar;
  }

  public function setBar($bar): void {
    $this->bar = $bar;
  }

  public function customGetBar() {
    $this->customGetBarCallCount++;
    return $this->bar;
  }

  public function customGetBarCallCountX() {
    return $this->customGetBarCallCount;
  }

  private function customGetBarPv() {
    return $this->customGetBarCallCount;
  }

  /**
   * @return mixed
   */
  public function isOk() {
    return $this->isOk;
  }

  /**
   * @param mixed $isOk
   * @return Dummy
   */
  public function setIsOk($isOk) {
    $this->isOk = $isOk;
    return $this;
  }
}