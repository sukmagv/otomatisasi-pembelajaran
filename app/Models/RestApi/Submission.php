<?php

namespace App\Models\RestApi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Submission extends Model
{
    use HasFactory;

    protected $table = 'restapi_student_submits';

    protected $fillable = [
        'user_id',
        'topic_id',
        'submit_file_path',
        'submit_comment',
        'created_by',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
