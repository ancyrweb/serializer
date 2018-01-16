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
use Rewieer\Serializer\Normalizer\ObjectNormalizer;

class Dummy {
  public $foo;
  public $bar;

  public function __construct($foo = null, $bar = null) {
    $this->foo = $foo;
    $this->bar = $bar;
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
    $output = $this->normalizer->normalize($obj, new Context());
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
    $this->assertEquals("a", $out->foo);
    $this->assertEquals(new Dummy("b", "c"), $out->bar);
  }

  public function testDenormalizingWithALoader() {
    $metadata = new ClassMetadata();
    $context = new Context();
    $dummy = new Dummy();

    $metadata->configureAttribute("bar", [
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
    $this->assertEquals(1, $out->foo);
    $this->assertTrue(is_int($out->foo));
    $this->assertEquals(2.3, $out->bar);
    $this->assertTrue(is_float($out->bar));
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
}