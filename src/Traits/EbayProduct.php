<?php

namespace Andyts93\LaravelEbay\Traits;

use Andyts93\LaravelEbay\Facades\EbayApi;
use Andyts93\LaravelEbay\Models\EbayListing;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait EbayProduct
{
    protected function getSkuKeyName(): string
    {
        return 'sku';
    }

    public function ebayListings(): MorphMany
    {
        return $this->morphMany(EbayListing::class, 'ebayable');
    }

    public function ebayCreateInventoryItem($itemData, $customPayload = [])
    {
        EbayApi::upsertInventoryItem(
            $this->getAttribute($this->getSkuKeyName()),
            $itemData,
            $customPayload
        );
    }

    public function ebayCreateOffer($offerData)
    {
        $result = EbayApi::upsertOffer(
            [
                'sku' => $this->getAttribute($this->getSkuKeyName()),
                ...$offerData,
            ]
        );

        $offerId = null;

        if ($result['success']) {
            $offerId = $result['data']->offerId;
        }
        elseif ($result['error'][0]?->errorId === 25002) {
            $offerId = collect($result['error'][0]->parameters ?? [])->firstWhere('name', 'offerId')?->value;
        }

        if ($offerId) {
            return $this->ebayListings()->updateOrCreate(['sku' => $this->getAttribute('sku'), 'ebay_listing_type' => 'FIXED_PRICE'], [
                'ebay_offer_id' => $offerId,
                'ebay_price' => $offerData['price'],
                'ebay_category_id' => $offerData['category_id']
            ]);
        }
    }
}
