<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Serializer;

use Rewieer\Serializer\Event\EventSubscriberInterface;
use Rewieer\Serializer\Event\PostDenormalizeEvent;
use Rewieer\Serializer\Event\PostDeserializeEvent;
use Rewieer\Serializer\Event\PreNormalizeEvent;
use Rewieer\Serializer\Event\PreSerializeEvent;
use Rewieer\Serializer\Event\SerializerEvents;
use Rewieer\Serializer\Serializer;
use Rewieer\Serializer\SerializerTools;

class Dummy {
  public $foo;
  public $bar;

  public function __construct($foo = null, $bar = null) {
    $this->foo = $foo;
    $this->bar = $bar;
  }
}

class Sub1 implements EventSubscriberInterface {
  public function preSerialize(PreSerializeEvent $event) {
    $event->setValue("qux", "c");
  }

  public function preNormalize(PreNormalizeEvent $event) {
    $event->getEntity()->bar = "newbar";
  }

  public function postDeserialize(PostDeserializeEvent $event) {
    $event->setValue("foo", "Jon");
  }

  public function postDenormalize(PostDenormalizeEvent $event) {
    $event->getEntity()->bar = "Snow";
  }

  public static function getEvents(): array {
    return [
      SerializerEvents::PRE_NORMALIZE => "preNormalize",
      SerializerEvents::PRE_SERIALIZE => "preSerialize",

      SerializerEvents::POST_DESERIALIZE => "postDeserialize",
      SerializerEvents::POST_DENORMALIZE => "postDenormalize",
    ];
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
  public function testSerializeWithSubscriber() {
    $serializer = new Serializer();
    $subscriber = new Sub1();
    $serializer->addSubscriber($subscriber);

    $obj = new Dummy("a", "b");
    $output = $serializer->serialize($obj, "json");
    $expected = '{"foo":"a","bar":"newbar","qux":"c"}';

    $this->assertEquals($expected, $output);
  }
  public function testDeserializeWithSubscriber() {
    $serializer = new Serializer();
    $subscriber = new Sub1();
    $serializer->addSubscriber($subscriber);

    $obj = new Dummy();
    $data = '{"foo":"a"}';
    $out = $serializer->deserialize($data, "json", $obj);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("Jon", $out->foo);
    $this->assertEquals("Snow", $out->bar);
  }


}