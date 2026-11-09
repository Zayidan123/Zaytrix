<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $table = 'knowledge_chunks';
    protected $fillable = ['document_id', 'chunk_index', 'content', 'bm25_tokens', 'token_count'];
    protected $casts = ['bm25_tokens' => 'array'];
}
