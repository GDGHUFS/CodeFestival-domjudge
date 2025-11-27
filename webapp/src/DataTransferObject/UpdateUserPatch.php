<?php declare(strict_types=1);

namespace App\DataTransferObject;

use OpenApi\Attributes as OA;

class UpdateUserPatch
{
    public function __construct(
        #[OA\Property(nullable: true)]
        public readonly ?string $username,
        #[OA\Property(nullable: true)]
        public readonly ?string $name,
        #[OA\Property(format: 'email', nullable: true)]
        public readonly ?string $email,
        #[OA\Property(nullable: true)]
        public readonly ?string $ip,
        #[OA\Property(format: 'password', nullable: true)]
        public readonly ?string $password,
        #[OA\Property(nullable: true)]
        public readonly ?bool $enabled,
        #[OA\Property(nullable: true)]
        public readonly ?string $teamId,
        #[Serializer\Type('array<string>')]
        public readonly ?array $roles,
    ) {}
}
