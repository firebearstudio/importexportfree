<?php

namespace Firebear\ImportExport\Model\Import\Source;

use Braintree\Exception;

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
            error_log("/" . $this->_delimiter . "/");
            error_log(json_encode($parsed));
        } catch (\Exception $e) {
            $parsed = false;
        }

        $checkerEnclosure = false;
        $resultArray = [];

        if (is_array($parsed) && count($parsed) != $this->_colQty) {
            foreach ($parsed as $key => $item) {
                $strpos = strpos($item, $this->_enclosure);
                $strripos = strripos($item, $this->_enclosure);

                if ($checkerEnclosure !== false) {
                    $resultArray[$checkerEnclosure] .= $item;
                } else {
                    $resultArray[$key] = $item;
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




        return $resultArray ? $this->removeEnclosure($resultArray) : $this->removeEnclosure($parsed);
    }

    protected function removeEnclosure($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as &$item) {
            $item = str_replace('"', "", $item);
        }

        return $array;
    }

    public function rewind()
    {
        $this->_file->seek(0);
        $this->_getNextRow();
        parent::rewind();
    }
}
