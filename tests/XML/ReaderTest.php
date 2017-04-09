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

namespace Wedeto\FileFormats\XML;

use LibXMLError;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\Path;

/**
 * @covers Wedeto\FileFormats\XML\Reader
 * @covers Wedeto\FileFormats\XML\XMLNode
 * @covers Wedeto\FileFormats\XML\XMLException
 */
class ReaderTest extends TestCase
{
    private $dir;
    private $file;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testdir'));
        $this->dir = vfsStream::url('testdir');
        $this->file = $this->dir . '/test.xml';
    }

    public function testReadFile()
    {
        $data = ['foo' => 'bar', 'baz' => [1, 2, 3], 'now' => date(\DateTime::ATOM)];
        $dt = $data['now'];
        $expected = <<<XML
<?xml version="1.0"?>
<foobar><foo>bar</foo><baz>1</baz><baz>2</baz><baz>3</baz><now>$dt</now></foobar>
XML;

        file_put_contents($this->file, $expected);

        $reader = new Reader;
        $actual = $reader->readFile($this->file);

        $this->assertEquals($data, $actual);
    }

    public function testReadGarbage()
    {
        $data = "GARBAGE";

        $reader = new Reader;
        try
        {
            $actual = $reader->readString($data);
        }
        catch (XMLException $ex)
        {
            $this->assertContains("XMLReader error", $ex->getMessage());
            $err = $ex->getError();
            $this->assertInstanceOf(LibXMLError::class, $err);

            $expected = trim($err->message);
            $this->assertContains($expected, $ex->getMessage());
        }
    }

    public function testReadInvalidFileHandle()
    {
        $fh = null;
        $reader = new Reader;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No file handle was provided");
        $actual = $reader->readFileHandle($fh);
    }

    public function testXMLReaderWriter()
    {
        $expected = ['foo' => 'bar', 'a' => [1, 2, 3], 'baz' => ['_type' => 'test', 'key' => 'value']];

        $writer = new Writer;
        $writer->write($expected, $this->file);

        $reader = new Reader;
        $actual = $reader->readFile($this->file);

        $this->assertEquals($expected, $actual);
    }

    public function testXMLReaderWriterUsingString()
    {
        $expected = ['foo' => 'bar', 'a' => [1, 2, 3], 'baz' => ['_type' => 'test', 'key' => 'value']];

        $writer = new Writer;
        $xml_string = $writer->write($expected);

        $reader = new Reader;
        $actual = $reader->readString($xml_string);

        $this->assertEquals($expected, $actual);
    }

    public function testXMLReaderWriterUsingFileHandle()
    {
        $expected = ['foo' => 'bar', 'a' => [1, 2, 3], 'baz' => ['_type' => 'test', 'key' => 'value']];

        $writer = new Writer;
        $fh = fopen($this->file, "w");
        $writer->write($expected, $fh);
        fclose($fh);

        $fh = fopen($this->file, "r");
        $reader = new Reader;
        $actual = $reader->readFileHandle($fh);

        $this->assertEquals($expected, $actual);
    }
}
