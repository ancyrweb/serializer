<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer\Normalizer;

use Serializer\Context;
use Serializer\SerializerTools;

class ObjectNormalizer implements NormalizerInterface {
  /**
   * @param $data
   * @param Context $context
   * @return mixed
   */
  public function normalize($data, Context $context = null) {
    $reflection = new \ReflectionClass($data);
    $out = [];
    $metadata = null;

    if ($context) {
      if ($context->getView() !== null) {
        $metadata = $context->getMetadataCollection()->getOrNull(get_class($data));
      }
    }

    foreach ($reflection->getProperties() as $property) {
      $skip = false;
      if ($metadata) {
        if ($context->getView() !== null) {
          // We get the data corresponding to the current path
          $viewData = $metadata->getViewOrNull($context->getView());
          $viewData = SerializerTools::getPortion($viewData, $context->getNavigator()->getPath());

          // If there's any we filter out unwanted stuff
          if ($viewData) {
            if (in_array($property->name, $viewData) === false &&
              array_key_exists($property->name, $viewData) === false) {
              $skip = true;
            }
          }
        }
      }

      if ($skip)
        continue;

      $value = $property->getValue($data);
      if (is_object($value)) {
        $context->getNavigator()->down($property->name);
        $value = $this->normalize($value, $context);
        $context->getNavigator()->up();
      } else if (is_array($value)) {
        // We don't handle associative arrays so we assume this is a true array of values
        $value = array_map(function($notNormalizedValue) use ($property, $context) {
          $context->getNavigator()->down($property->name);
          $normalizedValue = $this->normalize($notNormalizedValue, $context);
          $context->getNavigator()->up();
          return $normalizedValue;
        }, $value);
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