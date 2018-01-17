<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Normalizer;

use Rewieer\Serializer\Context;
use Rewieer\Serializer\Exception\PrivatePropertyException;
use Rewieer\Serializer\PropertyAccessor;
use Rewieer\Serializer\SerializerTools;

class ObjectNormalizer implements NormalizerInterface {
  /**
   * @param $data
   * @param Context|null $context
   * @return array
   * @throws PrivatePropertyException
   */
  public function normalize($data, Context $context = null) {
    $metadata = null;
    $out = [];
    $accessor = new PropertyAccessor($data);

    if ($context && $context->getMetadataCollection()) {
      $metadata = $context->getMetadataCollection()->getOrNull(get_class($data));
    }

    foreach ($accessor->getProperties() as $property) {
      $skip = false;
      if ($context && $context->getView() !== null) {

        // We get the data corresponding to the current path
        if ($metadata !== null && is_array($context->getView()) === false) {
          $viewData = $metadata->getViewOrNull($context->getView());
        } else {
          $viewData = $context->getView();
        }

        $viewData = SerializerTools::deepGet($viewData, $context->getNavigator()->getPath());

        // If there's any we filter out unwanted stuff
        if ($viewData) {
          if (in_array($property->name, $viewData) === false && array_key_exists($property->name, $viewData) === false) {
            $skip = true;
          }
        }
      }

      if ($skip)
        continue;

      $value = null;
      $hasFound = false;
      if ($metadata) {
        $propertyConfiguration = $metadata->getAttributeOrNull($property->getName());
        if ($propertyConfiguration && array_key_exists("getter", $propertyConfiguration)) {
          $getter = $propertyConfiguration["getter"];
          if ($accessor->hasMethod($getter) === false || $accessor->isPublic($getter) === false) {
            throw new \Exception(
              sprintf(
                "Method %s for property %s:%s doesn't exist or is not public",
                $propertyConfiguration["getter"],
                $accessor->getClassName(),
                $property->name
              )
            );
          }

          $hasFound = true;
          $value = call_user_func([$data, $getter]);
        }
      }

      if ($hasFound === false) {
        try {
          $value = $accessor->get($property, $data);
        } catch (PrivatePropertyException $e) {
          continue;
        }
      }

      if (is_object($value)) {
        $context->getNavigator()->down($property->name);
        $value = $this->normalize($value, $context);
        $context->getNavigator()->up();
      } else if (is_array($value)) {
        // We don't handle associative arrays so we assume this is a genuine array
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
   * @param array $data
   * @param $object
   * @param Context|null $context
   * @return mixed
   * @throws PrivatePropertyException
   */
  public function denormalize(array $data, $object, Context $context = null) {
    $accessor = new PropertyAccessor($object);
    foreach ($accessor->getProperties() as $property) {
      if (array_key_exists($property->getName(), $data) === false) {
        continue;
      }

      $value = $data[$property->getName()];
      if ($context) {
        $metadata = $context->getMetadataCollection()->getOrNull(get_class($object));
        if ($metadata) {
          $propertyConfiguration = $metadata->getAttributeOrNull($property->getName());
          if ($propertyConfiguration) {
            if (array_key_exists("class", $propertyConfiguration) && is_array($value)) {
              $item = new $propertyConfiguration["class"];
              $value = $this->denormalize($value, $item, $context);
            } else if (array_key_exists("denormalizer", $propertyConfiguration) && is_array($value)) {
              $value = $propertyConfiguration["denormalizer"]($value, $object, $context);
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

      $accessor->set($property, $object, $value);
    }

    return $object;
  }

}