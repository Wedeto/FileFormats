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

use Wedeto\Util\Hook;
use Wedeto\Util\TypedDictionary;
use Wedeto\Util\Type;

use Wedeto\IO\File;
use Wedeto\Util\DI\DI;
use Wedeto\Util\DI\Factory;
use Wedeto\Util\DI\Injector;

class WriterFactory implements Factory
{
    public function produce(string $class, array $args, string $selector, Injector $injector)
    {
        if (!isset($args['filename']))
            throw new \InvalidArgumentException('Missing argument filename');

        return static::factory($args['filename']);
    }

    public static function factory($file_name)
    {
        if (is_string($file_name))
            $file = new File($file_name);
        elseif ($file_name instanceof File)
            $file = $file_name;
        else
            throw new \InvalidArgumentException("Provide a file or file name to WriterFactory");

        $result = Hook::execute(
            "Wedeto.FileFormats.CreateWriter",
            ["writer" => null, "file" => $file]
        );

        if ($result['writer'] instanceof AbstractWriter)
            return $result['writer'];

        $ext = $file->getExt();
        if (!empty($file))
        {
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
        }
        throw new \DomainException("Could not create writer for file: {$file->getPath()}");
    }

    public static function getAvailableWriters()
    {
        $writers = array(
            'text/csv' => CSV\Writer::class,
            'text/ini' => INI\Writer::class,
            'application/json' => JSON\Writer::class,
            'text/x-phpserialized' => PHPS\Writer::class,
            'text/vnd.yaml' => YAML\Writer::class,
            'application/xml' => XML\Writer::class
        );

        $params = new TypedDictionary(['types' => Type::ARRAY], ['types' => $writers]);
        $result = Hook::execute('Wedeto.FileFormats.GetWriterTypes', $params);
        return $result['types']->toArray();
    }
}

// Register factory in DI to produce writers
DI::getInjector()->registerFactory(Writer::class, new WriterFactory());
