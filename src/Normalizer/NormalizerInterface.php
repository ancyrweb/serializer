<?php
namespace Serializer\Normalizer;

/**
 * Interface NormalizerInterface
 * @package Normalizer
 *
 * A Normalizer turns complex data into an array and can do vice versa
 */
interface NormalizerInterface {
  public function normalize($data);
  public function denormalize($data, $object);
}