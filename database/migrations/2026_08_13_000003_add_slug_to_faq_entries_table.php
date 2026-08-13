<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('question');
        });

        $used = [];
        foreach (DB::table('faq_entries')->orderBy('id')->get(['id', 'question']) as $row) {
            $base = Str::slug($row->question) ?: 'question';
            $slug = $base;
            $i = 2;
            while (in_array($slug, $used, true)) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $used[] = $slug;
            DB::table('faq_entries')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('faq_entries', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('faq_entries', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
