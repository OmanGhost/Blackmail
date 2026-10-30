<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    protected $guarded = [];

    protected $casts = ['open_graph' => 'array', 'sources' => 'array'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
