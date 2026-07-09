<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('halal_cert_number')->nullable()->after('minimum_stock');
            $table->string('halal_cert_issuer')->nullable()->after('halal_cert_number');
            $table->date('halal_cert_expired_date')->nullable()->after('halal_cert_issuer');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['halal_cert_number', 'halal_cert_issuer', 'halal_cert_expired_date']);
        });
    }
};
