<?php

use App\Support\MarkdownRenderer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->text('answer_html')->nullable()->after('answer');
        });

        $markdown = app(MarkdownRenderer::class);

        foreach (DB::table('faq_entries')->orderBy('id')->get(['id', 'answer']) as $row) {
            DB::table('faq_entries')->where('id', $row->id)->update([
                'answer_html' => $markdown->toHtml($row->answer),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('faq_entries', function (Blueprint $table) {
            $table->dropColumn('answer_html');
        });
    }
};
