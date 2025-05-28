<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_pending_deletion')->default(false);
            $table->timestamp('deletion_requested_at')->nullable();
            $table->text('deletion_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_pending_deletion',
                'deletion_requested_at',
                'deletion_reason'
            ]);
        });
    }
};

// Crear con: php artisan make:migration add_deletion_fields_to_users_table
// Ejecutar con: php artisan migrate
