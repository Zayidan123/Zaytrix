<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    protected $table = 'knowledge_documents';
    protected $fillable = ['user_id', 'title', 'file_path', 'mime_type', 'chunk_count', 'indexed_at'];
    protected $casts = ['indexed_at' => 'datetime', 'bm25_tokens' => 'array'];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }
}
