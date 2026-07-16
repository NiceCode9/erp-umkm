<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('business_id', 'idx_products_business_id');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->index('business_id', 'idx_raw_materials_business_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('business_id', 'idx_suppliers_business_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('business_id', 'idx_customers_business_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('business_id', 'idx_purchases_business_id');
            $table->index('payment_status', 'idx_purchases_payment_status');
            $table->index('created_at', 'idx_purchases_created_at');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('purchase_id', 'idx_purchase_items_purchase_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('business_id', 'idx_sales_business_id');
            $table->index('payment_status', 'idx_sales_payment_status');
            $table->index('sale_date', 'idx_sales_sale_date');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('sale_id', 'idx_sale_items_sale_id');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->index('business_id', 'idx_production_orders_business_id');
            $table->index('status', 'idx_production_orders_status');
            $table->index('created_at', 'idx_production_orders_created_at');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index('business_id', 'idx_shipments_business_id');
            $table->index('status', 'idx_shipments_status');
        });

        Schema::table('stock_distributions', function (Blueprint $table) {
            $table->index('business_id', 'idx_stock_distributions_business_id');
            $table->index('status', 'idx_stock_distributions_status');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('business_id', 'idx_stock_movements_business_id');
            $table->index('item_type', 'idx_stock_movements_item_type');
            $table->index('item_id', 'idx_stock_movements_item_id');
            $table->index('reference_type', 'idx_stock_movements_reference_type');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->index('business_id', 'idx_stock_opnames_business_id');
            $table->index('session_id', 'idx_stock_opnames_session_id');
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->index('business_id', 'idx_cashier_shifts_business_id');
            $table->index('user_id', 'idx_cashier_shifts_user_id');
        });

        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->index('raw_material_id', 'idx_raw_material_batches_raw_material_id');
            $table->index('branch_id', 'idx_raw_material_batches_branch_id');
            $table->index('expired_date', 'idx_raw_material_batches_expired_date');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->index('product_id', 'idx_product_batches_product_id');
            $table->index('branch_id', 'idx_product_batches_branch_id');
            $table->index('expired_date', 'idx_product_batches_expired_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_id');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropIndex('idx_raw_materials_business_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_business_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_business_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('idx_purchases_business_id');
            $table->dropIndex('idx_purchases_payment_status');
            $table->dropIndex('idx_purchases_created_at');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_items_purchase_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_business_id');
            $table->dropIndex('idx_sales_payment_status');
            $table->dropIndex('idx_sales_sale_date');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_sale_id');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropIndex('idx_production_orders_business_id');
            $table->dropIndex('idx_production_orders_status');
            $table->dropIndex('idx_production_orders_created_at');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('idx_shipments_business_id');
            $table->dropIndex('idx_shipments_status');
        });

        Schema::table('stock_distributions', function (Blueprint $table) {
            $table->dropIndex('idx_stock_distributions_business_id');
            $table->dropIndex('idx_stock_distributions_status');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_business_id');
            $table->dropIndex('idx_stock_movements_item_type');
            $table->dropIndex('idx_stock_movements_item_id');
            $table->dropIndex('idx_stock_movements_reference_type');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropIndex('idx_stock_opnames_business_id');
            $table->dropIndex('idx_stock_opnames_session_id');
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropIndex('idx_cashier_shifts_business_id');
            $table->dropIndex('idx_cashier_shifts_user_id');
        });

        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->dropIndex('idx_raw_material_batches_raw_material_id');
            $table->dropIndex('idx_raw_material_batches_branch_id');
            $table->dropIndex('idx_raw_material_batches_expired_date');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('idx_product_batches_product_id');
            $table->dropIndex('idx_product_batches_branch_id');
            $table->dropIndex('idx_product_batches_expired_date');
        });
    }
};
