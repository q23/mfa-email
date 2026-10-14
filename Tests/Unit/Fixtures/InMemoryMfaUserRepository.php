<?php

declare(strict_types=1);

namespace Q23\MfaEmail\Tests\Unit\Fixtures;

use Q23\MfaEmail\Domain\Repository\MfaUserRepository;

final class InMemoryMfaUserRepository extends MfaUserRepository
{
    /** @var array<int, array<string, int|string>> */
    public array $rows = [];

    public function __construct()
    {
    }

    public function findMfaData(int $feUserUid): ?array
    {
        return $this->rows[$feUserUid] ?? null;
    }

    public function update(int $feUserUid, array $fields): void
    {
        $this->rows[$feUserUid] = array_merge($this->rows[$feUserUid] ?? [], $fields);
    }
}
