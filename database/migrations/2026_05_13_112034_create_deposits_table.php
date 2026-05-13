<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['goal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
