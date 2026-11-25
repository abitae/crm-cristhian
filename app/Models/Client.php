<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'company', 'email', 'phone', 'city'])]
class Client extends Model
{
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(ClientOption::class, 'client_option_assignments');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ClientHistory::class)->latest('id');
    }

    public function recordHistory(User $user, string $type, string $body, ?array $meta = null): ClientHistory
    {
        return $this->histories()->create([
            'user_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta,
        ]);
    }
}
