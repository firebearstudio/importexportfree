<?php

namespace Firebear\ImportExport\Model\Import\Source;

class Txt extends \Magento\ImportExport\Model\Import\AbstractSource
{
    protected $_file;

    protected $_delimiter = ',';

    protected $_enclosure = '';

    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Read $directory,
        $delimiter = ',',
        $enclosure = '"'
    ) {
        register_shutdown_function([$this, 'destruct']);
        try {
            $this->_file = $directory->openFile($directory->getRelativePath($file), 'r');
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \LogicException("Unable to open file: '{$file}'");
        }
        if ($delimiter) {
            $this->_delimiter = $delimiter;
        }
        $this->_enclosure = $enclosure;
        parent::__construct($this->_getNextRow());
    }

    public function destruct()
    {
        if (is_object($this->_file)) {
            $this->_file->close();
        }
    }

    protected function _getNextRow()
    {
        try {
            $parsed = preg_split("/" . $this->_delimiter . "/", $this->_file->readLine(0, "\n"));
        } catch (\Exception $e) {
            $parsed = false;
        }

        $checkerEnclosure = false;
        $deletedItems = 0;

        if (is_array($parsed) && count($parsed) != $this->_colQty) {
            foreach ($parsed as $key => $item) {
                $strpos = strpos($item, $this->_enclosure);
                $strripos = strripos($item, $this->_enclosure);

                if ($checkerEnclosure !== false) {
                    $parsed[$checkerEnclosure] = $parsed[$checkerEnclosure] . $item;
                    array_splice($parsed, $key - $deletedItems, 1);
                    $deletedItems++;
                }

                if (strpos($item, $this->_enclosure) !== false && $strpos == $strripos) {
                    if ($checkerEnclosure === false) {
                        $checkerEnclosure = $key;
                    } else {
                        $checkerEnclosure = false;
                    }
                }
            }
        }

        return is_array($parsed) ? $parsed : [];
    }

    public function rewind()
    {
        $this->_file->seek(0);
        $this->_getNextRow();
        parent::rewind();
    }
}
