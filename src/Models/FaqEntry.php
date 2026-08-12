<?php

namespace Hybridcore\Faq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $category
 * @property string $question
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
    protected $fillable = ['category', 'question', 'answer', 'is_published', 'sort'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
