<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = ['source', 'title', 'url', 'description', 'published_at', 'high_impact', 'impact_keywords'];
    protected $casts = ['published_at' => 'datetime', 'high_impact' => 'boolean', 'impact_keywords' => 'array'];
}
