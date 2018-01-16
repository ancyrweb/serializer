<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;

/**
 * Class Navigator
 * @package Serializer
 * Handle graph traversal.
 */
class Navigator {
  /**
   * @var ClassMetadataCollection
   */
  private $metadataCollection;

  /**
   * @var string[]
   */
  private $path = [];

  /**
   * @return ClassMetadataCollection
   */
  public function getMetadataCollection() {
    return $this->metadataCollection;
  }

  /**
   * @param ClassMetadataCollection $metadataCollection
   * @return Navigator
   */
  public function setMetadataCollection(ClassMetadataCollection $metadataCollection) {
    $this->metadataCollection = $metadataCollection;
    return $this;
  }

  /**
   * Moves down the hierarchy
   * @param string $current
   */
  public function down(string $current) {
    $this->path[] = $current;
  }

  /**
   * Moves up
   */
  public function up() {
    array_pop($this->path);
  }

  /**
   * Get the current path
   * @return string[]
   */
  public function getPath() {
    return $this->path;
  }
}