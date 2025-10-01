<?php

namespace Andyts93\LaravelEbay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getOAuthUrl()
 * @method static string askAccessToken(string|null $refreshToken, string|null $code = null)
 * @method static array getInventoryLocations()
 * @method static array upsertInventoryItem(string $sku, array $itemData, array $customPayload = [])
 * @method static array upsertOffer(array $offerData, string $offerId = null)
 * @method static array publishOffer(string $offerId)
 * @method static array createFulfillmentPolicy(array $policyData)
 * @method static array createPaymentPolicy(array $policyData)
 * @method static array createReturnPolicy(array $policyData)
 * @method static array getRootCategory()
 * @method static array getCategories(string $categoryId = null)
 * @method static array getCategoryAspects(string $categoryId)
 * @method static array getOrders(array $orderIds = [], array $filter = [], integer $limit = 50, integer $offset = 0, array $fieldGroups = [])
 */
class EbayApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ebay-api';
    }
}
