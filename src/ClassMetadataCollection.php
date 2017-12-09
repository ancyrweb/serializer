<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer;

class ClassMetadataCollection {
  private $map = [];

  public function add(string $class, ClassMetadata $metadata) {
    $this->map[$class] = $metadata;
  }

  public function getOrNull($class) {
    if (array_key_exists($class, $this->map)) {
      return $this->map[$class];
    }

    return null;
  }
}