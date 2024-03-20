[![Packagist](https://img.shields.io/packagist/v/magestyapps/module-web-images.svg)](https://packagist.org/packages/magestyapps/module-web-images) [![Packagist](https://img.shields.io/packagist/dt/magestyapps/module-web-images.svg)](https://packagist.org/packages/magestyapps/module-web-images)

# Upload SVG and WebP images in Magento 2

This extension for Magento 2 allows uploading SVG and WebP images in the following sections:
* wysiwyg editor in static blocks and pages
* theme logo and favicon
* wysiwyg editor on product and category edit pages (description, summary, etc.)
* product media gallery
* category image upload


**IMPORTANT:** *if you need to upload any other image format or you need to upload it in any other Magento 2 area - please just drop us a line at [alex@magestyapps.com](mailto:alex@magestyapps.com?subject=Extend%20MagestyApps_WebImages%20extension) and we will update the extension*

**IMPORTANT:** *if you like the extension, could you please add a star to this GitHub repository in the top right corner. This is really important for us. Thanks.*

## Installation

### Using Composer (recommended)
1) Go to your Magento root folder
2) Download the extension using composer:
    ```
    composer require magestyapps/module-web-images
    ```
3) Run setup commands:

    ```
    php bin/magento setup:upgrade;
    php bin/magento setup:di:compile;
    php bin/magento setup:static-content:deploy -f;
    ```
   
### Manually
1) Go to your Magento root folder:
    
    ```
    cd <magento_root>
    ```
   
2) Copy extension files to *app/code/MagestyApps/WebImages* folder:
    ```
    git clone https://github.com/MagestyApps/module-web-images.git app/code/MagestyApps/WebImages
    ```
    ***NOTE:*** *alternatively, you can manually create the folder and copy the extension files there.*
    
3) Run setup commands:

    ```
    php bin/magento setup:upgrade;
    php bin/magento setup:di:compile;
    php bin/magento setup:static-content:deploy -f;
    ```

### Possible issues
*Problem:* An image gets uploaded to the server but not accessible in browser.

*Solution:* Most likely, this is related to your nginx/apache restrictions. Please, make sure that the requested image extension is allowed by the web server configuration.

## Other Extensions
You can find more useful extensions for Magento 2 by visiting [MagestyApps Official Website](https://www.magestyapps.com/)
