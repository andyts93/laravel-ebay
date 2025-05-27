<?php


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up()
    {
        Schema::create('ebay_listings', function (Blueprint $table) {
            $table->id();
            $table->morphs('ebayable');
            $table->string('sku');
            $table->string('ebay_offer_id')->nullable();
            $table->string('ebay_listing_id')->nullable();
            $table->string('ebay_listing_type')->nullable();
            $table->string('ebay_category_id')->nullable();
            $table->string('ebay_status')->default('DRAFT');
            $table->decimal('ebay_price', 10)->nullable();
            $table->integer('ebay_quantity')->default(1);
            $table->timestamp('ebay_published_at')->nullable();
            $table->timestamp('ebay_ended_at')->nullable();
            $table->json('ebay_data')->nullable();
            $table->timestamps();

            $table->index(['sku', 'ebay_status', 'ebay_published_at']);
            $table->index('ebay_offer_id');
            $table->index('ebay_listing_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ebay_listings');
    }
};
