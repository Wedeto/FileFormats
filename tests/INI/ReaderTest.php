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

namespace Wedeto\FileFormats\INI;


use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\Path;
use Wedeto\IO\IOException;

/**
 * @covers Wedeto\FileFormats\INI\Reader
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
        $this->file = $this->dir . '/test.ini';
    }

    public function testReadFile()
    {
        $ini = <<<EOT
[section1]
variable1 = "value1"
variable2 = null
variable3 = true
EOT;
        
        file_put_contents($this->file, $ini);

        $reader = new Reader;
        $actual = $reader->readFile($this->file);

        $expected = [
            'section1' => [
                'variable1' => 'value1',
                'variable2' => null,
                'variable3' => true
            ]
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testReadFileHandle()
    {
        $ini = <<<EOT
[section1]
variable1 = "value1"
variable2 = null
variable3 = true
EOT;
        
        $fh = fopen("php://memory", "rw");
        fwrite($fh, $ini);
        rewind($fh);

        $reader = new Reader;
        $actual = $reader->readFileHandle($fh);

        $expected = [
            'section1' => [
                'variable1' => 'value1',
                'variable2' => null,
                'variable3' => true
            ]
        ];

        $this->assertEquals($expected, $actual);
        fclose($fh);


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No file handle was provided");
        $actual = $reader->readFileHandle($fh);
    }

    public function testReadString()
    {
        $ini = <<<EOT
[section1]
variable1 = "value1"
variable2 = null
variable3 = true
EOT;
        
        $reader = new Reader;
        $actual = $reader->readString($ini);

        $expected = [
            'section1' => [
                'variable1' => 'value1',
                'variable2' => null,
                'variable3' => true
            ]
        ];

        $this->assertEquals($expected, $actual);
    }
}
