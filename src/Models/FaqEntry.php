<?php

namespace Hybridcore\Faq\Models;

use App\Support\MarkdownRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $category
 * @property string $question
 * @property string $slug
 * @property string $answer
 * @property string $answer_html
 * @property bool $is_published
 * @property int $sort
 * @property int $helpful_count
 * @property int $not_helpful_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 *
 * @mixin \Eloquent
 */
class FaqEntry extends Model
{
    protected $fillable = ['category', 'question', 'slug', 'answer', 'is_published', 'sort'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function feedbackNotes(): HasMany
    {
        return $this->hasMany(FaqFeedbackNote::class)->latest();
    }

    protected static function booted(): void
    {
        static::creating(function (FaqEntry $entry) {
            if (blank($entry->slug)) {
                $entry->slug = static::uniqueSlug($entry->question);
            }
        });

        // Rendered once here instead of on every public-page request — the
        // answer only changes on save, not on every visitor.
        static::saving(function (FaqEntry $entry) {
            if ($entry->isDirty('answer') || blank($entry->answer_html)) {
                $entry->answer_html = app(MarkdownRenderer::class)->toHtml($entry->answer);
            }
        });
    }

    /** Slugs are stable once set — a later question edit never silently breaks a shared link. */
    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'question';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
