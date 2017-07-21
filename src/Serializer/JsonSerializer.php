<?php
namespace Serializer\Serializer;

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