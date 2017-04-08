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

namespace Wedeto\FileFormats\JSON;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\IOException;

/**
 * @covers Wedeto\FileFormats\JSON\Reader
 */
final class ReaderTest extends TestCase
{
    private $dir;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('cachedir'));
        $this->dir = vfsStream::url('cachedir');
        $this->file = $this->dir . '/json.json';
    }

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

        $json = json_encode($data);

        file_put_contents($this->file, $json);

        $reader = new Reader;
        $read_data = $reader->readFile($this->file);

        $this->assertEquals($data, $read_data);

        $fh = fopen($this->file, "r");
        $read_data = $reader->readFileHandle($fh);

        $this->assertEquals($data, $read_data);
    }

    public function testJSONReadNonExistingFile()
    {
        $a = new Reader;

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Failed to read");
        $a->readFile($this->file);
    }

    public function testJSONReadNonExistingFileHandle()
    {
        $fh = null;
        $a = new Reader;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No file handle was provided");
        $a->readFileHandle($fh);
    }
}
