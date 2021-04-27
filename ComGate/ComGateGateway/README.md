## Synopsis
An extension to add integration with ComGate Payment Gateway

## Settings
Stores --> Configuration --> Sales -> Payment methods (https://[url]/admin/admin/system_config/edit/section/payment/key/[token]) --> ComGate Payment Gateway

## ComGate Settings
URL Paid: [Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}
URL Cancelled: [Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}
URL Pending: [Magento Base URL]/comgate/payment/result/?id=${id}&refId=${refId}
URL Payment Result Notification: [Magento Base URL]/comgate/payment/ipn
