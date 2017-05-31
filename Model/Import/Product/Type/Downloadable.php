<?php
namespace Firebear\ImportExport\Model\Import\Product\Type;

/**
 * Class Downloadable
 */
class Downloadable extends \Magento\DownloadableImportExport\Model\Import\Product\Type\Downloadable
{
    /**
     * Get fill data options with key link
     *
     * @param array $options
     * @return array
     */
    protected function fillDataTitleLink(array $options)
    {
        $result = [];
        $select = $this->connection->select();
        $select->from(
            ['dl' => $this->_resource->getTableName('downloadable_link')],
            [
                'link_id',
                'product_id',
                'sort_order',
                'number_of_downloads',
                'is_shareable',
                'link_url',
                'link_file',
                'link_type',
                'sample_url',
                'sample_file',
                'sample_type'
            ]
        );
        $select->joinLeft(
            ['dlp' => $this->_resource->getTableName('downloadable_link_price')],
            'dl.link_id = dlp.link_id AND dlp.website_id=' . self::DEFAULT_WEBSITE_ID,
            ['price_id']
        );
        $select->where(
            'product_id in (?)',
            $this->productIds
        );
        $existingOptions = $this->connection->fetchAll($select);
        foreach ($options as $option) {
            $existOption = $this->downloadableHelper->fillExistOptions(
                $this->dataLinkTitle,
                $option,
                $existingOptions
            );
            if (!empty($existOption)) {
                $result['title'][] = $existOption;
            }
            $existOption = $this->downloadableHelper->fillExistOptions(
                $this->dataLinkPrice,
                $option,
                $existingOptions
            );
            if (!empty($existOption)) {
                $result['price'][] = $existOption;
            }
        }
        return $result;
    }

    /**
     * Uploading files into the "downloadable/files" media folder.
     * Return a new file name if the same file is already exists.
     *
     * @param string $fileName
     * @param string $type
     * @param bool $renameFileOff
     * @return string
     */
    protected function uploadDownloadableFiles($fileName, $type = 'links', $renameFileOff = false)
    {
        try {
            $res = $this->uploaderHelper->getUploader(
                $type,
                $this->_entityModel->getParameters()
            )->move($fileName, $renameFileOff);
            return $res['file'];
        } catch (\Exception $e) {
            $this->_entityModel->addRowError(
                $this->_messageTemplates[self::ERROR_MOVE_FILE] . '. ' . $e->getMessage(),
                $this->rowNum
            );
            return '';
        }
    }
}
