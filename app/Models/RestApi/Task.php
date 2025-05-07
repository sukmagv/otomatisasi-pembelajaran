<?php

namespace App\Models\RestApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    
    protected $table = 'restapi_topic_tasks';

    protected $fillable = [
        'topic_id',
        'title',
        'order_number',
        'file_path',
        'flag',
        'created_at',
        'updated_at',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'task_id', 'id');
    }
}
