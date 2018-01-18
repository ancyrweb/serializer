<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Normalizer;

use Rewieer\Serializer\Context;

class DatetimeNormalizer implements NormalizerInterface {
  /**
   * @param $data
   * @param Context|null $context
   * @return string
   * @throws \Exception
   */
  public function normalize($data, Context $context = null) {
    return $data->format(\DateTime::RFC3339);
  }

  /**
   * @param array $data
   * @param $object
   * @param Context|null $context
   * @return mixed
   */
  public function denormalize(array $data, $object, Context $context = null) {

  }

}