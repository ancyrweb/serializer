<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Serializer\Normalizer;

use Serializer\Context;
use Serializer\ClassMetadata;
use Serializer\ClassMetadataCollection;
use Serializer\Normalizer\ObjectNormalizer;

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

  public function testNormalizingNested(){
    $obj = new Dummy("a", new Dummy("b", "c"));
    $output = $this->normalizer->normalize($obj);
    $this->assertEquals(["foo" => "a", "bar" => ["foo" => "b", "bar" => "c"]], $output);
  }

  public function testDenormalizing(){
    $data = ["foo" => "a", "bar" => "b"];
    $out = $this->normalizer->denormalize($data, new Dummy());

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals("b", $out->bar);
  }

  public function testDenormalizingNested() {
    $metadata = new ClassMetadata();
    $metadata->configureProperty("bar", [
      "class" => Dummy::class,
    ]);

    $context = new Context();
    $context->setMetadataCollection(new ClassMetadataCollection());
    $context->getMetadataCollection()->add(Dummy::class, $metadata);

    $data = ["foo" => "a", "bar" => ["foo" => "b", "bar" => "c"]];
    $out = $this->normalizer->denormalize($data, new Dummy(), $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals(new Dummy("b", "c"), $out->bar);
  }

  public function testDenormalizingWithALoader() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata->configureProperty("bar", [
      "loader" => function ($value, $object, Context $inContext = null) use ($context, $dummy) {
        $this->assertEquals(["foo" => "b", "bar" => "c"], $value);
        $this->assertEquals($object, $dummy);
        $this->assertEquals($context, $inContext);

        return new Dummy($value["foo"], $value["bar"]);
      }
    ]);

    $context->setMetadataCollection(new ClassMetadataCollection());
    $context->getMetadataCollection()->add(Dummy::class, $metadata);

    $data = ["foo" => "a", "bar" => ["foo" => "b", "bar" => "c"]];
    $out = $this->normalizer->denormalize($data, $dummy, $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals(new Dummy("b", "c"), $out->bar);
  }

  public function testDenormalizingWithTypes() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata
      ->configureProperty("foo", [
        "type" => "int",
      ])
      ->configureProperty("bar", [
        "type" => "float",
      ]);

    $context->setMetadataCollection(new ClassMetadataCollection());
    $context->getMetadataCollection()->add(Dummy::class, $metadata);

    $data = ["foo" => "1", "bar" => "2.3"];
    $out = $this->normalizer->denormalize($data, $dummy, $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals(1, $out->foo);
    $this->assertTrue(is_int($out->foo));
    $this->assertEquals(2.3, $out->bar);
    $this->assertTrue(is_float($out->bar));
  }
}