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
use Rewieer\Serializer\Event\PreDenormalizeEvent;
use Rewieer\Serializer\Event\PreNormalizeEvent;
use Rewieer\Serializer\Event\PostNormalizeEvent;
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
   * @param $object
   * @param Context|null $context
   * @return array
   */
  public function normalize($object, Context $context = null) {
    // PreNormalize event
    $preNormalizeEvent = new PreNormalizeEvent($context, $object);
    $this->dispatcher->dispatch(SerializerEvents::PRE_NORMALIZE, [$preNormalizeEvent, $context]);

    // Normalization
    $normalized = $this->doNormalize($object, $context);

    // PostNormalize
    $postNormalizeEvent = new PostNormalizeEvent($context, $object, $normalized);
    $this->dispatcher->dispatch(SerializerEvents::POST_NORMALIZE, [$postNormalizeEvent, $context]);
    return $postNormalizeEvent->getData();
  }

  private function doNormalize($object, Context $context = null) {
    if (is_array($object) || $object instanceof \Traversable) {
      $returnValue = [];
      foreach($object as $value) {
        $returnValue[] = $this->normalize($value, $context);
      }

      return $returnValue;
    }

    return $this->getNormalizer($object)->normalize($object, $context);
  }

  /**
   * @param $value
   * @param $object
   * @param $context
   * @return mixed
   */
  public function denormalize($value, $object, $context) {
    // PreNormalize event
    $preNormalize = new PreDenormalizeEvent($context, $object, $value);
    $this->dispatcher->dispatch(SerializerEvents::PRE_DENORMALIZE, [$preNormalize, $context]);
    $value = $preNormalize->getData();

    // Denormalizing
    $entity = $this->getNormalizer($object)->denormalize($value, $object, $context);

    // PostDeserialize event
    $postDenormalizeEvent = new PostDenormalizeEvent($context, $entity);
    $this->dispatcher->dispatch(SerializerEvents::POST_DENORMALIZE, [$postDenormalizeEvent, $context]);
    return $entity;
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
    return $this->serializers[$format]->serialize($this->normalize($data, $context));
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
    return $this->denormalize($deserialized, $object, $context);
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