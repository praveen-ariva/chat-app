<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['group_id', 'user_id', 'content'];
    
    // A message belongs to a group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    
    // A message belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}