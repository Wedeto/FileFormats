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
 * @covers Wedeto\FileFormats\ReaderFactory
 */
class ReaderFactoryTest extends TestCase
{
    public function tearDown()
    {
        Hook::resetHook('Wedeto.FileFormats.CreateReader');
    }

    public function testBuiltinTypes()
    {
        $reader = ReaderFactory::factory("test.csv");
        $this->assertInstanceOf(CSV\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.ini");
        $this->assertInstanceOf(INI\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.json");
        $this->assertInstanceOf(JSON\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.phps");
        $this->assertInstanceOf(PHPS\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.xml");
        $this->assertInstanceOf(XML\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.yaml");
        $this->assertInstanceOf(YAML\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.CSV");
        $this->assertInstanceOf(CSV\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.INI");
        $this->assertInstanceOf(INI\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.JSON");
        $this->assertInstanceOf(JSON\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.PHPS");
        $this->assertInstanceOf(PHPS\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.XML");
        $this->assertInstanceOf(XML\Reader::class, $reader);

        $reader = ReaderFactory::factory("test.YAML");
        $this->assertInstanceOf(YAML\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.csv"));
        $this->assertInstanceOf(CSV\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.ini"));
        $this->assertInstanceOf(INI\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.json"));
        $this->assertInstanceOf(JSON\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.phps"));
        $this->assertInstanceOf(PHPS\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.xml"));
        $this->assertInstanceOf(XML\Reader::class, $reader);

        $reader = ReaderFactory::factory(new File("test.yaml"));
        $this->assertInstanceOf(YAML\Reader::class, $reader);
    }

    public function testHookedReader()
    {
        Hook::subscribe('Wedeto.FileFormats.CreateReader', array($this, "readerFactory"));

        $reader = ReaderFactory::factory('foo.test');
        $this->assertInstanceOf(AbstractReader::class, $reader);
        $this->assertContains("Double\Wedeto\FileFormats\AbstractReader", get_class($reader));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Could not create reader for file: foo.bar");
        $reader = ReaderFactory::factory('foo.bar');
    }

    public function testReadInvalidSource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provide a file or file name to ReaderFactory");
        $reader = ReaderFactory::factory(false);
    }

    public function readerFactory(Dictionary $dict)
    {
        if ($dict['file']->getExtension() === 'test')
        {
            $mock = $this->prophesize(AbstractReader::class);
            $dict['reader'] = $mock->reveal();
        }
    }
}
