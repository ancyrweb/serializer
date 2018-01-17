<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;

use Rewieer\Serializer\Exception\PrivatePropertyException;

/**
 * General accessor tool
 * TODO : test this
 *
 * Class PropertyAccessor
 * @package Rewieer\Serializer
 */
class PropertyAccessor {
  /**
   * @var \ReflectionClass
   */
  private $refClass;

  public function __construct($class) {
    $this->refClass = new \ReflectionClass($class);
  }

  /**
   * @return \ReflectionProperty[]
   */
  public function getProperties() {
    return $this->refClass->getProperties();
  }

  public function hasMethod($name) {
    return $this->refClass->hasMethod($name);
  }

  public function isPublic($name) {
    return $this->refClass->getMethod($name)->isPublic();
  }

  public function getClassName() {
    return $this->refClass->name;
  }

  /**
   * Return the value for this property
   * @param \ReflectionProperty $property
   * @param $object
   * @return mixed
   * @throws PrivatePropertyException
   */
  public function get(\ReflectionProperty $property, $object) {
    if ($property->isPublic()) {
      return $property->getValue($object);
    }

    if ($this->refClass->hasMethod($property->name)) {
      return call_user_func([$object, $property->name]);
    }

    $camelCase = "get" .ucfirst($property->name);
    if ($this->refClass->hasMethod($camelCase)) {
      return call_user_func([$object, $camelCase]);
    }

    $snakeCase = "get_" .$property->name;
    if ($this->refClass->hasMethod($snakeCase)) {
      return call_user_func([$object, $snakeCase]);
    }

    throw new PrivatePropertyException($property->name, $this->refClass->name);
  }

  /**
   * Set the value for this property
   * @param \ReflectionProperty $property
   * @param $object
   * @param $value
   * @throws PrivatePropertyException
   */
  public function set(\ReflectionProperty $property, $object, $value) {
    if ($property->isPublic()) {
      $property->setValue($object, $value);
      return;
    }

    $camelCase = "set" .ucfirst($property->name);
    if ($this->refClass->hasMethod($camelCase)) {
      call_user_func_array([$object, $camelCase], [$value]);
      return;
    }

    $snakeCase = "set_" .$property->name;
    if ($this->refClass->hasMethod($snakeCase)) {
      call_user_func([$object, $snakeCase], [$value]);
      return;
    }

    throw new PrivatePropertyException($property->name, $this->refClass->name);
  }
}