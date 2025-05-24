<?php

namespace App\Models\RestApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'restapi_feedbacks';

    protected $fillable = [
        'submission_id',
        'run_output',
        'test_result',
        'created_at',
        'updated_at',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class, 'submission_id', 'id');
    }
}
