<?php

namespace Andyts93\LaravelEbay\Console\Commands;

use Andyts93\LaravelEbay\Services\EbayRestApiService;
use Illuminate\Console\Command;

class EbaySetupCommand extends Command
{
    protected $signature = 'ebay:setup
                            {--policies : Create basic policies automatically}
                            {--test : Test API connection}';

    protected $description = 'Initial setup for Ebay';

    public function handle()
    {
        $this->info('🚀 Setup eBay Laravel Package');
        $this->newLine();

        if (!$this->checkConfig()) {
            return Command::FAILURE;
        }

        if ($this->option('test')) {
            $this->testConnection();
        }

        if ($this->option('policies')) {
            $this->createPolicies();
        }

        $this->newLine();
        $this->info('✅ Setup completed!');

        return Command::SUCCESS;
    }

    private function checkConfig(): bool
    {
        $this->info('📋 Verifying configuration...');

        $required = ['client_id', 'client_secret', 'ru_name'];
        $missing = [];

        foreach ($required as $key) {
            if (empty(config('ebay.' . $key))) {
                $missing[] = "EBAY_" . strtoupper($key);
            }
        }

        if (!empty($missing)) {
            $this->error('❌ Missing config!');
            $this->warn('Add to your .env:');
            foreach ($missing as $var) {
                $this->line("- {$var}=your_value");
            }
            return false;
        }

        $this->info('✅ Configuration OK');
        return true;
    }

    private function testConnection(): void
    {
        $this->info('🔗 Test API connection...');

        try {
            $service = app(EbayRestApiService::class);
            $result = $service->getCategories();

            if ($result['success']) {
                $this->info('✅ API connection OK');
            }
            else {
                $this->error('❌ API connection error');
                $this->warn(json_encode($result['error'], JSON_PRETTY_PRINT));
            }
        } catch (\Exception $exception) {
            $this->error('❌ Error: ' . $exception->getMessage());
        }
    }

    private function createPolicies(): void
    {
        $this->info('🏗️  Creating policies...');

        $service = app(EbayRestApiService::class);

        // Policy spedizione
        $this->createShippingPolicy($service);

        // Policy pagamento
        $this->createPaymentPolicy($service);

        // Policy reso
        $this->createReturnPolicy($service);
    }

    private function createShippingPolicy($service): void
    {
        if (cache('ebay_fulfillment_policy_id')) {
            $this->warn('⚠️  Shipping policy already existing');
            return;
        }

        $policy = [
            'name' => 'Standard Shipping IT',
            'description' => 'Policy standard per spedizioni in Italia',
            'handling_time' => 1,
            'shipping_options' => [
                [
                    'type' => 'DOMESTIC',
                    'service_name' => 'Standard',
                    'cost' => config('ebay.default_shipping.standard_cost', 5.00),
                    'sort_order' => 1
                ]
            ]
        ];

        $result = $service->createFulfillmentPolicy($policy);

        if ($result['success']) {
            cache(['ebay_fulfillment_policy_id' => $result['data']['fulfillmentPolicyId']],
                config('ebay.cache.policies_ttl', 2592000));
            $this->info('✅ Shipping policy created');
        } else {
            $this->error('❌ Shipping policy error');
            $this->warn(json_encode($result['error'], JSON_PRETTY_PRINT));
        }
    }

    private function createPaymentPolicy($service): void
    {
        if (cache('ebay_payment_policy_id')) {
            $this->warn('⚠️  Payment policy existing');
            return;
        }

        $policy = [
            'name' => 'PayPal Payment',
            'description' => 'Pagamento tramite PayPal',
            'paypal_email' => config('ebay.paypal_email')
        ];

        $result = $service->createPaymentPolicy($policy);

        if ($result['success']) {
            cache(['ebay_payment_policy_id' => $result['data']['paymentPolicyId']],
                config('ebay.cache.policies_ttl', 2592000));
            $this->info('✅ Payment policy created');
        } else {
            $this->error('❌ Payment policy error');
            $this->warn(json_encode($result['error'], JSON_PRETTY_PRINT));
        }
    }

    private function createReturnPolicy($service): void
    {
        if (cache('ebay_return_policy_id')) {
            $this->warn('⚠️  Return policy existing');
            return;
        }

        $policy = [
            'name' => '30 Days Return',
            'description' => 'Reso entro 30 giorni',
            'returns_accepted' => config('ebay.default_return_policy.returns_accepted', true),
            'return_days' => config('ebay.default_return_policy.return_days', 30)
        ];

        $result = $service->createReturnPolicy($policy);

        if ($result['success']) {
            cache(['ebay_return_policy_id' => $result['data']['returnPolicyId']],
                config('ebay.cache.policies_ttl', 2592000));
            $this->info('✅ Return policy created');
        } else {
            $this->error('❌ Return policy error');
            $this->warn(json_encode($result['error'], JSON_PRETTY_PRINT));
        }
    }
}
