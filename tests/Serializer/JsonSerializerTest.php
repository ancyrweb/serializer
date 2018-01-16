<?php
/*
 * (c) Anthony Benkhebbab <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rewieer\Tests\Serializer\Serializer;

use Rewieer\Serializer\Serializer\JsonSerializer;

class JsonSerializerTest extends \PHPUnit\Framework\TestCase {

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