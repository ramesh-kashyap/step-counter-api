<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStep extends Model
{
    // Table name (optional if the table name matches the plural form of the model name)

    protected $fillable = ['user_id', 'step', 'today'];

    // If you're not using the default timestamps (created_at, updated_at), you can disable it
   // Set to false if your table does not have timestamps.

    // Define the fillable attributes to allow mass assignment
   
}
