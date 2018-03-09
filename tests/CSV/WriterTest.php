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

use Wedeto\IO\Path;
use Wedeto\IO\IOException;
use Wedeto\Util\Encoding;

/**
 * @covers Wedeto\FileFormats\CSV\Writer
 */
class WriterTest extends TestCase
{
    private $dir;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testdir'));
        $this->dir = vfsStream::url('testdir');
        $this->file = $this->dir . '/test.csv';
    }

    public function testCSVWriter()
    {
        $data = [
            ['id' => 1, 'name' => 'foo', 'desc' => 'test'],
            ['id' => 2, 'name' => 'foobar', 'desc' => '2test'],
            ['id' => 4, 'name' => 'foobarbaz', 'desc' => 'test 4th item'],
            ['id' => 7, 'name' => 'foobaz', 'desc' => 'test last']
        ];

        $writer = new Writer;
        $this->assertEquals(',', $writer->getDelimiter());
        $this->assertEquals('"', $writer->getEnclosure());
        $this->assertEquals('\\', $writer->getEscapeChar());

        $this->assertInstanceOf(Writer::class, $writer->setDelimiter(';'));
        $this->assertInstanceOf(Writer::class, $writer->setEnclosure("'"));
        $this->assertInstanceOf(Writer::class, $writer->setEscapeChar("!"));

        $this->assertEquals(';', $writer->getDelimiter());
        $this->assertEquals("'", $writer->getEnclosure());
        $this->assertEquals('!', $writer->getEscapeChar());

        $this->assertInstanceOf(Writer::class, $writer->setDelimiter(','));
        $this->assertInstanceOf(Writer::class, $writer->setEnclosure('"'));
        $this->assertInstanceOf(Writer::class, $writer->setEscapeChar('\\'));

        $writer->write($data, $this->file);

        $actual = file_get_contents($this->file);
        $expected = <<<CSV
id,name,desc
1,foo,test
2,foobar,2test
4,foobarbaz,"test 4th item"
7,foobaz,"test last"

CSV;

        $this->assertEquals($expected, $actual);

        $this->assertEquals(true, $writer->getWriteHeader());
        $this->assertInstanceOf(Writer::class, $writer->setWriteHeader(false));
        $this->assertEquals(false, $writer->getWriteHeader());
        $writer->write($data, $this->file);

        $actual = file_get_contents($this->file);
        $expected = <<<CSV
1,foo,test
2,foobar,2test
4,foobarbaz,"test 4th item"
7,foobaz,"test last"

CSV;
    }

    public function testWriteCSVWithBOM()
    {
        $data = [
            ['foo', 'bar', 'baz'],
            ['foobar', 'foobaz', 'barbaz']
        ];

        $writer = new Writer;
        $writer->setWriteBOM(true);
        $writer->setWriteHeader(false);

        $writer->write($data, $this->file);

        $actual = file_get_contents($this->file);
        $actual_bom = substr($actual, 0, 3);

        $this->assertEquals(Encoding::getBOM('UTF8'), $actual_bom);

        $rest = substr($actual, 3);
        $expected = "foo,bar,baz\nfoobar,foobaz,barbaz\n";

        $this->assertEquals($expected, $rest, "Rest of data should be written correctly");
    }

}
