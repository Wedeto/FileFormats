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
use Wedeto\IO\File;
use Wedeto\Util\DI\DI;
use Wedeto\Util\DI\Factory;
use Wedeto\Util\DI\Injector;

class ReaderFactory implements Factory
{
    public function produce(string $class, array $args, string $selector, Injector $injector)
    {
        if (!isset($args['filename']))
            throw new \InvalidArgumentException('Missing argument filename');

        return static::factory($args['filename']);
    }

    public static function factory($filename)
    {
        if (is_string($filename))
            $file = new File($filename);
        elseif ($filename instanceof File)
            $file = $filename;
        else
            throw new \InvalidArgumentException("Provide a file or file name to ReaderFactory");

        $ext = $file->getExt();
        $result = Hook::execute(
            "Wedeto.FileFormats.CreateReader", 
            ['reader' => null, 'file' => $file]
        );

        if ($result['reader'] instanceof AbstractReader)
            return $result['reader'];

        if (!empty($ext))
        {
            switch ($ext)
            {
                case "csv":
                    return new CSV\Reader;
                case "ini";
                    return new INI\Reader;
                case "json":
                    return new JSON\Reader;
                case "phps":
                    return new PHPS\Reader;
                case "xml":
                    return new XML\Reader;
                case "yaml":
                    return new YAML\Reader;
            }
        }
        throw new \DomainException("Could not create reader for file: {$file->getPath()}");
    }
}

// Register factory in DI to produce writers
DI::getInjector()->registerFactory(Reader::class, new ReaderFactory());
