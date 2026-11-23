<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            // Статус оплаты записи: unpaid, paid, cancelled
            $table->string('payment_status')->default('unpaid')->after('status_id');
            $table->decimal('amount', 10, 2)->default(0)->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'amount', 'paid_at']);
        });
    }
};
