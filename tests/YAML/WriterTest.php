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

namespace Wedeto\FileFormats\YAML;

use PHPUnit\Framework\TestCase;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

use Wedeto\IO\Path;
use Wedeto\IO\IOException;

/**
 * @covers Wedeto\FileFormats\YAML\Writer
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

    /**
     * @covers Wedeto\FileFormats\INI\Writer::format
     * @covers Wedeto\FileFormats\INI\Writer::writeParameter
     */
    public function testYAMLWriter()
    {
        if (!function_exists('yaml_emit'))
            return;

        $cfg = array('sec1' => array('nest1' => array('nest2' => array('nest3' => 1))));
        $file = $this->dir . '/test.yaml';

        $fh = fopen($file, "w");
        $writer = new Writer();
        $writer->format($cfg, $fh);
        fclose($fh);

        $expected = yaml_emit($cfg);
        $actual = file_get_contents($file);

        $this->assertEquals($expected, $actual);
    }
}
