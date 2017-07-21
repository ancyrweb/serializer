<?php
namespace Serializer;

class Dummy {
  public $foo;
  public $bar;

  public function __construct($foo = null, $bar = null) {
    $this->foo = $foo;
    $this->bar = $bar;
  }
}

class SerializerTest extends \PHPUnit\Framework\TestCase {
  /**
   * @var Serializer
   */
  public $serializer;

  public function setUp() {
    $this->serializer = new Serializer();
  }

  public function testSerialize(){
    $obj = new Dummy("a", "b");
    $output = $this->serializer->serialize($obj, "json");
    $expected = '{"foo":"a","bar":"b"}';

    $this->assertEquals($expected, $output);
  }

  public function testDeserialize(){
    $obj = new Dummy();
    $data = '{"foo":"a","bar":"b"}';
    $out = $this->serializer->deserialize($data, "json", $obj);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals("b", $out->bar);  }
}