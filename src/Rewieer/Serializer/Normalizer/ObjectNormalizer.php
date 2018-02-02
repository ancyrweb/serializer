<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Normalizer;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use Rewieer\Serializer\ClassMetadata;
use Rewieer\Serializer\Context;
use Rewieer\Serializer\Exception\MethodException;
use Rewieer\Serializer\Exception\PrivatePropertyException;
use Rewieer\Serializer\PropertyAccessor;
use Rewieer\Serializer\Serializer;
use Rewieer\Serializer\SerializerTools;

class ObjectNormalizer implements NormalizerInterface {
  /**
   * We hold a copy of the serializer because the ObjectNormalize supports normalizing
   * nested objects with user-set normalizers.
   * We call class-defined normalizers in priority and fallback to object normalizer otherwise
   * @var Serializer
   */
  private $serializer;

  public function __construct(Serializer $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * @param $value
   * @param Context|null $context
   * @return array
   */
  private function normalizeValue($value, Context $context = null) {
    return $this->serializer->normalize($value, $context);
  }

  /**
   * Return the properties of the object
   * @param PropertyAccessor $accessor
   * @param Context|null $context
   * @param ClassMetadata|null $metadata
   * @return array|null
   */
  public function getProperties(PropertyAccessor $accessor, Context $context = null, ClassMetadata $metadata = null) {
    $properties = null;

    // By default we try to get data out of the view
    if ($context && $context->getView() !== null) {
      // We get the data corresponding to the current path
      if ($metadata !== null && is_array($context->getView()) === false) {
        $viewData = $metadata->getViewOrNull($context->getView());
      } else {
        $viewData = $context->getView();
      }

      $properties = SerializerTools::deepGet($viewData, $context->getNavigator()->getPath());
      if ($properties !== null) {
        foreach ($properties as $k => $v) {
          if (is_numeric($k) === false) {
            unset($properties[$k]);
            $properties[] = $k;
          }
        }
      }
    }

    // If there's no view data we instead rely on local properties
    if ($properties === null) {
      $properties = array_map(function(\ReflectionProperty $property) {
        return $property->name;
      }, $accessor->getProperties());
    }

    return $properties;
  }
  /**
   * @param $data
   * @param Context|null $context
   * @return array
   * @throws MethodException
   */
  public function normalize($data, Context $context = null) {
    $metadata = null;
    $out = [];
    $accessor = new PropertyAccessor($data);

    if ($context && $context->getMetadataCollection()) {
      $metadata = $context->getMetadataCollection()->getOrNull(get_class($data));
    }

    $properties = $this->getProperties($accessor, $context, $metadata);
    foreach ($properties as $property) {
      $value = null;
      $valueHasBeenSet = false; // custom getter can return null, in which case we don't want to lookup for accessors

      if ($metadata) {
        $propertyConfiguration = $metadata->getAttributeOrNull($property);
        if ($propertyConfiguration && array_key_exists("getter", $propertyConfiguration)) {
          $getter = $propertyConfiguration["getter"];
          if ($accessor->hasMethod($getter) === false || $accessor->isPublic($getter) === false) {
            throw new MethodException($propertyConfiguration["getter"], $accessor->getClassName(), $property);
          }

          $valueHasBeenSet = true;
          $value = call_user_func([$data, $getter]);
        }
      }

      if ($valueHasBeenSet === false) {
        try {
          $value = $accessor->get($property, $data);
        } catch (PrivatePropertyException $e) {
          // If we don't find any accessor we consider the user doesn't want it to be normalized
          continue;
        }
      }

      if (is_array($value) || $value instanceof \Traversable) {
        // We don't handle associative arrays so we assume this is a genuine array
        $arrayValue = $value;
        $value = [];

        foreach ($arrayValue as $subItem) {
          if ($context)
            $context->getNavigator()->down($property);

          $value[] = $this->normalizeValue($subItem, $context);

          if ($context)
            $context->getNavigator()->up();
        }
        unset($arrayValue);
      } else if (is_object($value)) {
        if ($context)
          $context->getNavigator()->down($property);

        $value = $this->normalizeValue($value, $context);

        if ($context)
          $context->getNavigator()->up();
      }

      $out[$property] = $value;
    }

    return $out;
  }

  /**
   * TODO : do nested denormalization
   * @param array $data
   * @param $object
   * @param Context|null $context
   * @return mixed
   * @throws PrivatePropertyException
   */
  public function denormalize(array $data, $object, Context $context = null) {
    $accessor = new PropertyAccessor($object);
    $metadata = null;

    if ($context && $context->getMetadataCollection()) {
      $metadata = $context->getMetadataCollection()->getOrNull(get_class($object));
    }

    $properties = $this->getProperties($accessor, $context, $metadata);

    foreach ($properties as $property) {
      if (array_key_exists($property, $data) === false) {
        continue;
      }

      $value = $data[$property];
      $valueHasBeenSet = false;

      if ($context && $metadata) {
        $propertyConfiguration = $metadata->getAttributeOrNull($property);
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
              case "bool":
                $value = boolval($value);
                break;
            }
          }

          if (array_key_exists("setter", $propertyConfiguration)) {
            $setter = $propertyConfiguration["setter"];
            if ($accessor->hasMethod($setter) === false || $accessor->isPublic($setter) === false) {
              throw new MethodException($propertyConfiguration["setter"], $accessor->getClassName(), $property);
            }

            $valueHasBeenSet = true;
            call_user_func_array([$object, $setter], [$value]);
          }
        }
      }

      if ($valueHasBeenSet === false) {
        $accessor->set($property, $object, $value);
      }
    }

    return $object;
  }

}