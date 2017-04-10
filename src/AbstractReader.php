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

use Wedeto\IO\IOException;
use Wedeto\IO\File;
use Wedeto\Util\Functions as WF;

/**
 * Define the Data Reader interface.
 * Each implementing class should at least implement readString.
 *
 * Optionally, for performance reasons for example, you can override
 * readFile and readFileHandle to more efficiently read large files.
 * The default implementation just reads the entire string and passes
 * it to readString.
 */
abstract class AbstractReader
{
    /**
     * Read provided data. The method auto-detects if it is a file,
     * a resource or a string that contains the formatted data.
     * @param mixed $param The data to read
     * @return array The read data
     * @throws InvalidArgumentException When an unrecognized argument is provided
     * @throws Wedeto\IO\IOException When reading fails
     */
    public function read($param)
    {
        if ($param instanceof File)
            return $this->readFile($param->getPath());

        if (is_resource($param))
            return $this->readFileHandle($param);

        if (!is_string($param))
            throw new \InvalidArgumentException("Cannot read argument: " . WF::str($param));

        if (strlen($param) < 1024 && file_exists($param))
            return $this->readFile($param);

        return $this->readString($param);
    }

    /**
     * Read data from a file
     * @param string $file_name The file to read
     * @return array The read data 
     * @throws Wedeto\IO\IOException On read errors
     */
    public function readFile(string $file_name)
    {
        $contents = @file_get_contents($file_name);
        if ($contents === false)
            throw new IOException("Failed to read file contents");

        return $this->readString(file_get_contents($file_name));
    }

    /**
     * Read from a file handle to an open file or resource
     * @param resource $file_handle The resource to read
     * @throws Wedeto\IO\IOException On read errors
     */
    public function readFileHandle($file_handle)
    {
        if (!is_resource($file_handle))
            throw new \InvalidArgumentException("No file handle was provided");

        return $this->readString(stream_get_contents($file_handle));
    }

    /**
     * Read data from a formatted string
     * @param string $data The formatted / encoded data to be read
     * @return array The read data
     * @throws WASP\IO\IOEXception On parse errors
     */
    abstract public function readString(string $data);
}
