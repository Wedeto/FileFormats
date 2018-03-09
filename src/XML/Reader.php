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
    /**
     * Read a XML file into an array
     *
     * @param string $filename The file to read
     * @return array The array representation of the XML Data
     */
    public function readFile(string $file_name)
    {
        $reader = new XMLReader;
        $reader->open($file_name);

        $data = $this->toArray($reader);
        $reader->close();

        return $data;
    }

    /**
     * Read XML Data from a string
     *
     * @param string $data The data to read
     * @return array the array representation of the XML data
     */
    public function readString(string $data)
    {
        $reader = new XMLReader;
        $reader->XML($data);

        $contents = $this->toArray($reader);
        $reader->close();

        return $contents;
    }

    /**
     * Convert the XMLReader to an array. Doing this will remove support
     * for node attributes. You can access them under their node name,
     * as _attributename. When attributes are present, a nodes content will
     * be stored as _content_.
     * 
     * @param XMLReader The reader reading the XML
     * @return array An array represeting the XML structure.
     */
    public function toArray(XMLReader $reader)
    {
        $root = $this->parseTree($reader);
        return $root->JSONSerialize();
    }

    /**
     * Parse the XML into an array. This will add all nodes and attributes to
     * a tree of XML nodes. This is only basic XML flattening, for full-blown
     * XML support be sure to use a more fully featured solution like SimpleXML
     * 
     * @param XMLReader $reader The reader reading the XML
     * @return XMLNode The root node
     */
    public function parseTree(XMLReader $reader)
    {
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
                    while ($reader->moveToNextAttribute())
                    {
                        $node = new XMLNode;
                        $node->name = $reader->name;
                        $node->content = $reader->value;
                        $cur->attributes[] = $node;
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

        return $root;
    }
}

/**
 * XMLNode is a very simple representation of an XML node,
 * storing its children, attributes and content.
 * Its JSONSerializable flattens it into an array.
 */
class XMLNode implements JSONSerializable
{
    public $name = null;
    public $parent = null;
    public $children = [];
    public $attributes = [];
    public $content = null;

    public function JSONSerialize()
    {
        if (count($this->children) === 0 && count($this->attributes) === 0 && !empty($this->content))
            return $this->content;

        $children = [];
        foreach ($this->children as $child)
        {
            if (!isset($children[$child->name]))
                $children[$child->name] = [];
            $children[$child->name][] = $child->JSONSerialize();
        }
        
        // Attributes cannot be represented in an array directly, so
        // they are represented as children prefix with a _
        foreach ($this->attributes as $child)
        {
            $name = '_' . $child->name;
            if (!isset($children[$name]))
                $children[$name] = [];
            $children[$name][] = $child->JSONSerialize();
        }

        $keys = array_keys($children);
        foreach ($keys as $key)
        {
            if (count($children[$key]) === 1)
                $children[$key] = $children[$key][0];
        }

        if (!empty($this->content))
        {
            if (count($children))
                $children['_content_'] = $this->content;
            else
                $children = $this->content;
        }

        return $children;
    }
}

// @codeCoverageIgnoreStart
\Wedeto\Util\Functions::check_extension('xml', XMLReader::class, null);
// @codeCoverageIgnoreEnd
