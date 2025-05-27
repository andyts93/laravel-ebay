<?php


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up()
    {
        Schema::create('ebay_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        $this->insertDefaultSettings();
    }

    public function down()
    {
        Schema::dropIfExists('ebay_settings');
    }

    private function insertDefaultSettings()
    {
        $defaultSettings = [
            [
                'key' => 'access_token',
                'value' => null,
                'type' => 'string',
                'description' => 'Access token of the logged user',
                'is_public' => false,
            ],
            [
                'key' => 'refresh_token',
                'value' => null,
                'type' => 'string',
                'description' => 'Refresh token of the logged user',
                'is_public' => false,
            ],
            [
                'key' => 'default_location_id',
                'value' => 'default',
                'type' => 'string',
                'description' => 'Default store/warehouse',
                'is_public' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            \Andyts93\LaravelEbay\Models\EbaySetting::set($setting['key'], $setting['value'], $setting['type']);
        }
    }
};
