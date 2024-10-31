=== Products CSV Importer for Woocommerce ===
Contributors: simplisticsca, jonboss
Donate Link: https://simplistics.ca
Tags: woocommerce, importer, products, csv
Requires at least: 4.4 or higher
Tested up to: 4.6.1
Stable tag: 1.0.4
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Given a CSV document of the proper template, imports all rows as Woocommerce products. Created by Simplistics Web Design.

= Operation  =
1. Navigate to the ‘Products Importer’ Link in the wordpress administrator menu.
2. Click ‘choose file’ to upload the desired products document (only .csv file type is supported. For details on .csv formatting see ‘csv template format’ below)
3. Click ‘submit’ to run the import. The loading screen will appear, please wait while the import finished.
4. The importer will display the number of records successfully imported. You can view these in the ‘Products’ tab.

= Required Plugins =
* [Woocommerce] is required to use this plugin, downloadable here (https://en-ca.wordpress.org/plugins/woocommerce/)

= CSV Template Format =
Included with this plugin is a template file entitled ‘products_template.csv’ to be used to format your products for upload. The Columns in that CSV expect the following data:

* A: A unique reference ID used for your products. This must be an integer - it is used to link your products to the proper post ID.
* B: Categories for this product. This should be formatted as follows:
	- Each Category Name must be separated with a ‘|’
	The Categories need not exist and will be created for you if the string is written in this way. This plugin does not currently support heirarchical categories - you will have to assign categories to parents from the Woocommerce admin panel yourself. Alternatively, creating the categories ahead of time and only referencing the child category will link your product to the correct category.
* C: Product Weight (as a number)
* D: Product Name. The product’s name is used to determine variable products - so any product you wish to be a variation should have the same product name as its parent. The first product of its name will create the parent product as well as a variation with the given attributes - indicating a parent row is not necessary.
* E: Product SKU
* F: Product Description
* G: Product Images. Images will be searched for in the database and attached to the product if they already exist. Multiple images should be separated by a ‘|’, the first image in the string being the product thumbnail. The other images will be added to the product’s gallery. Images may include or disclude the file extension.
* H: Inventory. Please note that if you don’t wish to update the product inventory in this way, you can deselect the ‘update stock’ option in the plugin’s options page.
* I: Product Regular Price
* J: Product Sale Price, if applicable
* K-onwards: All columns from K onwards are parsed as product attributes. The column names will be created as new attributes if they do not already exist. If a product does not use that attribute, simply leave the field blank. Attributes of a Variable Product will automatically be set to ‘use for variation’

= Adding Custom Meta To Your Products =
It may be necessary to add extra information to your product for some reason. If you require this, you can do so by leaving an empty column after your product attributes, followed by any custom meta values you require. The titles of any custom meta columns will be translated into a meta_key and stored in the postmeta table associated with the product or variation.

= Customization Hooks =
action ’wpci_importer_finished’ - This hook fires immediately after the import finishes, and before the page is redirected.

== Installation ==
1. Upload the ‘products-ccv-importer’ to the plugins directory (‘wp-content/plugins’) of your website
2. Activate the plugin by clicking the ‘Plugins’ link in your wordpress admin area, and clicking ‘Activate’ next to this plugin’s name

== Frequently Asked Questions ==

= Feedback and Support =
If you have any questions, feedback or require support you can email: info@simplistics.ca

== Screenshots ==

1. Simply activate the plugin and import your products through the easy-to-use interface.
2. Simple .csv template is included in the plugin. Only one, simply formatted csv file is required for a full-featured product import.
3. Click 'Import' -> wait while the plugin runs -> Done.

== Changelog ==

= 1.0 =
* First Plugin Version

= 1.0.3 =
* Update to how images are imported. Fixed a bug where images that were uploaded multiple times would not attach to imported products.

= 1.0.4 =
* Fixed a bug that caused the support documents not to download properly from the interface.

== Upgrade Notice ==

= 1.0 =
* First Plugin Version