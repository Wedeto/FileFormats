<?php
/*
This is part of WASP, the Web Application Software Platform.
It is published under the MIT Open Source License.

Copyright 2017, Egbert van der Wal

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace WASP\FileFormats\JSON;

use PHPUnit\Framework\TestCase;

/**
 * @covers WASP\FileFormats\JSON\Writer
 */
final class WriterTest extends TestCase
{
    /**
     * @covers WASP\FileFormats\JSON\Writer::init
     * @covers WASP\FileFormats\JSON\Writer::add
     * @covers WASP\FileFormats\JSON\Writer::get
     * @covers WASP\FileFormats\JSON\Writer::remove
     * @covers WASP\FileFormats\JSON\Writer::setPrettyPrinting
     * @covers WASP\FileFormats\JSON\Writer::setCallback
     */
    public function testJSON()
    {
        $data = array(
            'a' => 1,
            'b' => true,
            'c' => "test",
            'd' => array(
                'e' => 2,
                'f' => false,
                'g' => 5.5
            ),
            'e' => null,
            99 => 'value'
        );

        $writer = new Writer();

        $json = $writer->write($data);
        $this->assertEquals($json, json_encode($data));

        $brokendata = array(
            'a' => "\x00\x80\xc2\x9e\xe2\x80\xa0" . 'example'
        );
        $json = $writer->write($brokendata);
        $json2 = json_encode($brokendata);
        
        $this->assertFalse($json2);
        $this->assertEquals(json_last_error(), JSON_ERROR_UTF8);
        $this->assertEquals($json, '{"a":"\u0000???example"}');

        $writer->setPrettyPrint(true);
        $this->assertTrue($writer->getPrettyPrint());

        $json = $writer->write($data);
        $expected_json = <<<EOT
{
    "a": 1,
    "b": true,
    "c": "test",
    "d": {
        "e": 2,
        "f": false,
        "g": 5.5
    },
    "e": null,
    "99": "value"
}
EOT;
        $this->assertEquals($json, $expected_json);
    }

    /** 
     * @covers WASP\FileFormats\JSON\Writer::pprintJSON
     */
    public function testSerializable()
    {
        $dict = new Dictionary(); // A JsonSerializable class
        $dict['test'] = 1;

        $json = Writer::pprintJSON($dict);
        $this->assertEquals($json, "{\n    \"test\": 1\n}");
    }

    /** 
     * @covers WASP\FileFormats\JSON\Writer::pprintJSON
     */
    public function testSerializableException()
    {
        $obj = new \StdClass;
        $obj->test = 1;

        $this->expectException(\RuntimeException::class);
        $json = Writer::pprintJSON($obj);
    }

    /**
     * @covers WASP\FileFormats\JSON\Writer::pprintJSON
     */
    public function testJsonIsArray()
    {
        $a = array(1, 2, 3, 4, 5, 6, 7);
        $json = Writer::pprintJSON($a, 0, true, null);

        $pprint = <<<EOT
[
    1,
    2,
    3,
    4,
    5,
    6,
    7
]
EOT;
        $this->assertEquals($pprint, $json);
    }
};
