<?php

namespace StreetMesh\Protocol\Laravel\Records;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * One link in a resident's history, as stored.
 *
 * @property int $id
 * @property string $did
 * @property string $cid
 * @property string|null $prev
 * @property string $data
 * @property string $rev
 * @property array<string, mixed> $body
 * @property Carbon $created_at
 */
class CommitRecord extends Model
{
    protected $table = 'streetmesh_commits';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'body' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // A history that can be edited is not a history. Correcting one means
        // committing again, which leaves both the mistake and the correction.
        static::updating(function (CommitRecord $commit): void {
            throw new RuntimeException(
                "Commit [{$commit->cid}] cannot be altered. A history that can be edited proves nothing."
            );
        });
    }
}
