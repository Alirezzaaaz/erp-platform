<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tax_uid')->nullable()->after('tax_reference_id');
            $table->string('buyer_economic_code')->nullable()->after('tax_uid');
            $table->timestamp('tax_sent_at')->nullable()->after('tax_status');
            $table->integer('tax_retry_count')->default(0)->after('tax_sent_at');
            $table->text('tax_error_message')->nullable()->after('tax_retry_count');
            $table->jsonb('tax_payload')->nullable()->after('tax_error_message');

            $table->index('tax_uid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tax_uid']);
            $table->dropColumn([
                'tax_uid', 'buyer_economic_code', 'tax_sent_at',
                'tax_retry_count', 'tax_error_message', 'tax_payload',
            ]);
        });
    }
};
