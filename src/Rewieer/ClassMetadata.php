<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;

/**
 * Class Metadata
 * Every class has meta data. It contain information about how to normalize and denormalize the object
 * It is composed of :
 * - properties : keeps data about a property
 * - views : choosing custom fields to render. One view is one representation of the object
 * @package Serializer
 */
class ClassMetadata {
  /**
   * @var array a map "ObjectKey" => "Configuration"
   */
  private $attributes = [];
  private $views = [];

  /**
   * Add metadata about the property
   * @param $attribute
   * @param $data
   * @return ClassMetadata
   */
  public function configureAttribute(string $attribute, array $data) {
    $this->attributes[$attribute] = $data;
    return $this;
  }

  /**
   * Get the property metadata
   * @param $attribute
   * @return mixed|null
   */
  public function getAttributeOrNull(string $attribute) {
    if (array_key_exists($attribute, $this->attributes)) {
      return $this->attributes[$attribute];
    }

    return null;
  }

  /**
   * Add the view
   * @param string $name
   * @param array $data
   */
  public function configureView(string $name, array $data) {
    $this->views[$name] = $data;
  }

  /**
   * Get the property metadata
   * @param $name
   * @return array|null
   */
  public function getViewOrNull(string $name) {
    if (array_key_exists($name, $this->views)) {
      return $this->views[$name];
    }

    return null;
  }

  /**
   * @return array the raw properties of the class metadata
   */
  public function rawProperties() {
    return $this->attributes;
  }
}