<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users');
            $table->text('notes');
            $table->text('parts_used')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_reports');
    }
};
