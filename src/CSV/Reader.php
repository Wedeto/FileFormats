<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
It is published under the MIT Open Source License.

Copyright 2017-2018, Egbert van der Wal

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

use Iterator;

use Wedeto\FileFormats\AbstractReader;
use Wedeto\IO\IOException;
use Wedeto\Util\Encoding;

/**
 * Read CSV files. This provides a direct CSV reader that converts a CSV file
 * directly to an array of records, but alternatively, you can traverse the
 * file record by record, by opening the file in the constructor and traversing
 * using foreach. This allows to handle large files without loading everything
 * into memory.
 */
class Reader extends AbstractReader implements Iterator
{
    protected $file_handle;
    protected $line_number;
    protected $current_line = null;
    protected $has_bom = null;

    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $escape_char = '\\';
    protected $read_header = true;

    protected $header = null;

    /**
     * Create the CSV Reader from a file name
     *
     * @param $file_name The name of a file to open
     */
    public function __construct($file_name = null)
    {
        if ($file_name !== null)
            $this->file_handle = fopen($file_name, "r");
    }

    /**
     * Set the delimiter character
     *
     * @param string $delimiter The delimiter character
     * @return $this Provides fluent interface
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return string The delimiter character, usually ,
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the enclosure / quoting character
     *
     * @param string $enclosure The enclose character
     * @return $this Provides fluent interface
     */
    public function setEnclosure(string $enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * @return string The enclosure character, quotes.
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * Set the escape character
     *
     * @param string $escape The escape character
     * @return $this Provides fluent interface
     */
    public function setEscapeChar(string $escape)
    {
        $this->escape_char = $escape;
        return $this;
    }

    /**
     * @return string The escape chararacter
     */
    public function getEscapeChar()
    {
        return $this->escape_char;
    }

    /**
     * Set if the first row should be read as header
     * @param bool $read_header True to read the first row as header
     * @return $this Provides fluent interface
     */
    public function setReadHeader(bool $read_header)
    {
        $this->read_header = $read_header;
        return $this;
    }

    /**
     * @return bool Whether the first row will be read as header
     */
    public function getReadHeader()
    {
        return $this->read_header;
    }
    
    /**
     * Read CSV data from a file
     *
     * @param string $file_name The name of the file to read
     * @return array The parsed data
     */
    public function readFile(string $file_name)
    {
        $data = array();
        $this->file_handle = fopen($file_name, "r");

        foreach ($this as $row)
            $data[] = $row;

        return $data;
    }

    /**
     * Read CSV from a stream.
     *
     * @param resource $file_handle The stream to read CSV from
     * @return array The parsed data
     */
    public function readFileHandle($file_handle)
    {
        if (!is_resource($file_handle))
            throw new \InvalidArgumentException("No file handle was provided");

        $data = array();
        $this->file_handle = $file_handle;

        foreach ($this as $row)
            $data[] = $row;

        return $data;
    }

    /**
     * Read CSV from a string. This will write the data to a temporary stream
     *
     * @param string $data The data to read as CSV
     * @return array The parsed data
     */
    public function readString(string $data)
    {
        $this->file_handle = fopen('php://temp', 'rw');
        fwrite($this->file_handle, $data);

        $data = array();
        foreach ($this as $row)
            $data[] = $row;

        return $data;
    }

    /**
     * Rewind the file handle to the start
     */
    public function rewind()
    {
        rewind($this->file_handle);
        $this->line_number = 0;
        $this->current_line = null;
        $this->header = null;
    }

    /**
     * @return array The current line of CSV
     */
    public function current()
    {
        if ($this->current_line === false)
            return false;

        if ($this->current_line === null)
            $this->readLine();

        return $this->current_line;
    }

    /**
     * @return int The line number of the current line
     */
    public function key()
    {
        return $this->line_number;
    }

    /**
     * Get ready to the next line
     */
    public function next()
    {
        $this->current_line = null;
        ++$this->line_number;
    }

    /**
     * @return bool True if the iterator is valid - when a valid line was read, false if not.
     */
    public function valid()
    {
        if ($this->current_line === null)
            $this->readLine();

        return $this->current_line !== false;
    }

    /**
     * @return bool True when a BOM was read, false if not
     */
    public function hasBOM()
    {
        return $this->has_bom;
    }
    
    /**
     * Read a line from the CSV Stream, available from current() afterwards.
     */
    protected function readLine()
    {
        if ($this->line_number === 0)
        {
            $st = ftell($this->file_handle);
            if ($st === 0)
            { 
                // Attempt to read a Byte Order Mark only if this file handle is at the start of the file
                $utf8_bom = Encoding::getBOM('UTF8');
                $maybe_bom = fread($this->file_handle, 3);
                $this->has_bom = $utf8_bom === $maybe_bom;

                // Rewind if the first three characters are not the UTF8 BOM
                if (!$this->has_bom)
                    fseek($this->file_handle, 0);
            }
        }

        if ($this->read_header && empty($this->line_number) && $this->header === null)
            $this->header = fgetcsv($this->file_handle, 0, $this->delimiter, $this->enclosure, $this->escape_char);

        $line = fgetcsv($this->file_handle, 0, $this->delimiter, $this->enclosure, $this->escape_char);

        if ($line === false)
        {
            $this->current_line = false;
            return;
        }

        if ($this->header !== null)
        {
            $row = array();
            foreach ($line as $idx => $col)
            {
                $name = isset($this->header[$idx]) ? $this->header[$idx] : null;
                if ($name)
                    $row[$name] = $col;
                else
                    $row[] = $col;
            }
            $this->current_line = $row;
        }
        else
            $this->current_line = $line;
    }
}
