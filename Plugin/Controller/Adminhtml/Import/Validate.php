<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Plugin\Controller\Adminhtml\Import;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Firebear\ImportExport\Model\Import;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import\Adapter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Block\Adminhtml\Import\Frame\Result;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;

class Validate
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Firebear\ImportExport\Model\Import
     */
    protected $importModel;

    /**
     * Validate constructor.
     *
     * @param Context          $context
     * @param RequestInterface $request
     *
     * @internal param $Context $ context
     */
    public function __construct(
        Context $context
    ) {
        $this->resultFactory = $context->getResultFactory();
        $this->objectManager = $context->getObjectManager();
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * @param \Magento\ImportExport\Controller\Adminhtml\Import\Validate $subject
     * @param \Closure                                                   $proceed
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\View\Result\Layout
     */
    public function aroundExecute(
        \Magento\ImportExport\Controller\Adminhtml\Import\Validate $subject,
        \Closure $proceed
    ) {
        $data = $subject->getRequest()->getPostValue();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        /** @var $resultBlock Result */
        $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');

        if ($data) {
            // common actions
            $resultBlock->addAction(
                'show',
                'import_validation_container'
            );

            /** @var $import \Magento\ImportExport\Model\Import */
            $import = $this->getImportModel()->setData($data);
            try {
                $sourcePath = $import->uploadSource();
                $directory = $this->objectManager->create(\Magento\Framework\Filesystem::class)
                    ->getDirectoryWrite(DirectoryList::ROOT);

                if (stripos($sourcePath, '.txt')) {
                    $source = new \Firebear\ImportExport\Model\Import\Source\Txt(
                        $sourcePath,
                        $directory,
                        $data[$import::FIELD_FIELD_SEPARATOR]
                    );
                } else {
                    $source = Adapter::findAdapterFor($sourcePath, $directory, $data[$import::FIELD_FIELD_SEPARATOR]);
                }

                $this->processValidationResult($import->validateSource($source), $resultBlock);
            } catch (LocalizedException $e) {
                $resultBlock->addError($e->getMessage());
            } catch (\Exception $e) {
                $resultBlock->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
            }
            return $resultLayout;
        } elseif ($this->getRequest()->isPost() && !$this->getRequest()->getFiles()->count()) {
            $resultBlock->addError(__('The file was not uploaded.'));
            return $resultLayout;
        }
        $this->messageManager->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }

    /**
     * @param bool $validationResult
     * @param Result $resultBlock
     * @return void
     */
    private function processValidationResult(
        $validationResult,
        $resultBlock
    ) {
        $import = $this->getImportModel();
        if (!$import->getProcessedRowsCount()) {
            if (!$import->getErrorAggregator()->getErrorsCount()) {
                $resultBlock->addError(__('This file is empty. Please try another one.'));
            } else {
                foreach ($import->getErrorAggregator()->getAllErrors() as $error) {
                    $resultBlock->addError($error->getErrorMessage());
                }
            }
        } else {
            $errorAggregator = $import->getErrorAggregator();
            if (!$validationResult) {
                $resultBlock->addError(
                    __('Data validation failed. Please fix the following errors and upload the file again.')
                );
                $this->addErrorMessages($resultBlock, $errorAggregator);
            } else {
                if ($import->isImportAllowed()) {
                    $resultBlock->addSuccess(
                        __('File is valid! To start import process press "Import" button'),
                        true
                    );
                } else {
                    $resultBlock->addError(__('The file is valid, but we can\'t import it for some reason.'));
                }
            }
            $resultBlock->addNotice(
                __(
                    'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                    $import->getProcessedRowsCount(),
                    $import->getProcessedEntitiesCount(),
                    $errorAggregator->getInvalidRowsCount(),
                    $errorAggregator->getErrorsCount()
                )
            );
        }
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $resultBlock
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return $this
     */
    protected function addErrorMessages(
        \Magento\Framework\View\Element\AbstractBlock $resultBlock,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        if ($errorAggregator->getErrorsCount()) {
            $message = '';
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                $message .= ++$counter . '. ' . $error . '<br>';
                if ($counter >= \Magento\ImportExport\Controller\Adminhtml\ImportResult::LIMIT_ERRORS_MESSAGE) {
                    break;
                }
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $error) {
                    $message .= $error->getErrorMessage()
                        . ' <a href="#" onclick="$(this).next().show();$(this).hide();return false;">'
                        . __('Show more') . '</a><div style="display:none;">' . __('Additional data') . ': '
                        . $error->getErrorDescription() . '</div>';
                }
            }
            try {
                $resultBlock->addNotice(
                    '<strong>' . __('Following Error(s) has been occurred during importing process:') . '</strong><br>'
                    . '<div class="import-error-wrapper">' . __('Only the first 100 errors are shown. ')
                    . '<a href="'
                    //. $this->createDownloadUrlImportHistoryFile($this->createErrorReport($errorAggregator))
                    . '">' . __('Download full report') . '</a><br>'
                    . '<div class="import-error-list">' . $message . '</div></div>'
                );
            } catch (\Exception $e) {
                foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                    $resultBlock->addError($errorMessage);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator
     * @return array
     */
    protected function getErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode . ' ' . __('in row(s):') . ' ' . implode(', ', $rows);
        }
        return $messages;
    }

    /**
     * @return Import
     * @deprecated
     */
    public function getImportModel()
    {
        if (!$this->importModel) {
            $this->importModel = $this->objectManager->get(Import::class);
        }
        return $this->importModel;
    }

    /**
     * @param $errorAggregator
     * @return mixed
     */
    protected function getSystemExceptions($errorAggregator)
    {
        return $errorAggregator->getErrorsByCode([AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
    }
}