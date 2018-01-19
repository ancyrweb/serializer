<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Serializer;

use Rewieer\Serializer\Context;
use Rewieer\Serializer\Event\EventSubscriberInterface;
use Rewieer\Serializer\Event\PostDenormalizeEvent;
use Rewieer\Serializer\Event\PreDenormalizeEvent;
use Rewieer\Serializer\Event\PreNormalizeEvent;
use Rewieer\Serializer\Event\PostNormalizeEvent;
use Rewieer\Serializer\Event\SerializerEvents;
use Rewieer\Serializer\Serializer;
use Rewieer\Serializer\SerializerTools;
use Rewieer\Tests\Mock\Dummy;
use Rewieer\Tests\Mock\SubArgsCollector;

class Sub1 implements EventSubscriberInterface {
  public function preSerialize(PostNormalizeEvent $event) {
    $event->setValue("qux", "c");
  }

  public function preNormalize(PreNormalizeEvent $event) {
    $event->getEntity()->setBar("newbar");
  }

  public function postDeserialize(PreDenormalizeEvent $event) {
    $event->setValue("foo", "Jon");
  }

  public function postDenormalize(PostDenormalizeEvent $event) {
    $event->getEntity()->setBar("Snow");
  }

  public static function getEvents(): array {
    return [
      SerializerEvents::PRE_NORMALIZE => "preNormalize",
      SerializerEvents::POST_NORMALIZE => "preSerialize",

      SerializerEvents::PRE_DENORMALIZE => "postDeserialize",
      SerializerEvents::POST_DENORMALIZE => "postDenormalize",
    ];
  }
}

class SerializerTest extends \PHPUnit\Framework\TestCase {
  public function testSerialize() {
    $serializer = new Serializer();
    $obj = new Dummy("a", "b");
    $output = $serializer->serialize($obj, "json");
    $expected = '{"foo":"a","bar":"b","isOk":true}';

    $this->assertEquals($expected, $output);
  }
  public function testDeserialize() {
    $serializer = new Serializer();
    $obj = new Dummy();
    $data = '{"foo":"a","bar":"b"}';
    $out = $serializer->deserialize($data, "json", $obj);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->getFoo());
    $this->assertEquals("b", $out->getBar());
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
    $expected = '{"foo":"a","bar":{"foo":"b","bar":"c","isOk":true},"isOk":true}';

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
    $argsCollector = new SubArgsCollector();
    $serializer->addSubscriber($subscriber);
    $serializer->addSubscriber($argsCollector);

    $obj = new Dummy("a", "b");
    $context = new Context();
    $output = $serializer->serialize($obj, "json", $context);
    $expected = '{"foo":"a","bar":"newbar","isOk":true,"qux":"c"}';
    $this->assertEquals($expected, $output);

    $this->assertInstanceOf(PreNormalizeEvent::class, $argsCollector->calls[0]);
    $this->assertEquals($obj, $argsCollector->calls[0]->getEntity());
    $this->assertEquals($context, $argsCollector->calls[0]->getContext());

    $this->assertInstanceOf(PostNormalizeEvent::class, $argsCollector->calls[1]);
    $this->assertEquals($obj, $argsCollector->calls[1]->getEntity());
    $this->assertEquals($context, $argsCollector->calls[1]->getContext());
    $this->assertEquals([
      "foo" => "a",
      "bar" => "newbar",
      "isOk" => true,
      "qux" => "c",
    ], $argsCollector->calls[1]->getData());
  }
  public function testDeserializeWithSubscriber() {
    $serializer = new Serializer();
    $subscriber = new Sub1();
    $argsCollector = new SubArgsCollector();
    $serializer->addSubscriber($argsCollector);
    $serializer->addSubscriber($subscriber);

    $obj = new Dummy();
    $context = new Context();
    $data = '{"foo":"a"}';
    $out = $serializer->deserialize($data, "json", $obj, $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("Jon", $out->getFoo());
    $this->assertEquals("Snow", $out->getBar());

    $this->assertInstanceOf(PreDenormalizeEvent::class, $argsCollector->calls[0]);
    $this->assertEquals($obj, $argsCollector->calls[0]->getEntity());
    $this->assertEquals($context, $argsCollector->calls[0]->getContext());
    $this->assertEquals([
      "foo" => "Jon",
    ], $argsCollector->calls[0]->getData());

    $this->assertInstanceOf(PostDenormalizeEvent::class, $argsCollector->calls[1]);
    $this->assertEquals($obj, $argsCollector->calls[1]->getEntity());
    $this->assertEquals($context, $argsCollector->calls[1]->getContext());
  }
}