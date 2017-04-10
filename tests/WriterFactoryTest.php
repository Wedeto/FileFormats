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

use Wedeto\Util\Hook;
use Wedeto\Util\Dictionary;

use Wedeto\IO\File;

/**
 * @covers Wedeto\FileFormats\WriterFactory
 */
class WriterFactoryTest extends TestCase
{
    public function tearDown()
    {
        Hook::resetHook('Wedeto.FileFormats.CreateWriter');
        Hook::resetHook('Wedeto.FileFormats.GetWriterTypes');
    }

    public function testBuiltinTypes()
    {
        $writer = WriterFactory::factory("test.csv");
        $this->assertInstanceOf(CSV\Writer::class, $writer);
        $writer = WriterFactory::factory("test.ini");
        $this->assertInstanceOf(INI\Writer::class, $writer);
        $writer = WriterFactory::factory("test.json");
        $this->assertInstanceOf(JSON\Writer::class, $writer);
        $writer = WriterFactory::factory("test.phps");
        $this->assertInstanceOf(PHPS\Writer::class, $writer);
        $writer = WriterFactory::factory("test.xml");
        $this->assertInstanceOf(XML\Writer::class, $writer);
        $writer = WriterFactory::factory("test.yaml");
        $this->assertInstanceOf(YAML\Writer::class, $writer);

        $writer = WriterFactory::factory("test.CSV");
        $this->assertInstanceOf(CSV\Writer::class, $writer);
        $writer = WriterFactory::factory("test.INI");
        $this->assertInstanceOf(INI\Writer::class, $writer);
        $writer = WriterFactory::factory("test.JSON");
        $this->assertInstanceOf(JSON\Writer::class, $writer);
        $writer = WriterFactory::factory("test.PHPS");
        $this->assertInstanceOf(PHPS\Writer::class, $writer);
        $writer = WriterFactory::factory("test.XML");
        $this->assertInstanceOf(XML\Writer::class, $writer);
        $writer = WriterFactory::factory("test.YAML");
        $this->assertInstanceOf(YAML\Writer::class, $writer);

        $writer = WriterFactory::factory(new File("test.csv"));
        $this->assertInstanceOf(CSV\Writer::class, $writer);
        $writer = WriterFactory::factory(new File("test.ini"));
        $this->assertInstanceOf(INI\Writer::class, $writer);
        $writer = WriterFactory::factory(new File("test.json"));
        $this->assertInstanceOf(JSON\Writer::class, $writer);
        $writer = WriterFactory::factory(new File("test.phps"));
        $this->assertInstanceOf(PHPS\Writer::class, $writer);
        $writer = WriterFactory::factory(new File("test.xml"));
        $this->assertInstanceOf(XML\Writer::class, $writer);
        $writer = WriterFactory::factory(new File("test.yaml"));
        $this->assertInstanceOf(YAML\Writer::class, $writer);
    }

    public function testHookedWriter()
    {
        Hook::subscribe('Wedeto.FileFormats.CreateWriter', array($this, "writerFactory"));
        Hook::subscribe('Wedeto.FileFormats.GetWriterTypes', array($this, "writerFactoryTypes"));

        $list = WriterFactory::getAvailableWriters();
        $this->assertTrue(isset($list['application/x-test']));
        $this->assertEquals(static::class, $list['application/x-test']);

        $writer = WriterFactory::factory('foo.test');
        $this->assertInstanceOf(AbstractWriter::class, $writer);
        $this->assertContains("Double\Wedeto\FileFormats\AbstractWriter", get_class($writer));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Could not create writer for file");
        $writer = WriterFactory::factory('foo.bar');
    }

    public function testFactoryInvalidSource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provide a file or file name to WriterFactory");
        $writer = WriterFactory::factory(false);
    }

    public function writerFactory(Dictionary $dict)
    {
        if ($dict['file']->getExt() === 'test')
        {
            $mock = $this->prophesize(AbstractWriter::class);
            $dict['writer'] = $mock->reveal();
        }
    }

    public function writerFactoryTypes(Dictionary $dict)
    {
        $dict['types']['application/x-test'] = static::class;
    }
}
