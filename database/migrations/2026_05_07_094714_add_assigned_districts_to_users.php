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
        Schema::table('users', function (Blueprint $table) {
            // JSON list of district names this user is responsible for.
            // Only meaningful when role = 'area_staff'. Max 4 districts (enforced in app).
            $table->json('assigned_districts')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('assigned_districts');
        });
    }
};
