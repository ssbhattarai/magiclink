<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'expires_at'], 'idx_email_expires');
            $table->index('token', 'idx_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_links');
    }
};
