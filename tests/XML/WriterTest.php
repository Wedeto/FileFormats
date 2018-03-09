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

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\IOException;

/**
 * @covers Wedeto\FileFormats\XML\Writer
 */
class WriterTest extends TestCase
{
    private $dir;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('testdir'));
        $this->dir = vfsStream::url('testdir');
    }

    public function testXMLWriterWritesNestedAndNumericArrays()
    {
        $cfg = array('sec1' => array('nest1' => array('nest2' => array('nest3' => 1))), 'sec2' => [1, 2, 3, 4]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('foobar');
        $this->assertEquals('foobar', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<foobar><sec1><nest1><nest2><nest3>1</nest3></nest2></nest1></sec1><sec2>1</sec2><sec2>2</sec2><sec2>3</sec2><sec2>4</sec2></foobar>

XML;
        $actual = file_get_contents($file);

        $this->assertEquals($expected, $actual);
    }

    public function testXMLWriterWritesNestedNumericArrays()
    {
        $cfg = array('arr1' => [['foo', 'bar'], ['baz', 'boo']]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('bar');
        $this->assertEquals('bar', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<bar><arr1><arr1>foo</arr1><arr1>bar</arr1></arr1><arr1><arr1>baz</arr1><arr1>boo</arr1></arr1></bar>

XML;
        $actual = file_get_contents($file);

        $this->assertEquals($expected, $actual);

        $cfg = array('arr1' => [['foo', 'bar'], ['baz', 'boo', ['foo', 'bar', 'baz', 'boo']]]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('foo');
        $this->assertEquals('foo', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<foo><arr1><arr1>foo</arr1><arr1>bar</arr1></arr1><arr1><arr1>baz</arr1><arr1>boo</arr1><arr1><arr1>foo</arr1><arr1>bar</arr1><arr1>baz</arr1><arr1>boo</arr1></arr1></arr1></foo>

XML;
        $actual = file_get_contents($file);

        $this->assertEquals($expected, $actual);

        $cfg = array('arr1' => [['foo', 'bar'], ['baz', 'boo', ['a' => 'foo', 'b' => 'bar', 'baz', 'boo']]]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('foobar');
        $this->assertEquals('foobar', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<foobar><arr1><arr1>foo</arr1><arr1>bar</arr1></arr1><arr1><arr1>baz</arr1><arr1>boo</arr1><arr1><a>foo</a><b>bar</b><arr1 index="0">baz</arr1><arr1 index="1">boo</arr1></arr1></arr1></foobar>

XML;
        $actual = file_get_contents($file);

        $this->assertEquals($expected, $actual);
    }

    public function testXMLWriterWriteAttributes()
    {
        $cfg = array('arr1' => ['foo' => 'bar', 'baz' => 'boo', '_foobar' => 1]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('bar');
        $this->assertEquals('bar', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<bar><arr1 foobar="1"><foo>bar</foo><baz>boo</baz></arr1></bar>

XML;
        $actual = file_get_contents($file);
    }

    public function testXMLWriterWriteAttributesAndContent()
    {
        $cfg = array('arr1' => ['foo' => ['_bar' => 'baz', '_content_' => 'foobar']]);
        $file = $this->dir . '/test.xml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->setRootNode('header');
        $this->assertEquals('header', $writer->getRootNode());
        $writer->write($cfg, $fh);
        fclose($fh);

        $expected = <<<XML
<?xml version="1.0"?>
<header><foo bar="baz">foobar</foo></header>

XML;
        $actual = file_get_contents($file);
    }
}
