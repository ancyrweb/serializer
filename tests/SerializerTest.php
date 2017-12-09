<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Serializer;

use Serializer\Serializer;
use Serializer\SerializerTools;

class Dummy {
  public $foo;
  public $bar;

  public function __construct($foo = null, $bar = null) {
    $this->foo = $foo;
    $this->bar = $bar;
  }
}

class SerializerTest extends \PHPUnit\Framework\TestCase {
  public function testSerialize() {
    $serializer = new Serializer();
    $obj = new Dummy("a", "b");
    $output = $serializer->serialize($obj, "json");
    $expected = '{"foo":"a","bar":"b"}';

    $this->assertEquals($expected, $output);
  }

  public function testDeserialize() {
    $serializer = new Serializer();
    $obj = new Dummy();
    $data = '{"foo":"a","bar":"b"}';
    $out = $serializer->deserialize($data, "json", $obj);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->foo);
    $this->assertEquals("b", $out->bar);
  }

  public function testSerializeWithMetadata() {
    $serializer = new Serializer(
      SerializerTools::createMetadataFromConfig([
        Dummy::class => [
          "bar" => [
            "class" => Dummy::class,
          ]
        ]
      ])
    );

    $obj = new Dummy("a", new Dummy("b", "c"));
    $output = $serializer->serialize($obj, "json");
    $expected = '{"foo":"a","bar":{"foo":"b","bar":"c"}}';

    $this->assertEquals($expected, $output);
  }

  public function testDeserializeWithMetadata() {
    $serializer = new Serializer(
      SerializerTools::createMetadataFromConfig([
        Dummy::class => [
          "bar" => [
            "class" => Dummy::class,
          ]
        ]
      ])
    );

    $data = '{"foo":"a","bar":{"foo":"b","bar":"c"}}';
    $output = $serializer->deserialize($data, "json", new Dummy());
    $expected = new Dummy("a", new Dummy("b", "c"));

    $this->assertEquals($expected, $output);
  }
}