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
   * @param \ReflectionClass $class
   * @param string $property
   * @param $object
   * @return mixed
   * @throws PrivatePropertyException
   */
  public static function recursiveGet(\ReflectionClass $class, string $property, $object) {
    if ($class->hasProperty($property)) {
      $property = $class->getProperty($property);
      if ($property->isPublic()) {
        return $property->getValue($object);
      }

      if ($class->hasMethod($property->name)) {
        return call_user_func([$object, $property->name]);
      }

      $camelCase = "get" .ucfirst($property->name);
      if ($class->hasMethod($camelCase)) {
        return call_user_func([$object, $camelCase]);
      }

      $snakeCase = "get_" .$property->name;
      if ($class->hasMethod($snakeCase)) {
        return call_user_func([$object, $snakeCase]);
      }
    }

    if ($class->getParentClass()) {
      return self::recursiveGet($class->getParentClass(), $property, $object);
    }

    throw new PrivatePropertyException($property->name, $class->name);
  }
  /**
   * Return the value for this property
   * @param string $property
   * @param $object
   * @return mixed
   * @throws PrivatePropertyException
   */
  public function get(string $property, $object) {
    return self::recursiveGet($this->refClass, $property, $object);
  }

  /**
   * @param \ReflectionClass $class
   * @param string $property
   * @param $object
   * @param $value
   * @throws PrivatePropertyException
   */
  public static function recursiveSet(\ReflectionClass $class, string $property, $object, $value) {
    if ($class->hasProperty($property)) {
      $property = $class->getProperty($property);
      if ($property->isPublic()) {
        $property->setValue($object, $value);
        return;
      }

      $camelCase = "set" .ucfirst($property->name);
      if ($class->hasMethod($camelCase)) {
        call_user_func_array([$object, $camelCase], [$value]);
        return;
      }

      $snakeCase = "set_" .$property->name;
      if ($class->hasMethod($snakeCase)) {
        call_user_func([$object, $snakeCase], [$value]);
        return;
      }
    }

    if ($class->getParentClass()) {
      return self::recursiveSet($class->getParentClass(), $property, $object, $value);
    }

    throw new PrivatePropertyException($property->name, $class->name);
  }

  /**
   * Set the value for this property
   * @param string $property
   * @param $object
   * @param $value
   * @throws PrivatePropertyException
   */
  public function set(string $property, $object, $value) {
    return self::recursiveSet($this->refClass, $property, $object, $value);
  }
}