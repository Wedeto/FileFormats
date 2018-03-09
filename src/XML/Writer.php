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

use XMLWriter;

use Wedeto\Util\Functions as WF;
use Wedeto\FileFormats\AbstractWriter;

class Writer extends AbstractWriter
{
    private $root_node = "Response";

    public function setRootNode(string $node_name)
    {
        $this->root_node = $node_name;
        return $this;
    }

    public function getRootNode()
    {
        return $this->root_node;
    }

    protected function format($data, $file_handle)
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument();

        $writer->startElement($this->root_node);
        $this->formatRecursive($writer, $data, $this->root_node);
        $writer->endElement();
        
        $writer->endDocument();
        fwrite($file_handle, $writer->outputMemory());
    }

    protected function formatRecursive(\XMLWriter $writer, $data, $parent_name)
    {
        if (WF::is_sequential_array($data))
        {
            $first = true;
            $remain = count($data);
            foreach ($data as $idx => $value)
            {
                // The first element has already been opened
                if (!$first)
                    $writer->startElement($parent_name);
                else
                    $first = false;

                if (WF::is_sequential_array($value))
                {
                    // wrap to avoid losing structure
                    $writer->startElement($parent_name); 
                    $this->formatRecursive($writer, $value, $parent_name);
                    $writer->endElement();
                }
                elseif (is_array($value))
                    $this->formatRecursive($writer, $value, $parent_name);
                else
                    $writer->text(WF::str($value));

                // Don't close the last element
                if (--$remain > 0)
                    $writer->endElement();
            }
            return;
        }

        foreach ($data as $key => $value)
        {
            if (substr($key, 0, 1) == "_")
            {
                $writer->writeAttribute(substr($key, 1), (string)$value); 
            }
            else
            {
                $is_numeric_key = is_int($key);
                if ($is_numeric_key)
                {
                    $idx = $key;
                    $key = $parent_name;
                }

                $writer->startElement($key);
                if ($is_numeric_key)
                    $writer->writeAttribute("index", $idx);

                if (is_array($value))
                {
                    if (isset($value['_content_']))
                    {
                        $writer->text(WF::str($value['_content_']));
                        unset($value['_content_']);
                    }
                    $this->formatRecursive($writer, $value, $key);
                }
                else
                    $writer->text(WF::str($value));
                $writer->endElement();
            }
        }
    }
}

// @codeCoverageIgnoreStart
\Wedeto\Util\Functions::check_extension('xml', XMLWriter::class, null);
// @codeCoverageIgnoreEnd
