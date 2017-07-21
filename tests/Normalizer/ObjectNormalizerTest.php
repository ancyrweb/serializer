<?php
namespace Serializer\Normalizer;

class Dummy {
  public $foo;
  public $bar;

  public function __construct($foo = null, $bar = null) {
    $this->foo = $foo;
    $this->bar = $bar;
  }
}

class ObjectNormalizerTest extends \PHPUnit\Framework\TestCase {
  /**
   * @var ObjectNormalizer
   */
  public $normalizer;

  public function setUp() {
    $this->normalizer = new ObjectNormalizer();
  }

  public function testNormalizing(){
    $obj = new Dummy("a", "b");
    $output = $this->normalizer->normalize($obj);
    $this->assertEquals(["foo" => "a", "bar" => "b"], $output);
  }

  public function testDenormalizing(){
    $data = ["foo" => "a", "bar" => "b"];
    $out = $this->normalizer->denormalize($data, new Dummy());

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals("b", $out->bar);
  }
}