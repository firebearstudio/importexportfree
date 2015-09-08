<?php
namespace Firebear\ImportExport\Plugin\Block\Import;

class Form {

    public function beforeSetForm(\Magento\ImportExport\Block\Adminhtml\Import\Edit\Form $subject, $form)
    {
        // fieldset for file uploading
        $fieldsets['dropbox'] = $form->addFieldset(
            'dropbox_fieldset',
            ['legend' => __('Import From Dropbox')]
        );
        $fieldsets['dropbox']->addField(
            'dropbox_access_token',
            'text',
            [
                'name' => 'dropbox_access_token',
                'label' => __('Access Token'),
                'title' => __('Access Token'),
                'required' => true,
                'class' => 'input-text',
                'note' => __(
                    'You need to create new Dropbox API App in your App Console.
                        See <a href="https://www.dropbox.com/developers/apps" target="_blank">https://www.dropbox.com/developers/apps</a>'
                )
            ]
        );
        $fieldsets['dropbox']->addField(
            'dropbox_file_path',
            'text',
            [
                'name' => 'dropbox_file_path',
                'label' => __('File Path'),
                'title' => __('File Path'),
                'required' => true,
                'class' => 'input-text'
            ]
        );

        return [$form];
    }
}