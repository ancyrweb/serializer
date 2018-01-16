<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Serializer\Event;

class SerializerEvents {
  // Serialization events
  const PRE_NORMALIZE = "preNormalize";
  const PRE_SERIALIZE = "preSerialize";

  // Deserialization events
  const POST_DESERIALIZE = "postDeserialize";
  const POST_DENORMALIZE = "postDenormalize";
}