<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Message
 *
 * @property int $id
 * @property string|null $message
 * @property bool $sent
 * @property string|null $attachment
 * @property bool $has_attachment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */

class Message extends Model
{
    protected $guarded = [];
}
