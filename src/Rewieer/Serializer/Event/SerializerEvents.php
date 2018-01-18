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
  const POST_NORMALIZE = "postNormalize";

  // Deserialization events
  const PRE_DENORMALIZE = "preDenormalize";
  const POST_DENORMALIZE = "postDenormalize";
}