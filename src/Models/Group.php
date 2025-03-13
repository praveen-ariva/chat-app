<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'created_by'];
    
    // A group has many members (users)
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withTimestamp('joined_at');
    }
    
    // A group has many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    // A group belongs to a creator (user)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}