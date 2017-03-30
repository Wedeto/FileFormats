<?php
/*
This is part of WASP, the Web Application Software Platform.
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

namespace WASP\FileFormats;

use WASP\Util\Hook;

class WriterFactory
{
    public static function factory(string $file_name)
    {
        $ext_pos = strpos($file_name, ".");
        if ($ext_pos === false)
            throw new \RuntimeException("File has no extension: $file_name");

        $ext = strtolower(substr($file_name, $ext_pos + 1));

        $result = Hook::execute(
            "WASP.FileFormats.CreateWriter",
            ["writer" => null, "filename" => $file_name, "ext" => $ext]
        );

        if ($result['writer'] instanceof AbstractWriter)
            return $result['writer'];

        switch ($ext)
        {
            case "csv":
                return new CSV\Writer;
            case "ini":
                return new INI\Writer;
            case "json":
                return new JSON\Writer;
            case "phps":
                return new PHPS\Writer;
            case "xml":
                return new XML\Writer;
            case "yaml":
                return new YAML\Writer;
        }
        throw new \DomainException("Unsupported file format: $ext");
    }

    public static function getAvailableWriters()
    {
        $writers = array(
            'text/csv' => CSV\Writer::class,
            'text/ini' => INI\Writer::class,
            'application/json' => JSON\Writer::class,
            'text/x-phpserialized' => PHPS\Writer::class,
            'text/vnd.yaml' => YAML\Writer::class,
            'application/xml' => XML\Writer::class,
        );

        $result = Hook::execute('WASP.FileFormats.GetWriterTypes', ['types' => $writers]);
        return $result['types'] ?: $writers;
    }
}
