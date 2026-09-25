<?php

declare(strict_types=1);

namespace App\Persistence;

use MongoDB\Client;

final readonly class DatabaseClient
{
    private const array TYPE_MAP = ['root' => 'array', 'document' => 'array', 'array' => 'array'];

    private Client $mongoClient;

    public function __construct(
        private string $databaseUri,
        private string $databaseName,
    ) {
        $this->mongoClient = new Client($this->databaseUri);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $document
     */
    public function upsert(string $collectionName, array $query, array $document): void
    {
        $this->mongoClient
            ->getCollection($this->databaseName, $collectionName)
            ->updateOne($query, $document, ['upsert' => true]);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $update
     */
    public function updateOne(string $collectionName, array $query, array $update): bool
    {
        return $this->mongoClient
            ->getCollection($this->databaseName, $collectionName)
            ->updateOne($query, $update)
            ->getMatchedCount() === 1;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     * @return list<array<string, mixed>>
     */
    public function getByQuery(string $collectionName, array $query, array $options = []): array
    {
        $documents = $this->mongoClient
            ->getCollection($this->databaseName, $collectionName)
            ->find($query, ['typeMap' => self::TYPE_MAP] + $options);

        $result = [];
        foreach ($documents as $document) {
            if (is_array($document)) {
                $result[] = $document;
            }
        }
        return $result;
    }

    public function dropDatabase(): void
    {
        $this->mongoClient->dropDatabase($this->databaseName);
    }
}
