<?php

declare(strict_types=1);

namespace App\Http\Traits;

use App\Models\User;
use RuntimeException;

trait AuthorizesRequests
{
    /**
     * Authorize a given action against a policy.
     *
     * @param string $ability The method name in the policy
     * @param mixed $model The model to authorize against
     * @return bool
     * @throws RuntimeException
     */
    protected function authorize(string $ability, mixed $model): bool
    {
        $user = $this->request->getAttribute('user');
        
        if (!$user instanceof User) {
            return false;
        }

        // Get the policy class name from the model
        $policyClass = $this->getPolicyForModel($model);
        
        if (!class_exists($policyClass)) {
            throw new RuntimeException("Policy not found for model " . get_class($model));
        }

        $policy = new $policyClass();

        // Check the before method first
        $before = $policy->before($user);
        if ($before !== null) {
            return $before;
        }

        if (!method_exists($policy, $ability)) {
            throw new RuntimeException("Policy method {$ability} not found in {$policyClass}");
        }

        return $policy->$ability($user, $model);
    }

    /**
     * Get the policy class for the given model.
     *
     * @param mixed $model
     * @return string
     */
    protected function getPolicyForModel(mixed $model): string
    {
        $modelClass = get_class($model);
        $modelName = class_basename($modelClass);
        
        return "App\\Policies\\{$modelName}Policy";
    }
}
