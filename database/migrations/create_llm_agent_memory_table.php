<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_agent_memory', static function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->jsonb('value');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            // Optimize for key lookups and expiration cleanup
            $table->index(['key', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_agent_memory');
    }
};
