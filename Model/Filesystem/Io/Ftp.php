<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\ImportExport\Model\Filesystem\Io;

/**
 * Extended FTP client
 */
class Ftp extends \Magento\Framework\Filesystem\Io\Ftp
{
    /**
     * Returns the last modified time of the given file
     * Note: Not all servers support this feature! Does not work with directories.
     *
     * @param string $filename
     *
     * @see http://php.net/manual/en/function.ftp-mdtm.php
     *
     * @return int
     */
    public function mdtm($filename)
    {
        return @ftp_mdtm($this->_conn, $filename);
    }
}
