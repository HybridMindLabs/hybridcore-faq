<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            // Defaults true so existing entries (and the simple create flow)
            // stay live — this is an opt-out draft state, not opt-in.
            $table->boolean('is_published')->default(true)->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
