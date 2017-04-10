<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
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

namespace Wedeto\FileFormats;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\IOException;
use Wedeto\IO\File;

/**
 * @covers Wedeto\FileFormats\AbstractWriter
 */
class AbstractWriterTest extends TestCase
{
    private $dir;
    private $file;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testdir'));
        $this->dir = vfsStream::url('testdir');
        $this->file = $this->dir . '/test.dat';
    }

    public function testWriter()
    {
        $expected = 'formatted_test_data';
        $mock = new MockAbstractWriter(true, $expected);
        $this->assertTrue($mock->getPrettyPrint());

        $mock = new MockAbstractWriter(false, $expected);
        $this->assertFalse($mock->getPrettyPrint());

        $this->assertInstanceOf(AbstractWriter::class, $mock->setPrettyPrint(true));
        $this->assertTrue($mock->getPrettyPrint());
        $this->assertInstanceOf(AbstractWriter::class, $mock->setPrettyPrint(false));
        $this->assertFalse($mock->getPrettyPrint());

        // Test writing to file
        $this->assertEquals($expected, $mock->write(['data']));
        $this->assertEquals(strlen($expected), $mock->write(['data'], $this->file));

        // Test writing to file handle
        $file = new File($this->file);
        $fh = $file->open('w');
        $this->assertEquals($expected, $mock->write(['data']));
        $this->assertEquals(strlen($expected), $mock->write(['data'], $fh));
    }

    public function testWriterInvalidData()
    {
        $mock = new MockAbstractWriter(false, "data");
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Data should be array or Traversable");
        $mock->write('data', false);
    }

    public function testWriteToInvalidTarget()
    {
        $mock = new MockAbstractWriter(false, "data");
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Argument 2 should be a file name or a resource");
        $mock->write(['data'], false);
    }
}

class MockAbstractWriter extends AbstractWriter
{
    private $data;

    public function __construct($pprint, string $data)
    {
        parent::__construct($pprint);
        $this->data = $data;
    }

    public function format($data, $file_handle)
    {
        fwrite($file_handle, $this->data);
    }
}
