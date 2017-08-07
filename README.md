# Magento 2 - Improved Import / Export extension  

We’ve started a new project aimed at improving the default Magento 2 import / export functionality. Although this process is significantly refined now, it still lacks some important features; therefore we are developing this Magento 2 extension. Now, you can easily import product custom options, product images from remote URL, or tier prices out-of-the-box, but there is still room for improvement. 

The Improved Import / Export Magento 2 extension provides the ability to import a CSV file with product data and product images from a remote FTP server or a cloud storage such as Dropbox, Box, or Google Drive. There is also an opportunity to perform the export of products and images to the same location. Due to a scheduled cron job feature, the extension offers automated product updates from a cloud storage. Thus, you can easily connect your Magento 2 store with 3th party tools, warehouses, product inventory management (PIM) systems, etc. As you can see, Improved Import / Export for Magento 2 is useful for automated product stock status updates as well as any other product attribute updates.

<a href="https://firebearstudio.com/the-improved-import.html" title="Magento 2 Improved Import">
<b>Full version - Improved Import for Magento 2 CE & EE </b></a><br />

## Installation
1. Run:
```
composer require firebear/importexportfree
```
``` 
php -f bin/magento setup:upgrade
```
```
php -f bin/magento setup:static-content:deploy
```
```
php -f bin/magento cache:clean
```

<h2>Improved Import features</h2> 

<h3>Free version features</h3>

The free version of Improved Import offers the following features:

- product images import from Dropbox or via Custom URL and FTP 
- csv file import from Dropbox or via Custom URL and FTP 
- debug import in var/log/firebear-import.log
<h3>Full paid version features</h3>

The paid one adds:
- Google Drive and Box integration (the free version supports only Dropbox)
- Scheduled cron job for csv file import from all supported sources. This is a great solution for keeping product stocks and data updated automatically. 
- CSV file product export to Box, Google Drive, Dropbox, or other sources via FTP
- Product images export to Box, Google Drive, Dropbox, or other sources via FTP
- Dedicated simple categories import from CSV file (by default in M2 categories can be imported only together with assigned products)
- Import product attaibutes and attribute values on the fly creation 
- Import downloadable products 
- Import fields mapping (in Magento 1.x style!) – you can map Magento 2 product attributes to any custom CSV column in your file! Fully flexible and powerful way to import custom data structures in Magento!
- Categories import 
- 1 year of free support and help with import from FireBear Team (also extra consultancy and customisation packages available!)  

<a href="https://firebearstudio.com/the-improved-import.html" title="Magento 2 Improved Import">
<b>Buy Improved Import for Magento 2 CE & EE </b></a><br />

<b>Full version of Imroved Import in action:</b>

<a href="https://firebearstudio.com/the-improved-import.html" title="Magento 2 Improved Import"><img src="https://firebearstudio.com/media/wysiwyg/magento2-cron-import-custom-mapping.gif"></a>

For further information, read this post: <a href="https://firebearstudio.com/blog/the-improved-import-export-magento-2-extension-by-firebear.html" title="The Improved Import / Export Magento 2 Extension by Firebear" ><strong>The Improved Import / Export Magento 2 Extension by Firebear</strong></a>

<a href="https://firebearstudio.com/blog/improved-import-magento-2-extension-manual.html"><b>Improved Import Extension manual and integration guide</b></a>

<p><strong>Looking for some specific import feature , modification or connection to Magento 2? - <a href="https://firebearstudio.com/contacts" target="_blank">Contact us</a> to discuss and get specific complex import solutions!&nbsp;</strong></p>

<h3 style="text-align: justify;">Improved Import for Magento 2 upcoming features roadmap</h3>
Since we are continuously working on Improved Import features following community and merchant needs, here are upcoming features which we are working on now.

After purchasing the extension, you will receive free upgrades during one year and also will get 50% discount for the second year upgrades! This mean you can purchase extension now to use current features and get free updates with powerful new features!
<ul>
	<li>CSV mapping presets for Magento 1.x CE &amp; EE , Shopify , BigCommerce , WooCommerce , Prestashop, and other major ecommerce systems - to make migration to Magento 2 and sync with the platform extremely easy!</li>
	<li>API connection between Magento 2 and Shopify , BigCommerce, and other cloud-based SAAS ecommerce platforms - effortlessly import and export products, customers, and other data on the fly - without creating CSV files! Setup synchronization between your Magento 2 store and other ecommerce platforms in a few clicks;</li>
	<li>Improved Import auto-upgrade - built-in system to keep your Improved Import copy up to date and get new import features, bug fix, and patches in Magento 2 instantly!</li>
	<li>Unzip / untar archive with a CSV file before import - flexible import of large compressed data;</li>
	<li>Order export / import - CSV & XML </li>
	<li>Import products, categories, customers from XML files with flexible mapping</li>
	<li>Export mapping - all import mapping flexibility during data export from Magento 2 - create any kind of CSV, XML, and TXT files to export product, categories, orders, and customers from Magento 2!</li>
	<li>Custom data structure mapping for categories, customers, discount codes, catalog price rules, import! Full flexibility of data import to Magento 2!</li>
	<li>Product data auto translation during import by Google or Bing - great for import to different store views;</li>
	<li>Integration with API of data crawling services, such as https://www.import.io/. Crawl product data from any source and import it directly to Magento 2 - this oppens nearly endless possibilities!</li>
	<li>MS Excel XLSX files native import and mapping - processing of complex Excel files with multiple pages, flexible file files mapping to Magento 2 products , customers and orders striucture </li>
	<li>Full covarage by unit and integrations tests </li>
	<li>Extension is submitted and will be available soon on Magento Marketplace</li>
	<li>API for Magento 2 Import and Improved Import functionality</li>
	<li>Import from Google Sheet (Google Drive Table) - dynamically manage Magento 2 products catalog in the cloud and trigger import</li>
	<li>JSON format support for import and export products , orders etc</li>
	<li>Auto create configurable product during the import from simple products by SKU pattern</li>
	<li>Option to delete / disable products which is not included on CSV file or specific import job</li>
	<li>API connection to external SAP, ERP, PIM, CRM systems</li>
	<li>Combine import / export jobs to queue with dependencies and custom logic between</li>
	<li>Multiple files and sources input for one import / export job - merge and split data and files on the fly, advanced data modificators and processors (export only products with stock less than X etc.</li> 
</ul>

<a href="https://firebearstudio.com/blog/the-complete-guide-to-magento-2-product-import-export.html">Magento 2 Import Guide</a>

<a href="https://firebearstudio.com/the-improved-import.html" title="Magento 2 Improved Import"><img src="https://firebearstudio.com/files/m2import/magento2-dropbox-box-drive-ftp-products-images-import.png" alt="Magento 2 import from Dropbox, Box, Google, FTP" /></a>

<img src="https://firebearstudio.com/files/m2import/magento-import-dropbox.png" alt="Magento 2 import from Dropbox, Box, Google, FTP" />


