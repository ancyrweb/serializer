<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Serializer\Serializer;

interface SerializerInterface {
  public function serialize($data);
  public function deserialize(string $data);
}