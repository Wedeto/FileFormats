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

namespace Wedeto\FileFormats\XML;

use XMLReader;
use JSONSerializable;

use Wedeto\FileFormats\AbstractReader;
use Wedeto\IO\IOException;

class Reader extends AbstractReader
{
    public function readFile(string $file_name)
    {
        $reader = new XMLReader;
        $reader->open($file_name);

        $data = $this->toArray($reader);
        $reader->close();

        return $data;
    }

    public function readString(string $data)
    {
        $reader = new XMLReader;
        $reader->XML($data);

        $contents = $this->toArray($reader);
        $reader->close();

        return $contents;
    }

    public function toArray(XMLReader $reader)
    {
        $data = array();

        libxml_use_internal_errors(true);
        $root = new XMLNode;
        $cur = null;

        $read_anything = false;
        while ($reader->read())
        {
            $read_anything = true;
            if ($reader->nodeType === XMLReader::ELEMENT)
            {
                if ($cur === null)
                {
                    $root->name = $reader->name;
                    $cur = $root;
                }
                else
                {
                    $node = new XMLNode;
                    $node->name = $reader->name;
                    $node->parent = $cur;
                    $cur->children[] = $node;
                    $cur = $node;
                }

                if ($reader->hasAttributes)
                {
                    $attributes = array();
                    while ($reader->moveToNextAttribute())
                    {
                        $node = new XMLNode;
                        $node->name = "_" . $reader->name;
                        $node->content = $reader->value;
                        $cur->children[] = $node;
                    }
                }

            }
            else if ($reader->nodeType === XMLReader::END_ELEMENT)
            {
                $cur = $cur->parent;
            }
            else if ($reader->nodeType === XMLReader::TEXT)
            {
                $cur->content = $reader->value;
            }
        }

        try
        {
            foreach (libxml_get_errors() as $error)
                throw new XMLException($error);
        }
        finally
        {
            libxml_clear_errors();
        }

        return $root->JSONSerialize();
    }
}

class XMLNode implements JSONSerializable
{
    public $name = null;
    public $parent = null;
    public $children = array();
    public $content = null;

    public function JSONSerialize()
    {
        if (count($this->children) === 0 && !empty($this->content))
            return $this->content;

        $children = array();
        foreach ($this->children as $child)
        {
            if (!isset($children[$child->name]))
                $children[$child->name] = array();
            $children[$child->name][] = $child->JSONSerialize();
        }

        $keys = array_keys($children);
        foreach ($keys as $key)
        {
            if (count($children[$key]) === 1)
                $children[$key] = $children[$key][0];
        }
        return $children;
    }
}

// @codeCoverageIgnoreStart
\Wedeto\Util\Functions::check_extension('xml', XMLReader::class, null);
// @codeCoverageIgnoreEnd
