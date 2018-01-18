<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Serializer;

use Rewieer\Serializer\ClassMetadataCollection;
use Rewieer\Serializer\PropertyAccessor;
use Rewieer\Serializer\SerializerTools;

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

class Proxy extends Foo {

}

class PropertyAccessorTest extends \PHPUnit\Framework\TestCase {
  public function testGettingValue() {
    $obj = new Foo(5);
    $accessor = new PropertyAccessor($obj);
    $this->assertEquals(5, $accessor->get("var", $obj));
  }

  public function testGettingValueOfProxy() {
    $obj = new Proxy(5);
    $accessor = new PropertyAccessor($obj);
    $this->assertEquals(5, $accessor->get("var", $obj));
  }

  public function testSettingValue() {
    $obj = new Foo(5);
    $accessor = new PropertyAccessor($obj);
    $accessor->set("var", $obj, 10);
    $this->assertEquals(10, $obj->getVar());
  }

  public function testSettingValueOfProxy() {
    $obj = new Proxy(5);
    $accessor = new PropertyAccessor($obj);
    $accessor->set("var", $obj, 10);
    $this->assertEquals(10, $obj->getVar());
  }
}