<?php

namespace Andyts93\LaravelEbay\Services;

use Andyts93\LaravelEbay\Facades\EbaySettings;
use Andyts93\LaravelEbay\Models\EbayListing;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class EbayRestApiService
{
    private Client $client;
    private string $baseUrl;
    private string $marketplaceId;

    public function __construct()
    {
        $this->client = new Client();
        $this->marketplaceId = config('ebay.marketplace_id');

        $this->baseUrl = config('ebay.sandbox')
            ? 'https://api.sandbox.ebay.com'
            : 'https://api.ebay.com';
    }

    public function getInventoryLocations()
    {
        return $this->makeRequest('GET', $this->baseUrl . '/sell/inventory/v1/location');
    }

    /**
     * Create an inventory item
     */
    public function upsertInventoryItem(string $sku, array $itemData, array $customPayload = [])
    {
        $url = $this->baseUrl . '/sell/inventory/v1/inventory_item/' . $sku;

        $payload = [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => $itemData['quantity']
                ]
            ],
            'condition' => $itemData['condition'] ?? 'NEW',
            'product' => [
                'title' => $itemData['title'],
                'description' => $itemData['description'],
                ...(isset($itemData['images']) ? ['imageUrls' => $itemData['images']] : []),
                'aspects' => $itemData['aspects'] ?? [],
            ],
            ...$customPayload,
        ];

        return $this->makeRequest('PUT', $url, $payload);
    }

    /**
     * Create an offer (listing)
     */
    public function upsertOffer($offerData, $offerId = null)
    {
        $url = $this->baseUrl . '/sell/inventory/v1/offer';
        $method = "POST";

        $payload = [
            'sku' => $offerData['sku'],
            'marketplaceId' => $this->marketplaceId,
            'format' => 'FIXED_PRICE',
            'categoryId' => $offerData['category_id'],
            'listingDuration' => 'GTC',
            'merchantLocationKey' => EbaySettings::get('default_location_id'),
            'pricingSummary' => [
                'price' => [
                    'currency' => 'EUR',
                    'value' => $offerData['price']
                ],
            ],
            'tax' => [
                'applyTax' => true,
                'thirdPartyTaxCategory' => 'STANDARD',
            ]
        ];

        if ($offerId) {
            $url .= "/{$offerId}";
            $method = "PUT";
        }

        return $this->makeRequest($method, $url, $payload);
    }

    /**
     * Publish an offer
     */
    public function publishOffer($offerId)
    {
        $url = $this->baseUrl . '/sell/inventory/v1/offer/' . $offerId . '/publish';

        return $this->makeRequest('POST', $url);
    }

    /**
     * Create fulfillment policy (shipping)
     */
    public function createFulfillmentPolicy($policyData)
    {
        $url = $this->baseUrl . '/sell/account/v1/fulfillment_policy';

        $payload = [
            'name' => $policyData['name'],
            'description' => $policyData['description'],
            'marketplaceId' => $this->marketplaceId,
            'categoryTypes' => [
                [
                    'name' => 'ALL_EXCLUDING_MOTORS_VEHICLES'
                ]
            ],
            'handlingTime' => [
                'value' => $policyData['handling_time'] ?? 1,
                'unit' => 'DAY'
            ],
            'shippingOptions' => $this->buildShippingOptions($policyData['shipping_options'])
        ];

        return $this->makeRequest('POST', $url, $payload);
    }

    /**
     * Create payment policy
     */
    public function createPaymentPolicy($policyData)
    {
        $url = $this->baseUrl . '/sell/account/v1/payment_policy';

        $payload = [
            'name' => $policyData['name'],
            'description' => $policyData['description'],
            'marketplaceId' => $this->marketplaceId,
            'categoryTypes' => [
                [
                    'name' => 'ALL_EXCLUDING_MOTORS_VEHICLES'
                ]
            ],
            'paymentMethods' => [
                [
                    'paymentMethodType' => 'PAYPAL',
                    'recipientAccountReference' => [
                        'referenceId' => $policyData['paypal_email'],
                        'referenceType' => 'PAYPAL_EMAIL'
                    ]
                ]
            ]
        ];

        return $this->makeRequest('POST', $url, $payload);
    }

    /**
     * Create return policy
     */
    public function createReturnPolicy($policyData)
    {
        $url = $this->baseUrl . '/sell/account/v1/return_policy';

        $payload = [
            'name' => $policyData['name'],
            'description' => $policyData['description'],
            'marketplaceId' => $this->marketplaceId,
            'categoryTypes' => [
                [
                    'name' => 'ALL_EXCLUDING_MOTORS_VEHICLES'
                ]
            ],
            'returnsAccepted' => $policyData['returns_accepted'] ?? true,
            'returnPeriod' => [
                'value' => $policyData['return_days'] ?? 30,
                'unit' => 'DAY'
            ],
            'returnMethod' => 'REPLACEMENT',
            'returnShippingCostPayer' => 'BUYER'
        ];

        return $this->makeRequest('POST', $url, $payload);
    }

    public function getRootCategory()
    {
        return $this->makeRequest('GET', $this->baseUrl . '/commerce/taxonomy/v1/get_default_category_tree_id?marketplace_id=' . config('ebay.marketplace_id'));
    }

    /**
     * Get ebay categories
     */
    public function getCategories($categoryId = null)
    {
        $rootCategory = $this->getRootCategory()['data']->categoryTreeId;

        $url = $this->baseUrl . '/commerce/taxonomy/v1/category_tree/' . $rootCategory;

        if ($categoryId) {
            $url .= '/get_category_subtree?category_id=' . $categoryId;
        }

        return $this->makeRequest('GET', $url);
    }

    /**
     * Get mandatory aspects per category
     */
    public function getCategoryAspects($categoryId)
    {
        $url = $this->baseUrl . '/commerce/taxonomy/v1/category_tree/' . $this->marketplaceId . '/get_item_aspects_for_category';
        $url .= '?category_id=' . $categoryId;

        return $this->makeRequest('GET', $url);
    }

    public function getOrders($orderIds = [], $filter = [], $limit = 50, $offset = 0, $taxBreakdown = false)
    {
        $url = $this->baseUrl . "/sell/fulfillment/v1/order?limit={$limit}&offset={$offset}";
        if (!empty($orderIds)) {
            $url .= "&orderIds=" . implode(',', $orderIds);
        }
        if (!empty($filter)) {
            $replacements = ['[', ']', '{', '}', '|'];
            $filters = array_map(function($el) use ($replacements) {
                foreach ($replacements as $replacement) {
                    $el = preg_replace('/\\' . $replacement . '/', urlencode($replacement), $el);
                }
                return $el;
            }, $filter);
            $url .= '&filter=' . implode(',', $filters);
        }
        if ($taxBreakdown) {
            $url .= '&fieldGroups=TAX_BREAKDOWN';
        }

        return $this->makeRequest('GET', $url);
    }

    private function buildShippingOptions($shippingOptions)
    {
        $options = [];

        foreach ($shippingOptions as $option) {
            $options[] = [
                'optionType' => $option['type'] ?? 'DOMESTIC',
                'costType' => 'FLAT_RATE',
                'shippingServices' => [
                    [
                        'serviceName' => $option['service_name'],
                        'shippingCost' => [
                            'currency' => 'EUR',
                            'value' => $option['cost']
                        ],
                        'sortOrder' => $option['sort_order'] ?? 1
                    ]
                ]
            ];
        }

        return $options;
    }

    private function makeRequest($method, $url, $payload = null, $retries = 0)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Content-Language' => config('ebay.default_content_language'),
            'Accept-Language' => config('ebay.default_content_language')
        ];

        $options = [
            'headers' => $headers
        ];

        if ($payload) {
            $options['json'] = $payload;
        }

        try {
            $response = $this->client->request($method, $url, $options);

            $body = $response->getBody()->getContents();
            $data = json_decode($body);

            return [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'data' => $data
            ];

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorData = json_decode($errorBody);

            if ($e->getCode() === 401 && $retries < 3) {
                EbaySettings::set('access_token', '');
                return $this->makeRequest($method, $url, $payload, $retries + 1);
            }

            return [
                'success' => false,
                'status_code' => $e->getCode(),
                'error' => $errorData->errors ?? $e->getMessage()
            ];
        }
    }

    public function getOAuthUrl(): string
    {
        $url = config('ebay.sandbox')
            ? 'https://auth.sandbox.ebay.com'
            : 'https://auth.ebay.com';

        $params = [
            'client_id' => config('ebay.client_id'),
            'redirect_uri' => config('ebay.ru_name'),
            'response_type' => 'code',
            'scope' => implode(' ', config('ebay.scopes'))
        ];

        return $url . '/oauth2/authorize?' . http_build_query($params);
    }

    private function getAccessToken()
    {
        // Controlla se il token è in cache
        $token = EbaySettings::get('access_token');

        if (!$token) {
            $token = $this->askAccessToken(EbaySettings::get('refresh_token'));
        }

        return $token;
    }

    public function askAccessToken(string|null $refreshToken, string|null $code = null)
    {
        $url = config('ebay.sandbox')
            ? 'https://api.sandbox.ebay.com/identity/v1/oauth2/token'
            : 'https://api.ebay.com/identity/v1/oauth2/token';

        $credentials = base64_encode(config('ebay.client_id') . ':' . config('ebay.client_secret'));

        try {
            if (!empty($refreshToken)) {
                $form_params = [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'scope' => implode(' ', config('ebay.scopes'))
                ];
            }
            else {
                if (empty($code)) {
                    throw new \Exception('Code non presente');
                }
                $form_params = [
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('ebay.ru_name'),
                    'code' => $code
                ];
            }
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $credentials,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => $form_params
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            EbaySettings::set('access_token', $data['access_token']);

            if (isset($data['refresh_token'])) {
                EbaySettings::set('refresh_token', $data['refresh_token']);
            }

            return $data['access_token'];

        } catch (RequestException $e) {
            throw new \Exception('Errore nel refresh del token: ' . $e->getMessage());
        }
    }
}
