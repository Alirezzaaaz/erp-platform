<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->integer('version')->default(1);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->jsonb('layout_data');
            $table->decimal('total_width', 10, 2)->default(0);
            $table->decimal('total_height', 10, 2)->default(0);
            $table->string('unit_of_measure')->default('meter');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('published_by')->nullable()->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'warehouse_id', 'status']);
            $table->unique(['warehouse_id', 'version']);
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_layout_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('type');
            $table->string('name');
            $table->decimal('pos_x', 10, 2)->default(0);
            $table->decimal('pos_y', 10, 2)->default(0);
            $table->decimal('pos_z', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(0);
            $table->decimal('depth', 10, 2)->default(0);
            $table->decimal('height', 10, 2)->default(0);
            $table->integer('capacity')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'warehouse_layout_id']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouse_layouts');
    }
};
