<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer;


class Context {
  private $metadataCollection;

  public function __construct() {

  }

  public function setMetadataCollection(ClassMetadataCollection $metadataCollection) {
    $this->metadataCollection = $metadataCollection;
    return $this;
  }

  public function getMetadataCollection() {
    return $this->metadataCollection;
  }
}