<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->getPrimaryRoleName(),
            'roles' => $this->getRoleNames()->toArray(),
            'is_active' => $this->is_active,
            'avatar_url' => $this->avatar_url,
            'subscription_tier_id' => $this->subscription_tier_id,
            'managed_by' => $this->managed_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'subscription_tier' => $this->whenLoaded('subscriptionTier', function () {
                if (!$this->subscriptionTier) {
                    return null;
                }
                return [
                    'id' => $this->subscriptionTier->id,
                    'name' => $this->subscriptionTier->name,
                ];
            }),
        ];
    }
}
