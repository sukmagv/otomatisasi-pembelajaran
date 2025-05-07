<?php

namespace App\Models\RestApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'restapi_topics';

    protected $fillable = [
        'title',
        'description',
        'created_at',
        'updated_at',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'topic_id');
    }

    public function firstTask()
    {
        return $this->hasOne(Task::class, 'topic_id')->where('order_number', 1);
    }
}
