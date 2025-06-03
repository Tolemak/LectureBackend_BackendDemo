<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Persistence;

use MongoDB\Client;
use MongoDB\Model\BSONDocument;

final readonly class DatabaseClient
{
    private Client $mongoClient;

    public function __construct(
        private string $databaseUri,
        private string $databaseName,
    ) {
        $this->mongoClient = new Client($this->databaseUri);
    }

    public function upsert(string $collectionName, array $query, array $document): void
    {
        $this->mongoClient
            ->selectCollection($this->databaseName, $collectionName)
            ->updateOne($query, $document, ['upsert' => true]);
    }

    public function getByQuery(string $collectionName, array $query, array $options = []): array
    {
        $documents = $this->mongoClient
            ->selectCollection($this->databaseName, $collectionName)
            ->find($query, $options);

        $result = [];
        foreach ($documents as $document) {
            if ($document instanceof BSONDocument) {
                $result[] = $document->getArrayCopy();
            } else {
                $result[] = (array)$document;
            }
        }
        return $result;
    }

    public function dropDatabase(): void
    {
        $this->mongoClient->dropDatabase($this->databaseName);
    }
}
