<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track all data changes for sync
        Schema::create('sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('entity_type', 100);       // product, invoice, party, ...
            $table->unsignedBigInteger('entity_id');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->jsonb('payload')->nullable();      // داده کامل entity
            $table->jsonb('changed_fields')->nullable(); // فقط فیلدهای تغییر یافته
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('source_platform', 20)->nullable(); // windows, android, web
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
        });

        // Track what each client has synced
        Schema::create('sync_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('client_id', 100)->unique();    // UUID از سمت کلاینت
            $table->string('device_name')->nullable();
            $table->string('platform', 20);                 // windows, android
            $table->unsignedBigInteger('last_sync_change_id')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        // Conflict log
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('sync_clients')->onDelete('cascade');
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->jsonb('client_data');
            $table->jsonb('server_data');
            $table->enum('resolution', ['client_wins', 'server_wins', 'manual', 'pending'])->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
        Schema::dropIfExists('sync_clients');
        Schema::dropIfExists('sync_changes');
    }
};
