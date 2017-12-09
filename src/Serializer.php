<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

  /**
   * @var ClassMetadataCollection|null
   */
  private $classMetadataCollection;

  public function __construct(ClassMetadataCollection $collection = null) {
    $this->setSerializer("json", new JsonSerializer());
    $this->setNormalizer("object", new ObjectNormalizer());

    if ($collection === null) {
      $collection = new ClassMetadataCollection();
    }

    $this->classMetadataCollection = $collection;
  }

  /**
   * Configure the context
   * @param Context|null $context
   * @return Context
   */
  private function configureContext(Context $context = null) {
    if ($context === null) {
      $context = new Context();
    }

    if ($context->getMetadataCollection() === null) {
      $context->setMetadataCollection($this->classMetadataCollection);
    }

    return $context;
  }

  /**
   * Serialize the data
   * @param $data
   * @param string $format
   * @param Context|null $context
   * @return mixed
   */
  public function serialize($data, string $format, Context $context = null) {
    $context = $this->configureContext($context);
    $normalized = $this->normalizers[gettype($data)]->normalize($data, $context);
    return $this->serializers[$format]->serialize($normalized);
  }

  /**
   * Deserialize the data
   * @param $data
   * @param string $format
   * @param $object
   * @param Context|null $context
   * @return mixed
   */
  public function deserialize($data, string $format, $object, Context $context = null) {
    $context = $this->configureContext($context);
    $deserialized = $this->serializers[$format]->deserialize($data);
    return $this->normalizers[gettype($object)]->denormalize($deserialized, $object, $context);
  }

  /**
   * Set the serializer for a given format
   * @param $format
   * @param SerializerInterface $serializer
   */
  public function setSerializer($format, SerializerInterface $serializer) {
    $this->serializers[$format] = $serializer;
  }

  /**
   * Set the normalizer for a given format
   * @param $type
   * @param NormalizerInterface $normalizer
   */
  public function setNormalizer($type, NormalizerInterface $normalizer) {
    $this->normalizers[$type] = $normalizer;
  }
}