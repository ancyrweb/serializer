<?php
namespace Serializer\Serializer;

class JsonSerializerTest extends \PHPUnit\Framework\TestCase{

  /**
   * @var JsonSerializer
   */
  public $serializer;

  public function setUp() {
    $this->serializer = new JsonSerializer();
  }

  public function testSerializing(){
    $data = ["foo" => "bar"];
    $output = '{"foo":"bar"}';
    $this->assertEquals($output, $this->serializer->serialize($data));
  }

  public function testDeserializing(){
    $data = '{"foo":"bar"}';
    $output = ["foo" => "bar"] ;
    $this->assertEquals($output, $this->serializer->deserialize($data));
  }
}