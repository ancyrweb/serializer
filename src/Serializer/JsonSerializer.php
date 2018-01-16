<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Serializer;

class JsonSerializer implements SerializerInterface {

  /**
   * @param $data
   * @return mixed
   */
  public function serialize($data) {
    return json_encode($data);
  }

  /**
   * @param string $data
   * @return mixed
   */
  public function deserialize(string $data) {
    return json_decode($data, true);
  }
}