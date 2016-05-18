Shopify-PHP-Wrapper
==================

Simple PHP Shopify API Plugin based on <https://github.com/uniacid/PHP-Shopify-Plugin>

Usage
=====
```php
include "Shopify.php";
$shopify = new Shopify();

/* Create Customer */
$result = $shopify->createCustomer(array(
    "customer"=>array(
        "first_name"=>"Bob",
        "last_name"=>"Smith",
        "email"=>"bob@example.com",
        "verified_email"=>true,
        "password"=>"B0bW@SH3R3",
        "password_confirmation"=>"B0bW@SH3R3",
        "send_email_welcome"=>false
    )

));

$customerID = $result->data['customer']['id'];
```

Make sure you set your api key, secret, password, and base url in the Shopify.php file.

## License
See License file  
Spoiler Alert: MIT License
