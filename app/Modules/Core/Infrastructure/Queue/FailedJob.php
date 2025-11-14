<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Queue;

use App\Modules\Core\Infrastructure\Jobs\Job;
use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $job_class
 * @property string $job_data
 * @property string $exception
 * @property string $exception_message
 * @property string $exception_trace
 * @property int $failed_at
 * @property int $attempts
 */
class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $fillable = [
        'job_class',
        'job_data',
        'exception',
        'exception_message',
        'exception_trace',
        'failed_at',
        'attempts',
    ];

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
            'job_class' => get_class($job),
            'job_data' => serialize($job),
            'exception' => get_class($exception),
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
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Retry the failed job.
     */
    public function retry(Queue $queue): bool
    {
        $job = $this->getJob();

        if ($job === null) {
            return false;
        }

        $queue->push($job);
        $this->delete();

        return true;
    }

    /**
     * Delete the failed job.
     */
    public function delete(): ?bool
    {
        return parent::delete();
    }
}

