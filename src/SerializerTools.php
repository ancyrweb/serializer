<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer;

class SerializerTools {
  public static function createMetadataFromConfig(array $config) {
    $collection = new ClassMetadataCollection();
    foreach ($config as $class => $metadataConfig) {
      $metadata = new ClassMetadata();
      foreach($metadataConfig as $propertyName => $propertyConfig) {
        $metadata->configureProperty($propertyName, $propertyConfig);
      }

      $collection->add($class, $metadata);
    }
    return $collection;
  }
}