# Upload SVG images in Magento 2

This extension for Magento 2 allows uploading SVG images in the following sections:
* wysiwyg editor in static blocks and pages
* wysiwyg editor on product edit page
* theme logo and favicon


**IMPORTANT:** *if you need to upload any other image format or you need to upload it in any other Magento 2 area - please just drop us a line at [alex@magestyapps.com](mailto:alex@magestyapps.com?subject=Extend%20MagestyApps_WebImages%20extension) and will update the extension*

## Installation (via Composer)
1) Go to your Magento root folder
2) Downaload the extension using composer:
    ```
    composer require magestyapps/module-web-images
    ```
3) Run setup commands:

    ```
    php bin/magento setup:upgrade;
    php bin/magento setup:di:compile;
    php bin/magento setup:static-content:deploy -f;
    ```

## Manual Installation
1) Go to your Magento root folder and create a new folder:
    
    ```
    app/code/MagestyApps/AdvancedBreadcrumbs
    ```
   
2) Copy extension files to the folder

3) Run setup commands:

    ```
    php bin/magento setup:upgrade;
    php bin/magento setup:di:compile;
    php bin/magento setup:static-content:deploy -f;
    ```
