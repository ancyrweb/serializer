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
 * Metadata allows to customize the normalization and denormalization step. This mecanism is used to give informations
 * to the normalizer that allows it to denormalize a sub-array into an object.
 * @package Serializer
 */
class ClassMetadata {
  /**
   * @var array a map whose keys are properties and values are arrays of data
   */
  private $properties = [];

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
   * @return array the raw properties of the class metadata
   */
  public function raw() {
    return $this->properties;
  }
}