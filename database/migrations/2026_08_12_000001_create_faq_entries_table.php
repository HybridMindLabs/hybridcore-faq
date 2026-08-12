<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->string('question');
            $table->text('answer'); // markdown, rendered server-side via App\Support\MarkdownRenderer
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_entries');
    }
};
