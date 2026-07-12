<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('destination');
        });

        Schema::table('shipments', function (Blueprint $table) {
            DB::statement('UPDATE shipments SET recipient_name = COALESCE((SELECT customer_name FROM sales WHERE sales.id = shipments.sale_id), \'\')');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('recipient_name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('recipient_name');
        });
    }
};
