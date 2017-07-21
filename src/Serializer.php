<?php
namespace Serializer;

use Serializer\Normalizer\NormalizerInterface;
use Serializer\Normalizer\ObjectNormalizer;
use Serializer\Serializer\JsonSerializer;
use Serializer\Serializer\SerializerInterface;

class Serializer {
  /**
   * @var SerializerInterface[]
   */
  private $serializers = [];

  /**
   * @var NormalizerInterface[]
   */
  private $normalizers = [];

  public function __construct() {
    $this->setSerializer("json", new JsonSerializer());
    $this->setNormalizer("object", new ObjectNormalizer());
  }

  public function serialize($data, string $format){
    $normalized = $this->normalizers[gettype($data)]->normalize($data);
    return $this->serializers[$format]->serialize($normalized);
  }

  public function deserialize($data, string $format, $object){
    $deserialized = $this->serializers[$format]->deserialize($data);
    return $this->normalizers[gettype($object)]->denormalize($deserialized, $object);
  }

  public function setSerializer($format, SerializerInterface $serializer){
    $this->serializers[$format] = $serializer;
  }

  public function setNormalizer($type, NormalizerInterface $normalizer){
    $this->normalizers[$type] = $normalizer;
  }
}