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
 * @covers Wedeto\FileFormats\AbstractReader
 */
class AbstractReaderTest extends TestCase
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

    public function testReadFile()
    {
        file_put_contents($this->file, 'data');

        $mock = $this->getMockForAbstractClass(AbstractReader::class);
        $mock->method('readString')->will($this->returnValue(['data' => true]));

        $this->assertEquals(['data' => true], $mock->readFile($this->file));

        $fh = fopen($this->file, 'r');
        $this->assertEquals(['data' => true], $mock->readFileHandle($fh));

        $this->assertEquals(['data' => true], $mock->read($fh));
        $this->assertEquals(['data' => true], $mock->read($this->file));
        $this->assertEquals(['data' => true], $mock->read('data'));

        $f = new File($this->file); 
        $this->assertEquals(['data' => true], $mock->read($f));
    }

    public function testErrors()
    {
        $mock = $this->getMockForAbstractClass(AbstractReader::class);
        $mock->method('readString')->will($this->returnValue(['data' => true]));

        $thrown = false;
        try
        {
            $mock->readFile($this->file);
        }
        catch (IOException $e)
        {
            $this->assertContains('Failed to read file contents', $e->getMessage());
            $thrown = true;
        }

        $this->assertTrue($thrown);

        $thrown = false;
        try
        {
            $mock->readFileHandle(null);
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertContains("No file handle was provided", $e->getMessage());
            $thrown = true;
        }

        $this->assertTrue($thrown);

        $thrown = false;
        try
        {
            $mock->read(5.5);
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertContains("Cannot read argument: 5.5", $e->getMessage());
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
