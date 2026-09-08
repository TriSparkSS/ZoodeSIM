<?php

namespace App\Support;

class ApiLogContext
{
    public ?string $userId = null;

    public ?string $referenceType = null;

    public ?string $referenceId = null;

    public function forUser(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function forReference(?string $type, ?string $id): self
    {
        $this->referenceType = $type;
        $this->referenceId = $id;

        return $this;
    }

    public function reset(): void
    {
        $this->userId = null;
        $this->referenceType = null;
        $this->referenceId = null;
    }
}
