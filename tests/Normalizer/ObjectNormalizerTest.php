<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Serializer\Normalizer;

use Rewieer\Serializer\Context;
use Rewieer\Serializer\ClassMetadata;
use Rewieer\Serializer\ClassMetadataCollection;
use Rewieer\Serializer\Event\PostNormalizeEvent;
use Rewieer\Serializer\Event\PreNormalizeEvent;
use Rewieer\Serializer\Exception\MethodException;
use Rewieer\Serializer\Exception\PrivatePropertyException;
use Rewieer\Serializer\Normalizer\DatetimeNormalizer;
use Rewieer\Serializer\Normalizer\ObjectNormalizer;
use Rewieer\Serializer\Serializer;
use Rewieer\Tests\Mock\ArrayFoo;
use Rewieer\Tests\Mock\Foo;
use Rewieer\Tests\Mock\FooProxy;
use Rewieer\Tests\Mock\Person;
use Rewieer\Tests\Mock\Dummy;
use Rewieer\Tests\Mock\SubArgsCollector;

class ObjectNormalizerTest extends \PHPUnit\Framework\TestCase {
  /**
   * @var Serializer
   */
  private $serializer;

  /**
   * @var ObjectNormalizer
   */
  public $normalizer;

  public function setUp() {
    $this->serializer = new Serializer();
    $this->normalizer = $this->serializer->getNormalizers()["object"];
  }

  public function testNormalizing(){
    $obj = new Dummy("a", "b");
    $output = $this->normalizer->normalize($obj);
    $this->assertEquals(["foo" => "a", "bar" => "b", "isOk" => true], $output);
  }

  public function testNormalizingNested(){
    $obj = new Dummy("a", new Dummy("b", "c"));
    $output = $this->normalizer->normalize($obj, new Context());
    $this->assertEquals(["foo" => "a", "bar" => ["foo" => "b", "bar" => "c", "isOk" => true], "isOk" => true], $output);
  }

  public function testNormalizingWithViewButNoMetaData(){
    $obj = new Dummy("a", "b");
    $context = new Context();
    $context->schema(["foo"]);

    $output = $this->normalizer->normalize($obj, $context);
    $this->assertEquals(["foo" => "a"], $output);
  }

  public function testNormalizingWithNestedNormalizer() {
    $this->serializer->setNormalizer(\DateTime::class, new DatetimeNormalizer());

    $obj = new Dummy("a", new \Datetime("29-11-1995"));
    $output = $this->normalizer->normalize($obj, new Context());
    $this->assertEquals(["foo" => "a", "bar" => "1995-11-29T00:00:00+01:00", "isOk" => true], $output);
  }

  public function testNormalizingProxy(){
    $obj = new FooProxy(10);
    $context = new Context();
    $context->schema(["var"]);

    $output = $this->normalizer->normalize($obj, $context);
    $this->assertEquals(["var" => 10], $output);
  }

  public function testNormalizingWithViews() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy("a", "b");

    $metadata
      ->configureView("view1", [
        "foo",
      ]);

    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Dummy::class, $metadata);
    $context
      ->setMetadataCollection($metadataCollection)
      ->useView("view1");

    $out = $this->normalizer->normalize($dummy, $context);

    $this->assertEquals(["foo" => "a"], $out);
  }

  public function testNormalizingWithProvidedView() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy("a", "b");


    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Dummy::class, $metadata);
    $context
      ->setMetadataCollection($metadataCollection)
      ->schema([
        "foo",
      ]);

    $out = $this->normalizer->normalize($dummy, $context);
    $this->assertEquals(["foo" => "a"], $out);
  }

  public function testNormalizingNestedWithViews() {
    $johnDoe = new Person(
      "John Doe",
      "Developer",
      ($janeDoe = new Person(
        "Jane Doe",
        "Manager",
        ($marshall =  new Person(
          "Marshall",
          "President"
        ))
      ))
    );

    $metadata = new ClassMetadata();
    $metadata
      ->configureView("view1", [
        "name",
        "job",
        "friend" => [
          "name",
          "friend",
        ]
      ]);

    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Person::class, $metadata);

    $context = new Context();
    $context->setMetadataCollection($metadataCollection);
    $context->useView("view1");

    $this->serializer->addSubscriber(($argsCollector = new SubArgsCollector()));
    $out = $this->normalizer->normalize($johnDoe, $context);

    $this->assertEquals([
      "name" => "John Doe",
      "job" => "Developer",
      "friend" => [
        "name" => "Jane Doe",
        "friend" => [
          "name" => "Marshall",
          "job" => "President",
          "friend" => null,
        ]
      ]
    ], $out);

    // Ensuring events are called
    // Note : the events for the initial object are not triggered by the normalizer itself but by serializer
    $this->assertEquals(4, count($argsCollector->calls));
    $this->assertInstanceOf(PreNormalizeEvent::class, $argsCollector->calls[0]);
    $this->assertEquals($janeDoe, $argsCollector->calls[0]->getEntity());

    $this->assertInstanceOf(PreNormalizeEvent::class, $argsCollector->calls[1]);
    $this->assertEquals($marshall, $argsCollector->calls[1]->getEntity());

    $this->assertInstanceOf(PostNormalizeEvent::class, $argsCollector->calls[2]);
    $this->assertEquals([
      "name" => "Marshall",
      "job" => "President",
      "friend" => null,
    ], $argsCollector->calls[2]->getData());

    $this->assertInstanceOf(PostNormalizeEvent::class, $argsCollector->calls[3]);
    $this->assertEquals([
      "name" => "Jane Doe",
      "friend" => [
        "name" => "Marshall",
        "job" => "President",
        "friend" => null,
      ]
    ], $argsCollector->calls[3]->getData());
  }

  public function testNormalizingNestedArrayWithViews() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $person = new Person(
      "John Doe",
      "Developer",
      [
        new Person("Jane Doe", "Manager", [
          new Person("Anne Mary", "Freelance"),
        ]),
        new Person("Marshall","President")
      ]
    );

    $metadata
      ->configureView("view1", [
        "name",
        "job",
        "friend" => [
          "name",
        ]
      ]);

    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Person::class, $metadata);
    $context
      ->setMetadataCollection($metadataCollection)
      ->useView("view1");

    $out = $this->normalizer->normalize($person, $context);

    $this->assertEquals([
      "name" => "John Doe",
      "job" => "Developer",
      "friend" => [
        [
          "name" => "Jane Doe",
        ],
        [
          "name" => "Marshall",
        ],
      ]
    ], $out);
  }

  public function testNormalizingWithGetter(){
    $obj = new Dummy("a", "b");
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("bar", [
      "getter" => "customGetBar",
    ]);
    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Dummy::class, $metadata);

    $context = new Context();
    $context->setMetadataCollection($metadataCollection);

    $output = $this->normalizer->normalize($obj, $context);
    $this->assertEquals(["foo" => "a", "bar" => "b", "isOk" => true], $output);
    $this->assertEquals(1, $obj->customGetBarCallCountX());
  }

  public function testNormalizingWithGetterWhenGetterDoesNotExist(){
    $obj = new Dummy("a", "b");
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("bar", [
      "getter" => "customGetBarX",
    ]);
    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Dummy::class, $metadata);

    $context = new Context();
    $context->setMetadataCollection($metadataCollection);

    $message = null;
    try {
      $this->normalizer->normalize($obj, $context);
    } catch (\Exception $e)  {
      $message = $e->getMessage();
    }

    $this->assertEquals("Method customGetBarX for property Rewieer\Tests\Mock\Dummy:bar doesn't exist or is not public", $message);
  }

  public function testNormalizingWithGetterWhenGetterIsNotPublic(){
    $obj = new Dummy("a", "b");
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("bar", [
      "getter" => "customGetBarPv",
    ]);
    $metadataCollection = new ClassMetadataCollection();
    $metadataCollection->add(Dummy::class, $metadata);

    $context = new Context();
    $context->setMetadataCollection($metadataCollection);

    $message = null;
    try {
      $this->normalizer->normalize($obj, $context);
    } catch (\Exception $e)  {
      $message = $e->getMessage();
    }

    $this->assertEquals("Method customGetBarPv for property Rewieer\Tests\Mock\Dummy:bar doesn't exist or is not public", $message);
  }

  public function testNormalizingWhenNestedArrayAccessibleItem(){
    $obj = new Dummy(
      "a",
      new ArrayFoo([
        new Dummy("a1", "b1"),
        new Dummy("a2", "b2")
      ])
    );

    $output = $this->normalizer->normalize($obj, new Context());
    $this->assertEquals([
      "foo" => "a",
      "bar" => [
        [
          "foo" => "a1",
          "bar" => "b1",
          "isOk" => true,
        ],
        [
          "foo" => "a2",
          "bar" => "b2",
          "isOk" => true,
        ]
      ],
      "isOk" => true
    ], $output);
  }

  // Denormalization

  public function testDenormalizing(){
    $data = ["foo" => "a", "bar" => "b"];
    $out = $this->normalizer->denormalize($data, new Dummy());

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->getFoo());
    $this->assertEquals("b", $out->getBar());
  }

  public function testDenormalizingNested() {
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("bar", [
      "class" => Dummy::class,
    ]);

    $context = new Context();
    $context->setMetadataCollection(new ClassMetadataCollection());
    $context
      ->getMetadataCollection()
      ->add(Dummy::class, $metadata);

    $data = ["foo" => "a", "bar" => ["foo" => "b", "bar" => "c"]];
    $out = $this->normalizer->denormalize($data, new Dummy(), $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals("a", $out->getFoo());
    $this->assertEquals(new Dummy("b", "c"), $out->getBar());
  }

  public function testDenormalizingWithALoader() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata->configureAttribute("bar", [
      "denormalizer" => function ($value, $object, Context $inContext = null) use ($context, $dummy) {
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
    $this->assertEquals("a", $out->getFoo());
    $this->assertEquals(new Dummy("b", "c"), $out->getBar());
  }

  public function testDenormalizingWithIntAndFloat() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata
      ->configureAttribute("foo", [
        "type" => "int",
      ])
      ->configureAttribute("bar", [
        "type" => "float",
      ]);

    $context->setMetadataCollection(new ClassMetadataCollection());
    $context->getMetadataCollection()->add(Dummy::class, $metadata);

    $data = ["foo" => "1", "bar" => "2.3"];
    $out = $this->normalizer->denormalize($data, $dummy, $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals(1, $out->getFoo());
    $this->assertTrue(is_int($out->getFoo()));
    $this->assertEquals(2.3, $out->getBar());
    $this->assertTrue(is_float($out->getBar()));
  }

  public function testDenormalizingWithIntAndBool() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata
      ->configureAttribute("foo", [
        "type" => "bool",
      ])
      ->configureAttribute("bar", [
        "type" => "bool",
      ]);

    $context->setMetadataCollection(new ClassMetadataCollection());
    $context->getMetadataCollection()->add(Dummy::class, $metadata);

    $data = ["foo" => 0, "bar" => "true"];
    $out = $this->normalizer->denormalize($data, $dummy, $context);

    $this->assertTrue($out instanceof Dummy);
    $this->assertEquals(false, $out->getFoo());
    $this->assertTrue(is_bool($out->getFoo()));
    $this->assertEquals(true, $out->getBar());
    $this->assertTrue(is_bool($out->getBar()));
  }

  public function testDenormalizingProxy() {
    $context = new Context();
    $context->schema(["var"]);

    $foo = new FooProxy(0);

    $data = ["var" => "this is new var"];
    $out = $this->normalizer->denormalize($data, $foo, $context);

    $this->assertTrue($out instanceof FooProxy);
    $this->assertEquals("this is new var", $out->getVar());
  }

  public function testDenormalizingWithUnexistingProperty() {
    $context = new Context();
    $context->schema(["test"]);

    $foo = new Foo(1);

    $data = ["var" => "this is new var", "test" => "haha"];
    $message = null;

    try {
      $this->normalizer->denormalize($data, $foo, $context);
    } catch (PrivatePropertyException $e) {
      $message = $e->getMessage();
    }

    $this->assertEquals(
      "Cannot access property. Please define accessor such as getTest/setTest or get_test/set_test for class Rewieer\Tests\Mock\Foo",
      $message
    );

    $this->assertEquals(1, $foo->getVar());
  }

  public function testDenormalizingWithCustomSetter() {
    $context = new Context();
    $context->schema(["test"]);

    $collection = new ClassMetadataCollection();
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("test", [
      "setter" => "setVar"
    ]);

    $collection->add(Foo::class, $metadata);
    $context->setMetadataCollection($collection);

    $foo = new Foo(1);

    $data = ["test" => "haha"];
    $out = $this->normalizer->denormalize($data, $foo, $context);

    $this->assertTrue($out instanceof Foo);
    $this->assertEquals("haha", $out->getVar());
  }

  public function testDenormalizingWithUnexistingCustomSetter() {
    $context = new Context();
    $context->schema(["test"]);

    $collection = new ClassMetadataCollection();
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("test", [
      "setter" => "setVarX"
    ]);

    $collection->add(Foo::class, $metadata);
    $context->setMetadataCollection($collection);

    $foo = new Foo(1);

    $data = ["test" => "haha"];
    $message = null;
    try {
      $this->normalizer->denormalize($data, $foo, $context);
    } catch (MethodException $e) {
      $message = $e->getMessage();
    }

    $this->assertEquals(
      "Method setVarX for property Rewieer\Tests\Mock\Foo:test doesn't exist or is not public",
      $message
    );
  }

  public function testDenormalizingWithPrivateCustomSetter() {
    $context = new Context();
    $context->schema(["test"]);

    $collection = new ClassMetadataCollection();
    $metadata = new ClassMetadata();
    $metadata->configureAttribute("test", [
      "setter" => "setVarPv"
    ]);

    $collection->add(Foo::class, $metadata);
    $context->setMetadataCollection($collection);

    $foo = new Foo(1);

    $data = ["test" => "haha"];
    $message = null;
    try {
      $this->normalizer->denormalize($data, $foo, $context);
    } catch (MethodException $e) {
      $message = $e->getMessage();
    }

    $this->assertEquals(
      "Method setVarPv for property Rewieer\Tests\Mock\Foo:test doesn't exist or is not public",
      $message
    );
  }

}