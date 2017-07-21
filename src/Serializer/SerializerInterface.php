<?php
namespace Serializer\Serializer;

interface SerializerInterface {
  public function serialize($data);
  public function deserialize(string $data);
}