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

namespace Wedeto\FileFormats\CSV;

use Wedeto\Util\Functions as WF;
use Wedeto\FileFormats\AbstractWriter;
use Wedeto\Util\Encoding;

/**
 * Writes CSV to files or streams.
 */
class Writer extends AbstractWriter
{
    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $escape_char = '\\';
    protected $write_header = true;
    protected $write_bom = false;

    /**
     * Set the field delimiter, usually a comma ,
     * 
     * @param string $delimiter The delimiter to user to separate fields
     * @return $this Provides fluent interface
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return string The field delimiter
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the enclosure character, enclosing strings
     * 
     * @param string $enclosure The enclosure string, usually a double quote
     * @return $this Provides fluent interface
     */
    public function setEnclosure(string $enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * @return string The enclosure character
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * Set the escape character
     *
     * @param string $escape The Escape character to escape quotes
     * @return $this Provides fluent interface
     */
    public function setEscapeChar(string $escape)
    {
        $this->escape_char = $escape;
        return $this;
    }

    /**
     * @return string The Escape character used to escape quotes
     */
    public function getEscapeChar()
    {
        return $this->escape_char;
    }

    /**
     * Set if a header should be written.
     * @param bool $write_header True when the header should be written, false when not
     * @return $this Provides fluent interface
     */
    public function setWriteHeader(bool $write_header)
    {
        $this->write_header = $write_header;
        return $this;
    }

    /** 
     * @return bool True when a header will be written, false when not
     */
    public function getWriteHeader()
    {
        return $this->write_header;
    }

    /**
     * Set if a BOM should be written
     *
     * @param bool $write True when a BOM will be written, false when not
     * @return $this Provides fluent interface
     */
    public function setWriteBOM(bool $write)
    {
        $this->write_bom = $write;
        return $this;
    }

    /**
     * @return bool True when a BOM will be written, false when not
     */
    public function getWriteBOM()
    {
        return $this->write_bom;
    }

    /**
     * Format the data into CSV
     * @param mixed $data Traversable data
     */
    protected function format($data, $file_handle)
    {
        $header = false;
        foreach ($data as $idx => $row)
        {
            $row = WF::cast_array($row);
            $this->validateRow($row);

            if ($this->write_bom && ftell($file_handle) === 0)
                fwrite($file_handle, Encoding::getBOM('UTF8'));

            if (!$header && $this->write_header)
            {
                $keys = array_keys($row);
                fputcsv($file_handle, $keys, $this->delimiter, $this->enclosure, $this->escape_char);
                $header = true;
            }
            fputcsv($file_handle, $row, $this->delimiter, $this->enclosure, $this->escape_char);
        }
    }

    /**
     * Make sure that the data is not nested more than 1 level deep as CSV does not support that.
     * @param array $row The row to validate
     * @throws InvalidArgumentException When the array contains arrays
     */
    protected function validateRow(array $row)
    {
        foreach ($row as $k => $v)
        {
            if (WF::is_array_like($v))
                throw new InvalidArgumentException("CSVWriter does not support nested arrays");
        }
    }
}
