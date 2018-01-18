<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer;

use Rewieer\Serializer\Event\EventDispatcher;
use Rewieer\Serializer\Event\EventSubscriberInterface;
use Rewieer\Serializer\Event\PostDenormalizeEvent;
use Rewieer\Serializer\Event\PostDeserializeEvent;
use Rewieer\Serializer\Event\PreNormalizeEvent;
use Rewieer\Serializer\Event\PreSerializeEvent;
use Rewieer\Serializer\Event\SerializerEvents;
use Rewieer\Serializer\Normalizer\NormalizerInterface;
use Rewieer\Serializer\Normalizer\ObjectNormalizer;
use Rewieer\Serializer\Serializer\JsonSerializer;
use Rewieer\Serializer\Serializer\SerializerInterface;

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

  /**
   * @var EventDispatcher
   */
  private $dispatcher;

  public function __construct(ClassMetadataCollection $collection = null) {
    $this->setSerializer("json", new JsonSerializer());
    $this->setNormalizer("object", new ObjectNormalizer($this));

    if ($collection === null) {
      $collection = new ClassMetadataCollection();
    }

    $this->classMetadataCollection = $collection;
    $this->dispatcher = new EventDispatcher();
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

    // PreNormalize event
    $preNormalizeEvent = new PreNormalizeEvent($context, $data);
    $this->dispatcher->dispatch(SerializerEvents::PRE_NORMALIZE, [$preNormalizeEvent]);

    // Normalization
    $normalized = $this->getNormalizer($data)->normalize($data, $context);

    // PreSerialize
    $preSerializeEvent = new PreSerializeEvent($context, $data, $normalized);
    $this->dispatcher->dispatch(SerializerEvents::PRE_SERIALIZE, [$preSerializeEvent]);
    $normalized = $preSerializeEvent->getData();

    // Serialization
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

    // PostDeserialize event
    $postDeserializeEvent = new PostDeserializeEvent($context, $object, $deserialized);
    $this->dispatcher->dispatch(SerializerEvents::POST_DESERIALIZE, [$postDeserializeEvent]);
    $deserialized = $postDeserializeEvent->getData();

    // Denormalizing
    $entity = $this->getNormalizer($object)->denormalize($deserialized, $object, $context);

    // PostDeserialize event
    $postDenormalizeEvent = new PostDenormalizeEvent($context, $entity);
    $this->dispatcher->dispatch(SerializerEvents::POST_DENORMALIZE, [$postDenormalizeEvent]);

    return $entity;
  }

  /**
   * Adds a subscriber
   * @param EventSubscriberInterface $subscriber
   */
  public function addSubscriber(EventSubscriberInterface $subscriber) {
    $this->dispatcher->addSubscriber($subscriber);
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

  /**
   * @return null|ClassMetadataCollection
   */
  public function getClassMetadataCollection(): ?ClassMetadataCollection {
    return $this->classMetadataCollection;
  }

  /**
   * @return SerializerInterface[]
   */
  public function getSerializers(): array {
    return $this->serializers;
  }

  public function getNormalizer($object) : NormalizerInterface {
    if (array_key_exists(get_class($object), $this->normalizers)) {
      return $this->normalizers[get_class($object)];
    }

    return $this->normalizers[gettype($object)];
  }

  /**
   * @return NormalizerInterface[]
   */
  public function getNormalizers(): array {
    return $this->normalizers;
  }
}