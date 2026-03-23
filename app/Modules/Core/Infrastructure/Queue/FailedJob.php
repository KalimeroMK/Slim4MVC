<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Queue;

use App\Modules\Core\Infrastructure\Jobs\Job;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property string $job_class
 * @property string $job_data
 * @property string $exception
 * @property string $exception_message
 * @property string $exception_trace
 * @property int $failed_at
 * @property int $attempts
 *
 * @method static static|null find(int $id)
 * @method static int count()
 * @method static void truncate()
 * @method static \Illuminate\Database\Eloquent\Builder<self> orderBy(string $column, string $direction = 'asc')
 * @method static static create(array<string, mixed> $attributes)
 */
class FailedJob extends Model
{
    public $timestamps = false;

    protected $table = 'failed_jobs';

    protected $fillable = [
        'job_class',
        'job_data',
        'exception',
        'exception_message',
        'exception_trace',
        'failed_at',
        'attempts',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'failed_at' => 'integer',
        'attempts' => 'integer',
    ];

    /**
     * Store a failed job.
     */
    public static function store(Job $job, Exception $exception, int $attempts = 1): self
    {
        return self::create([
            'job_class' => $job::class,
            'job_data' => serialize($job),
            'exception' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception_trace' => $exception->getTraceAsString(),
            'failed_at' => time(),
            'attempts' => $attempts,
        ]);
    }

    /**
     * Get the job instance.
     */
    public function getJob(): ?Job
    {
        try {
            return unserialize($this->job_data);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Retry the failed job.
     */
    public function retry(Queue $queue): bool
    {
        $job = $this->getJob();

        if (! $job instanceof Job) {
            return false;
        }

        $queue->push($job);
        $this->delete();

        return true;
    }

    /**
     * Delete the failed job.
     */
    #[Override]
    public function delete(): ?bool
    {
        return parent::delete();
    }
}
