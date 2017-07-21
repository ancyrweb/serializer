<?php

namespace Serializer\Normalizer;

class ObjectNormalizer implements NormalizerInterface {
  /**
   * @param $data
   * @return mixed
   */
  public function normalize($data) {
    $reflection = new \ReflectionClass($data);
    $out = [];

    foreach($reflection->getProperties() as $property){
      $out[$property->getName()] = $property->getValue($data);
    }

    return $out;
  }

  /**
   * @param $data
   * @return mixed
   */
  public function denormalize($data, $object) {
    $reflection = new \ReflectionClass($object);
    foreach($reflection->getProperties() as $property){
      if(array_key_exists($property->getName(), $data)){
        $property->setValue($object, $data[$property->getName()]);
      }
    }

    return $object;
  }

}