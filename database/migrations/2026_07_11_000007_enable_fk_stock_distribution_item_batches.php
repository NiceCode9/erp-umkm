<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_distribution_item_batches', function (Blueprint $table) {
            $table->foreign('stock_distribution_item_id', 'sdib_sdi_fk')
                ->references('id')->on('stock_distribution_items')->cascadeOnDelete();
            $table->foreign('product_batch_id', 'sdib_pb_fk')
                ->references('id')->on('product_batches')->cascadeOnDelete();
            $table->foreign('raw_material_batch_id', 'sdib_rmb_fk')
                ->references('id')->on('raw_material_batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_distribution_item_batches', function (Blueprint $table) {
            $table->dropForeign('sdib_sdi_fk');
            $table->dropForeign('sdib_pb_fk');
            $table->dropForeign('sdib_rmb_fk');
        });
    }
};
