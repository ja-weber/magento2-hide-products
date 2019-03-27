# Magento 2 Extension. Hide Products by Customer Groups
After installing and configuring the extension, products will be hidden for specific customer groups.

# Installation
To install use the following composer command:

    composer require weberinformatics/magento2-hide-products

Then enable the module:

    bin/magento module:enable WeberInformatics_HideProducts
    bin/magento setup:upgrade
    
And clean the cache:

    bin/magento cache:clean

# Configuration
Add rules for hiding products in **`etc/config.json`**. Example:

    {
      "hideRules": [
        {
          "productId": 1,
          "customerGroupIds": [1, 2]
        },
        {
          "productId": 3,
          "customerGroupIds": [4, 5]
        }
      ]
    }