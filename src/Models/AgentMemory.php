<?php

declare(strict_types=1);

namespace Droath\NextusAi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;

class AgentMemory extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'llm_agent_memory';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'key',
        'value',
        'expires_at',
    ];

    /**
     * Check if this memory entry is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope to get only non-expired entries.
     */
    #[Scope]
    protected function notExpired($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    /**
     * Scope to get only expired entries.
     */
    #[Scope]
    protected function expired($query): void
    {
        $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'expires_at' => 'datetime',
        ];
    }
}
