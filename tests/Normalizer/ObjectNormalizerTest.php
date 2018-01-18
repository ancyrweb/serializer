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
use Rewieer\Serializer\Normalizer\DatetimeNormalizer;
use Rewieer\Serializer\Normalizer\ObjectNormalizer;
use Rewieer\Serializer\Serializer;

class Dummy {
  private $foo;
  private $bar;
  private $isOk;
  private $customGetBarCallCount = 0;

  public function __construct($foo = null, $bar = null, $isOk = true) {
    $this->foo = $foo;
    $this->bar = $bar;
    $this->isOk = $isOk;
  }

  public function getFoo() {
    return $this->foo;
  }

  public function setFoo($foo): void {
    $this->foo = $foo;
  }

  public function getBar() {
    return $this->bar;
  }

  public function setBar($bar): void {
    $this->bar = $bar;
  }

  public function customGetBar() {
    $this->customGetBarCallCount++;
    return $this->bar;
  }

  public function customGetBarCallCountX() {
    return $this->customGetBarCallCount;
  }

  private function customGetBarPv() {
    return $this->customGetBarCallCount;
  }

  /**
   * @return mixed
   */
  public function isOk() {
    return $this->isOk;
  }

  /**
   * @param mixed $isOk
   * @return Dummy
   */
  public function setIsOk($isOk) {
    $this->isOk = $isOk;
    return $this;
  }
}

class TestPerson {
  public $name;
  public $job;
  public $friend;

  public function __construct($name, $job, $friend = null) {
    $this->name = $name;
    $this->job = $job;
    $this->friend = $friend;
  }
}

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
    $context->renderFields(["foo"]);

    $output = $this->normalizer->normalize($obj, $context);
    $this->assertEquals(["foo" => "a"], $output);
  }

  public function testNormalizingWithNestedNormalizer() {
    $this->serializer->setNormalizer(\DateTime::class, new DatetimeNormalizer());

    $obj = new Dummy("a", new \Datetime("29-11-1995"));
    $output = $this->normalizer->normalize($obj, new Context());
    $this->assertEquals(["foo" => "a", "bar" => "1995-11-29T00:00:00+01:00", "isOk" => true], $output);
  }


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

  public function testDenormalizingWithTypes() {
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
      ->renderFields([
        "foo",
      ]);

    $out = $this->normalizer->normalize($dummy, $context);
    $this->assertEquals(["foo" => "a"], $out);
  }

  public function testNormalizingNestedWithViews() {
    $person = new TestPerson(
      "John Doe",
      "Developer",
      new TestPerson(
        "Jane Doe",
        "Manager",
        new TestPerson(
          "Marshall",
          "President"
        )
      )
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
    $metadataCollection->add(TestPerson::class, $metadata);

    $context = new Context();
    $context->setMetadataCollection($metadataCollection);
    $context->useView("view1");

    $out = $this->normalizer->normalize($person, $context);

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
  }

  public function testNormalizingNestedArrayWithViews() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $person = new TestPerson(
      "John Doe",
      "Developer",
      [
        new TestPerson("Jane Doe", "Manager", [
          new TestPerson("Anne Mary", "Freelance"),
        ]),
        new TestPerson("Marshall","President")
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
    $metadataCollection->add(TestPerson::class, $metadata);
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

    $this->assertEquals("Method customGetBarX for property Rewieer\Tests\Serializer\Normalizer\Dummy:bar doesn't exist or is not public", $message);
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

    $this->assertEquals("Method customGetBarPv for property Rewieer\Tests\Serializer\Normalizer\Dummy:bar doesn't exist or is not public", $message);
  }

}