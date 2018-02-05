<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;

/**
 * Class ClassMetadataCollection
 * @package Serializer
 * Holds a map of "class" => "ClassMetadata".
 */
class ClassMetadataCollection {
  private $map = [];

  /**
   * Add the metadata to the map
   * @param string $class
   * @param ClassMetadata $metadata
   */
  public function add(string $class, ClassMetadata $metadata) {
    $this->map[$class] = $metadata;
  }

  /**
   * Return metadata corresponding to the map
   * @param $class
   * @return mixed|null
   */
  public function getOrNull($class) {
    if (array_key_exists($class, $this->map)) {
      return $this->map[$class];
    }

    return null;
  }

  /**
   * Return raw data
   * @return array
   */
  public function raw() {
    return $this->map;
  }
}