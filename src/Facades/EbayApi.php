<?php

namespace Andyts93\LaravelEbay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getOAuthUrl()
 * @method static string askAccessToken(string|null $refreshToken, string|null $code = null)
 * @method static array getUser()
 * @method static array getInventoryLocations()
 * @method static array createInventoryLocation(array $data)
 * @method static array upsertInventoryItem(string $sku, array $itemData, array $customPayload = [])
 * @method static array upsertOffer(array $offerData, string $offerId = null, array $customPayload = [])
 * @method static array publishOffer(string $offerId)
 * @method static array deleteOffer(string $offerId)
 * @method static array createFulfillmentPolicy(array $policyData)
 * @method static array createPaymentPolicy(array $policyData)
 * @method static array createReturnPolicy(array $policyData)
 * @method static array getRootCategory()
 * @method static array getCategories(string $categoryId = null)
 * @method static array getCategoryAspects(string $categoryId)
 * @method static array getReturnPolicies()
 * @method static array getPaymentPolicies()
 * @method static array getFulfillmentPolicies()
 * @method static array getItemConditionPolicies(array $categories)
 * @method static array getInventoryItem(string $sku)
 * @method static array getOrders(array $orderIds = [], array $filter = [], integer $limit = 50, integer $offset = 0, array $fieldGroups = [])
 * @method static array createShippingFulfillment(string $orderId, array $lineItems, string $shippedDate = null, ?string $shippingCarrierCode = null, ?string $trackingNumber = null)
 */
class EbayApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ebay-api';
    }
}
