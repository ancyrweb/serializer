<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer\Normalizer;

use Serializer\Context;

class ObjectNormalizer implements NormalizerInterface {
  /**
   * @param $data
   * @param Context $context
   * @return mixed
   */
  public function normalize($data, Context $context = null) {
    $reflection = new \ReflectionClass($data);
    $out = [];

    foreach($reflection->getProperties() as $property) {
      $value = $property->getValue($data);
      if (is_object($value)) {
        $value = $this->normalize($value);
      }

      $out[$property->getName()] = $value;
    }

    return $out;
  }

  /**
   * @param $data
   * @param $object
   * @param $context
   * @return mixed
   */
  public function denormalize(array $data, $object, Context $context = null) {
    $reflection = new \ReflectionClass($object);
    foreach ($reflection->getProperties() as $property) {
      if (array_key_exists($property->getName(), $data) === false) {
        continue;
      }

      $value = $data[$property->getName()];
      if ($context) {
        $metadata = $context->getMetadataCollection()->getOrNull(get_class($object));
        if ($metadata) {
          $propertyConfiguration = $metadata->getPropertyOrNull($property->getName());
          if ($propertyConfiguration) {
            if (array_key_exists("class", $propertyConfiguration) && is_array($value)) {
              $item = new $propertyConfiguration["class"];
              $value = $this->denormalize($value, $item, $context);
            } else if (array_key_exists("loader", $propertyConfiguration) && is_array($value)) {
              $value = $propertyConfiguration["loader"]($value, $object, $context);
            } else if (array_key_exists("type", $propertyConfiguration)) {
              switch ($propertyConfiguration["type"]) {
                case "int":
                  $value = intval($value);
                  break;
                case "float":
                  $value = floatval($value);
                  break;
              }
            }
          }
        }
      }

      $property->setValue($object, $value);
    }

    return $object;
  }

}