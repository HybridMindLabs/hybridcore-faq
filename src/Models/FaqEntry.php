<?php

namespace Hybridcore\Faq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $category
 * @property string $question
 * @property string $slug
 * @property string $answer
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

    protected static function booted(): void
    {
        static::creating(function (FaqEntry $entry) {
            if (blank($entry->slug)) {
                $entry->slug = static::uniqueSlug($entry->question);
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
