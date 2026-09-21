<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');
            $table->string('primary_color')->default('#1976D2');
            $table->string('secondary_color')->default('#424242');
            $table->string('success_color')->default('#2E7D32');
            $table->string('warning_color')->default('#ED6C02');
            $table->string('error_color')->default('#D32F2F');
            $table->string('background_color')->default('#F5F5F5');
            $table->string('font_family')->default('Vazirmatn');
            $table->integer('border_radius')->default(8);
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->enum('theme_mode', ['light', 'dark', 'auto'])->default('light');
            $table->jsonb('layout_config')->nullable();
            $table->jsonb('custom_tokens')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_themes');
    }
};
