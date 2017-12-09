<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer\Normalizer;
use Serializer\Context;

/**
 * Interface NormalizerInterface
 * @package Normalizer
 *
 * A Normalizer turns complex data into an array and can do vice versa
 */
interface NormalizerInterface {
  public function normalize($data, Context $context = null);
  public function denormalize(array $data, $object, Context $context = null);
}