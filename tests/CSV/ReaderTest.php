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

namespace Wedeto\FileFormats\CSV;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * @covers Wedeto\FileFormats\CSV\Reader
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
        $this->file = $this->dir . '/test.csv';
    }

    public function testReadCSVWithDefaultSettings()
    {
        $reader = new Reader;

        $this->assertEquals(',', $reader->getDelimiter());
        $this->assertEquals('"', $reader->getEnclosure());

        $data = [
            ['id' => 1, 'name' => 'rec1', 'parameter' => 'foobar'],
            ['id' => '2', 'name' => 'rec2', 'parameter' => 'foobar "baz"']
        ];

        $csv = <<<EOT
id,name,parameter
1,rec1,foobar
2,rec2,"foobar ""baz"""

EOT;

        file_put_contents($this->file, $csv);

        $actual = $reader->readFile($this->file);
        $this->assertEquals($data, $actual);
    }

    public function testReadCSVFromFileHandle()
    {
        $reader = new Reader;

        $this->assertEquals(',', $reader->getDelimiter());
        $this->assertEquals('"', $reader->getEnclosure());

        $data = [
            ['id' => 1, 'name' => 'rec1', 'parameter' => 'foobar'],
            ['id' => '2', 'name' => 'rec2', 'parameter' => 'foobar "baz"']
        ];

        $csv = <<<EOT
id,name,parameter
1,rec1,foobar
2,rec2,"foobar ""baz"""

EOT;

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $csv);
        rewind($fh);

        $actual = $reader->readFileHandle($fh);
        $this->assertEquals($data, $actual);
    }

    public function testReadCSVFromInvalidFileHandle()
    {
        $fh = null;
        $reader = new Reader;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No file handle was provided");
        $reader->readFileHandle($fh);
    }

    public function testReadCSVFromString()
    {
        $reader = new Reader;

        $this->assertEquals(',', $reader->getDelimiter());
        $this->assertEquals('"', $reader->getEnclosure());

        $data = [
            ['id' => 1, 'name' => 'rec1', 'parameter' => 'foobar'],
            ['id' => '2', 'name' => 'rec2', 'parameter' => 'foobar "baz"']
        ];

        $csv = <<<EOT
id,name,parameter
1,rec1,foobar
2,rec2,"foobar ""baz"""

EOT;

        $actual = $reader->readString($csv);
        $this->assertEquals($data, $actual);
    }


    public function testReadCSVWithCustomSettings()
    {
        $reader = new Reader;

        $this->assertInstanceOf(Reader::class, $reader->setDelimiter(';'));
        $this->assertInstanceOf(Reader::class, $reader->setEnclosure("'"));
        $this->assertEquals(';', $reader->getDelimiter());
        $this->assertEquals("'", $reader->getEnclosure());

        $data = [
            ['id' => 1, 'name' => 'rec1', 'parameter' => 'foobar'],
            ['id' => 2, 'name' => 'rec2', 'parameter' => 'foobarbaz']
        ];

        $csv = <<<EOT
id;name;parameter
1;'rec1';'foobar'
2;'rec2';'foobarbaz'

EOT;

        file_put_contents($this->file, $csv);

        $actual = $reader->readFile($this->file);
        $this->assertEquals($data, $actual);
    }

    public function testReadCSVUsingIterator()
    {
        $data = [
            ['id' => 1, 'name' => 'rec1', 'parameter' => 'foobar'],
            ['id' => 2, 'name' => 'rec2', 'parameter' => 'foobarbaz', 0 => 'other']
        ];

        $csv = <<<EOT
id,name,parameter
1,rec1,foobar
2,rec2,"foobarbaz","other"

EOT;

        file_put_contents($this->file, $csv);
        $reader = new Reader($this->file);
        $this->assertInstanceOf(Reader::class, $reader->setReadHeader(true));
        $this->assertTrue($reader->getReadHeader());

        $reader->rewind();
        $record = $reader->current();
        $this->assertTrue($reader->valid());
        $this->assertEquals(0, $reader->key());
        $this->assertEquals($data[0], $record);

        $reader->next();
        $record = $reader->current();
        $this->assertTrue($reader->valid());
        $this->assertEquals(1, $reader->key());
        $this->assertEquals($data[1], $record);

        $reader->next();
        $this->assertFalse($reader->valid());
        $this->assertFalse($reader->current());
    }

    public function testReadCSVUsingIteratorWithoutHeader()
    {
        $data = [
            [1, 'rec1', 'foobar'],
            [2, 'rec2', 'foobarbaz', 'other']
        ];

        $csv = <<<EOT
1,rec1,foobar
2,rec2,"foobarbaz","other"

EOT;

        file_put_contents($this->file, $csv);
        $reader = new Reader($this->file);
        $this->assertInstanceOf(Reader::class, $reader->setReadHeader(false));
        $this->assertFalse($reader->getReadHeader());

        $reader->rewind();
        $record = $reader->current();
        $this->assertTrue($reader->valid());
        $this->assertEquals(0, $reader->key());
        $this->assertEquals($data[0], $record);

        $reader->next();
        $record = $reader->current();
        $this->assertTrue($reader->valid());
        $this->assertEquals(1, $reader->key());
        $this->assertEquals($data[1], $record);

        $reader->next();
        $this->assertFalse($reader->valid());
        $this->assertFalse($reader->current());
    }

    public function testGetAndSetEscapeChar()
    {
        $reader = new Reader;
        $this->assertInstanceOf(Reader::class, $reader->setEscapeChar('!'));
        $this->assertEquals('!', $reader->getEscapeChar());

        $this->assertInstanceOf(Reader::class, $reader->setEscapeChar('^'));
        $this->assertEquals('^', $reader->getEscapeChar());
    }

    public function testReadingBOM()
    {
        $csv = chr(0xEF) . chr(0xBB) . chr(0xBF) . "foo,bar,baz";

        file_put_contents($this->file, $csv);

        $reader = new Reader($this->file);
        $reader->setReadHeader(false);

        $reader->rewind();
        $data = $reader->current();

        $this->assertTrue($reader->hasBOM(), "The BOM should be recognized");

        $this->assertEquals(3, count($data));
        $this->assertEquals('foo', $data[0], "BOM should not be read as characters");
        $this->assertEquals(3, strlen($data[0]), "BOM should not be read as characters");
        $this->assertEquals('bar', $data[1]);
        $this->assertEquals('baz', $data[2]);
    }
}
