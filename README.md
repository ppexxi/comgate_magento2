## Synopsis
An extension to add integration with ComGate Payment Gateway

## Upload
Copy ComGate to *[magento]/app/code* (create directory [magento]/app/code, if it doesn't exist)

## Compile
php bin/magento setup:upgrade;  
php bin/magento setup:di:compile;  
php bin/magento setup:static-content:deploy -f;  
php bin/magento cache:clean;  
php bin/magento cache:flush  

## Settings
Stores --> Configuration --> Sales -> Payment methods (*https://[Magento Base URL]/admin/admin/system_config/edit/section/payment/key/[token]*) --> ComGate Payment Gateway

## ComGate Settings
URL Paid: *[Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}*  
URL Cancelled: *[Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}*  
URL Pending: *[Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}*  
URL Payment Result Notification: *[Magento Base URL]/comgate/payment/ipn*  
