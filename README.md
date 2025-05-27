A Simple eBay implementation for Laravel.

⚠️ **This package is under development.**

## Installation
Install the package through [Composer](https://getcomposer.org).

```shell
composer require andyts93/laravel-ebay
```

## Configuration
Publish the config file:
```shell
php artisan vendor:publish --tag=ebay-config
```
Follow the [official eBay guide](https://developer.ebay.com/api-docs/static/gs_ebay-rest-getting-started-landing.html) to create your keys and the test account. Edit your .env file to add these mandatory fields:
```text
EBAY_CLIENT_ID=
EBAY_CLIENT_SECRET=
EBAY_RU_NAME=
```

Publish the migrations and migrate
```shell
php artisan vendor:publish --tag=ebay-migrations
php artisan migrate
```

## Model configuration
Use the `EbayProduct` trait in your product model. Define the sku column name in the function (if the column is named *sku* you can omit the function).

```php
<?php

use \Illuminate\Database\Eloquent\Model;
use \Andyts93\LaravelEbay\Traits\EbayProduct;

class Product extends Model
{
    use EbayProduct;
    
    protected function getSkuKeyName(): string
    {
        return 'sku';
    }
    
    ...
}
```

## Usage
### 1. Create inventory item
```php
$product = Product::first();
$product->ebayCreateInventoryItem([
    'quantity' => $product->quantity,
    'title' => Str::limit($product->name, 80, ''),
    'description' => $product->description,
    'aspects' => [
        'Brand' => [$product->brand],
    ],
], [
    'packageWeightAndSize' => [
        'weight' => [
            'unit' => 'KILOGRAM',
            'value' => $product->weight,
        ]
    ],
]);
```

### 2. Create offer
```php
$offer = $product->ebayCreateOffer([
    'category_id' => EBAY_CATEGORY_ID,
    'price' => $product->price,
]);
```

### 3. Publish the offer
```php
$offer->publishOffer();
```

## Other functions
### Get product's offers
```php
$product->ebayListings()->get();
```

### Update an offer
```php
$offer->update([
    'category_id' => EBAY_CATEGORY_ID,
    'price' => $product->price + 10.00,
]);
```
