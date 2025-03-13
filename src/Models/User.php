<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['username'];
    
    // A user can be a member of many groups
    public function groups()
    {
        return $this->belongsToMany(Group::vendor/bin/phpunitclass, 'group_members')
            ->withTimestamp('joined_at');
    }
    
    // A user can have many messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    // A user can create many groups
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }
}
