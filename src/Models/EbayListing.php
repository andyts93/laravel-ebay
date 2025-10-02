<?php

namespace Andyts93\LaravelEbay\Models;

use Andyts93\LaravelEbay\Facades\EbayApi;
use Awobaz\Compoships\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EbayListing extends Model
{
    protected $fillable = [
        'sku',
        'ebay_offer_id',
        'ebay_listing_id',
        'ebay_listing_type',
        'ebay_category_id',
        'ebay_status',
        'ebay_price',
        'ebay_quantity',
        'ebay_published_at',
        'ebay_ended_at',
        'ebay_data',
    ];

    public function ebayable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEbayListingUrlAttribute()
    {
        return (config('ebay.sandbox') ? 'https://sandbox.ebay.com/' : 'https://ebay.com/') . "itm/{$this->ebay_listing_id}";
    }

    public function updateOffer($offerData, $customPayload = [])
    {
        $result = EbayApi::upsertOffer([
            'sku' => $this->sku,
            ...$offerData,
        ], $this->ebay_offer_id, $customPayload);
        dd($result);

        if ($result['success']) {
            $this->update([
                'ebay_price' => $offerData['price'],
                'ebay_category_id' => $offerData['category_id']
            ]);
        }
    }

    public function publishOffer()
    {
        $result = EbayApi::publishOffer($this->ebay_offer_id);

        if ($result['success']) {
            $this->update([
                'ebay_listing_id' => $result['data']->listingId,
                'ebay_status' => 'PUBLISHED',
                ...(!$this->ebay_published_at ? ['ebay_published_at' => now()] : []),
            ]);
        }
    }
}
