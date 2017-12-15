<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer;

/**
 * Class Metadata
 * Holds the configuration for an object
 *
 * Metadata allows to customize the normalization and denormalization step.
 * For example, This mecanism is used to give informations
 * to the normalizer that allows it to denormalize a sub-array into an object.
 * @package Serializer
 */
class ClassMetadata {
  /**
   * @var array a map "ObjectKey" => "Configuration"
   */
  private $properties = [];
  private $views = [];

  public function __construct() {

  }

  /**
   * Add metadata about the property
   * @param $property
   * @param $data
   * @return ClassMetadata
   */
  public function configureProperty(string $property, array $data) {
    $this->properties[$property] = $data;
    return $this;
  }

  /**
   * Get the property metadata
   * @param $property
   * @return mixed|null
   */
  public function getPropertyOrNull(string $property) {
    if (array_key_exists($property, $this->properties)) {
      return $this->properties[$property];
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
    return $this->properties;
  }
}