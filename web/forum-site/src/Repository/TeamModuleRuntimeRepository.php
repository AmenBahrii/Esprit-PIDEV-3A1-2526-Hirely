<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class TeamModuleRuntimeRepository
{
    private const ROW_LIMIT = 12;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array{schema_ready: bool, table: ?string, total: int, columns: list<string>, rows: list<array<string, mixed>>, schema_message: ?string}
     */
    public function jobOfferDashboard(): array
    {
        return $this->readModuleTable(
            ['joboffer', 'joboffers', 'job_offer', 'job_offers'],
            ['jobOfferId', 'title', 'contractType', 'salary', 'location', 'experienceRequired', 'publicationDate', 'status'],
            'Job Offer'
        );
    }

    /**
     * @return array{schema_ready: bool, table: ?string, total: int, columns: list<string>, rows: list<array<string, mixed>>, schema_message: ?string}
     */
    public function applicationDashboard(): array
    {
        return $this->readModuleTable(
            ['application', 'applications'],
            ['applicationId', 'applicationDate', 'currentStatus', 'email', 'phone', 'expectedSalary', 'experienceYears', 'score'],
            'Application'
        );
    }

    /**
     * @return array{schema_ready: bool, table: ?string, total: int, columns: list<string>, rows: list<array<string, mixed>>, schema_message: ?string}
     */
    public function evaluationDashboard(): array
    {
        return $this->readModuleTable(
            ['interview_evaluations', 'interviewevaluation', 'evaluation', 'evaluations'],
            ['id', 'evaluation_id', 'interview_id', 'criteria_id', 'score', 'overallRating', 'recommendation', 'hireDecision', 'comments', 'createdAt'],
            'Evaluation'
        );
    }

    /**
     * @param list<string> $candidateTables
     * @param list<string> $preferredColumns
     * @return array{schema_ready: bool, table: ?string, total: int, columns: list<string>, rows: list<array<string, mixed>>, schema_message: ?string}
     */
    private function readModuleTable(array $candidateTables, array $preferredColumns, string $moduleName): array
    {
        $table = $this->findExistingTable($candidateTables);

        if ($table === null) {
            return [
                'schema_ready' => false,
                'table' => null,
                'total' => 0,
                'columns' => [],
                'rows' => [],
                'schema_message' => sprintf('%s tables are not available yet. Apply docs/integration/team_modules_runtime_schema.sql in a dev/test database, then reload this page.', $moduleName),
            ];
        }

        $availableColumns = $this->listColumnNames($table);
        $columns = $this->selectDisplayColumns($availableColumns, $preferredColumns);

        if ($columns === []) {
            return [
                'schema_ready' => true,
                'table' => $table,
                'total' => $this->countRows($table),
                'columns' => [],
                'rows' => [],
                'schema_message' => sprintf('%s table exists, but no displayable columns were detected.', $moduleName),
            ];
        }

        return [
            'schema_ready' => true,
            'table' => $table,
            'total' => $this->countRows($table),
            'columns' => $columns,
            'rows' => $this->fetchRows($table, $columns, $availableColumns),
            'schema_message' => null,
        ];
    }

    /**
     * @param list<string> $candidateTables
     */
    private function findExistingTable(array $candidateTables): ?string
    {
        foreach ($candidateTables as $table) {
            $exists = (bool) $this->connection->fetchOne(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1',
                ['table' => $table]
            );

            if ($exists) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function listColumnNames(string $table): array
    {
        return array_map(
            static fn (mixed $column): string => (string) $column,
            $this->connection->fetchFirstColumn(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table ORDER BY ordinal_position',
                ['table' => $table]
            )
        );
    }

    /**
     * @param list<string> $availableColumns
     * @param list<string> $preferredColumns
     * @return list<string>
     */
    private function selectDisplayColumns(array $availableColumns, array $preferredColumns): array
    {
        $availableByLowerName = [];
        foreach ($availableColumns as $column) {
            $availableByLowerName[strtolower($column)] = $column;
        }

        $selected = [];
        foreach ($preferredColumns as $column) {
            $key = strtolower($column);
            if (isset($availableByLowerName[$key])) {
                $selected[] = $availableByLowerName[$key];
            }
        }

        if ($selected !== []) {
            return $selected;
        }

        return array_slice($availableColumns, 0, 8);
    }

    private function countRows(string $table): int
    {
        return (int) $this->connection->fetchOne(sprintf(
            'SELECT COUNT(*) FROM %s',
            $this->connection->quoteIdentifier($table)
        ));
    }

    /**
     * @param list<string> $columns
     * @param list<string> $availableColumns
     * @return list<array<string, mixed>>
     */
    private function fetchRows(string $table, array $columns, array $availableColumns): array
    {
        $quotedColumns = array_map(
            fn (string $column): string => $this->connection->quoteIdentifier($column),
            $columns
        );
        $orderColumn = $this->findOrderColumn($availableColumns);
        $orderSql = $orderColumn !== null
            ? ' ORDER BY ' . $this->connection->quoteIdentifier($orderColumn) . ' DESC'
            : '';

        return $this->connection->fetchAllAssociative(sprintf(
            'SELECT %s FROM %s%s LIMIT %d',
            implode(', ', $quotedColumns),
            $this->connection->quoteIdentifier($table),
            $orderSql,
            self::ROW_LIMIT
        ));
    }

    /**
     * @param list<string> $availableColumns
     */
    private function findOrderColumn(array $availableColumns): ?string
    {
        $preferred = ['id', 'jobOfferId', 'applicationId', 'createdAt', 'lastUpdateDate', 'applicationDate', 'publicationDate'];
        $availableByLowerName = [];
        foreach ($availableColumns as $column) {
            $availableByLowerName[strtolower($column)] = $column;
        }

        foreach ($preferred as $column) {
            $key = strtolower($column);
            if (isset($availableByLowerName[$key])) {
                return $availableByLowerName[$key];
            }
        }

        return $availableColumns[0] ?? null;
    }
}
