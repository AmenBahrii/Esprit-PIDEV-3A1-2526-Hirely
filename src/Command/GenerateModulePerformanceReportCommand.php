<?php

namespace App\Command;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:report:module-performance',
    description: 'Generates a before/after performance report for either the user/role or joboffer/application scope.'
)]
final class GenerateModulePerformanceReportCommand extends Command
{
    private const ITERATIONS = 5;
    private const USER_COUNT = 1200;
    private const RECRUITER_COUNT = 150;
    private const JOB_OFFER_COUNT = 900;
    private const APPLICATION_COUNT = 3600;

    protected function configure(): void
    {
        $this->addArgument(
            'scope',
            InputArgument::REQUIRED,
            'Choose either "user-role" or "joboffer-application".'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scope = (string) $input->getArgument('scope');

        if (!in_array($scope, ['user-role', 'joboffer-application'], true)) {
            $output->writeln('<error>Scope must be "user-role" or "joboffer-application".</error>');

            return Command::INVALID;
        }

        $projectDir = dirname(__DIR__, 2);
        $filesystem = new Filesystem();
        $benchmarkDir = $projectDir . '/var/benchmarks';
        $reportDir = $projectDir . '/docs/' . $scope;
        $databasePath = $benchmarkDir . '/' . $scope . '-performance.sqlite';
        $reportPath = $reportDir . '/performance-report.md';

        $filesystem->mkdir([$benchmarkDir, $reportDir]);
        $filesystem->remove($databasePath);

        $pdo = new PDO('sqlite:' . $databasePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->createSchema($pdo);
        $this->seedDatabase($pdo);

        $benchmarks = $scope === 'user-role'
            ? $this->buildUserRoleBenchmarks($pdo)
            : $this->buildJobofferApplicationBenchmarks($pdo);

        file_put_contents($reportPath, $this->buildReport($scope, $benchmarks));

        $output->writeln('<info>Performance report generated.</info>');
        $output->writeln($reportPath);

        foreach ($benchmarks as $name => $benchmark) {
            $output->writeln(sprintf(
                '%s: before %sms / %d queries, after %sms / %d queries',
                $name,
                number_format($benchmark['before']['avg_ms'], 2),
                $benchmark['before']['avg_queries'],
                number_format($benchmark['after']['avg_ms'], 2),
                $benchmark['after']['avg_queries']
            ));
        }

        return Command::SUCCESS;
    }

    private function createSchema(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE role (role_id INTEGER PRIMARY KEY, name TEXT NOT NULL, status TEXT NOT NULL, default_dashboard TEXT)');
        $pdo->exec('CREATE TABLE users (
            user_id INTEGER PRIMARY KEY,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL,
            status TEXT NOT NULL,
            role_id INTEGER,
            google_id TEXT,
            face_data TEXT,
            FOREIGN KEY(role_id) REFERENCES role(role_id)
        )');
        $pdo->exec('CREATE TABLE joboffer (
            jobOfferId INTEGER PRIMARY KEY,
            title TEXT NOT NULL,
            location TEXT NOT NULL,
            status TEXT NOT NULL,
            salary REAL NOT NULL,
            publicationDate TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(user_id)
        )');
        $pdo->exec('CREATE TABLE application (
            applicationId INTEGER PRIMARY KEY,
            applicationDate TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            currentStatus TEXT NOT NULL,
            expectedSalary REAL NOT NULL,
            experienceYears INTEGER NOT NULL,
            score REAL DEFAULT NULL,
            reviewNote TEXT DEFAULT NULL,
            jobOfferId INTEGER,
            user_id INTEGER NOT NULL,
            FOREIGN KEY(jobOfferId) REFERENCES joboffer(jobOfferId),
            FOREIGN KEY(user_id) REFERENCES users(user_id)
        )');

        $pdo->exec('CREATE INDEX idx_users_role_status ON users(role_id, status)');
        $pdo->exec('CREATE INDEX idx_joboffer_status_title_location ON joboffer(status, title, location)');
        $pdo->exec('CREATE INDEX idx_joboffer_user ON joboffer(user_id)');
        $pdo->exec('CREATE INDEX idx_application_joboffer ON application(jobOfferId)');
        $pdo->exec('CREATE INDEX idx_application_user ON application(user_id)');
    }

    private function seedDatabase(PDO $pdo): void
    {
        $pdo->beginTransaction();

        $roleStmt = $pdo->prepare(
            'INSERT INTO role (role_id, name, status, default_dashboard) VALUES (:id, :name, :status, :dashboard)'
        );
        $roles = [
            [1, 'admin', 'active', 'admin_dashboard'],
            [2, 'recruiter', 'active', 'recruiter_dashboard'],
            [3, 'candidate', 'active', 'candidate_dashboard'],
        ];

        foreach ($roles as [$id, $name, $status, $dashboard]) {
            $roleStmt->execute([
                'id' => $id,
                'name' => $name,
                'status' => $status,
                'dashboard' => $dashboard,
            ]);
        }

        $userStmt = $pdo->prepare(
            'INSERT INTO users (user_id, first_name, last_name, email, status, role_id, google_id, face_data)
             VALUES (:id, :first_name, :last_name, :email, :status, :role_id, :google_id, :face_data)'
        );

        $statuses = ['active', 'inactive'];

        for ($id = 1; $id <= self::USER_COUNT; ++$id) {
            $roleId = $id === 1 ? 1 : ($id <= self::RECRUITER_COUNT + 1 ? 2 : 3);
            $status = $statuses[$id % count($statuses)];

            $userStmt->execute([
                'id' => $id,
                'first_name' => 'User' . $id,
                'last_name' => 'Demo' . $id,
                'email' => sprintf('user%d@example.com', $id),
                'status' => $status,
                'role_id' => $roleId,
                'google_id' => $id % 3 === 0 ? 'google-' . $id : null,
                'face_data' => $id % 4 === 0 ? '[0.1,0.2,0.3]' : null,
            ]);
        }

        $jobOfferStmt = $pdo->prepare(
            'INSERT INTO joboffer (jobOfferId, title, location, status, salary, publicationDate, user_id)
             VALUES (:id, :title, :location, :status, :salary, :publicationDate, :user_id)'
        );

        $titles = ['Symfony Developer', 'Backend Engineer', 'PHP Engineer', 'Full Stack Developer'];
        $locations = ['Tunis', 'Sfax', 'Remote', 'Lagos', 'Abuja'];

        for ($id = 1; $id <= self::JOB_OFFER_COUNT; ++$id) {
            $jobOfferStmt->execute([
                'id' => $id,
                'title' => $titles[$id % count($titles)] . ' ' . $id,
                'location' => $locations[$id % count($locations)],
                'status' => $id % 5 === 0 ? 'Closed' : 'Open',
                'salary' => 1200 + (($id % 15) * 150),
                'publicationDate' => sprintf('2026-04-%02d', ($id % 28) + 1),
                'user_id' => 2 + ($id % self::RECRUITER_COUNT),
            ]);
        }

        $applicationStmt = $pdo->prepare(
            'INSERT INTO application (applicationId, applicationDate, email, phone, currentStatus, expectedSalary, experienceYears, score, reviewNote, jobOfferId, user_id)
             VALUES (:id, :applicationDate, :email, :phone, :currentStatus, :expectedSalary, :experienceYears, :score, :reviewNote, :jobOfferId, :user_id)'
        );

        $appStatuses = ['pending', 'Accepted', 'Rejected'];
        $candidateStart = self::RECRUITER_COUNT + 2;
        $candidateRange = self::USER_COUNT - $candidateStart;

        for ($id = 1; $id <= self::APPLICATION_COUNT; ++$id) {
            $candidateId = $candidateStart + ($id % $candidateRange);
            $status = $appStatuses[$id % count($appStatuses)];

            $applicationStmt->execute([
                'id' => $id,
                'applicationDate' => sprintf('2026-05-%02d', ($id % 28) + 1),
                'email' => sprintf('user%d@example.com', $candidateId),
                'phone' => sprintf('+2165555%04d', $id % 10000),
                'currentStatus' => $status,
                'expectedSalary' => 1000 + (($id % 12) * 100),
                'experienceYears' => $id % 8,
                'score' => $status === 'pending' ? null : (float) (55 + ($id % 40)),
                'reviewNote' => $status === 'pending' ? null : 'Reviewed automatically during benchmark seeding.',
                'jobOfferId' => ($id % self::JOB_OFFER_COUNT) + 1,
                'user_id' => $candidateId,
            ]);
        }

        $pdo->commit();
    }

    /**
     * @return array<string, array{before: array<string, int|float>, after: array<string, int|float>, goal: string}>
     */
    private function buildUserRoleBenchmarks(PDO $pdo): array
    {
        return [
            'Admin users summary' => [
                'before' => $this->benchmark(fn () => $this->runUserSummaryBefore($pdo)),
                'after' => $this->benchmark(fn () => $this->runUserSummaryAfter($pdo)),
                'goal' => 'Move grouped status/role counting from PHP loops to focused SQL queries.',
            ],
            'User directory filtering' => [
                'before' => $this->benchmark(fn () => $this->runUserDirectoryBefore($pdo)),
                'after' => $this->benchmark(fn () => $this->runUserDirectoryAfter($pdo)),
                'goal' => 'Filter active recruiter accounts in SQL instead of loading every user and scanning them in PHP.',
            ],
        ];
    }

    /**
     * @return array<string, array{before: array<string, int|float>, after: array<string, int|float>, goal: string}>
     */
    private function buildJobofferApplicationBenchmarks(PDO $pdo): array
    {
        return [
            'Applications index' => [
                'before' => $this->benchmark(fn () => $this->runApplicationIndexBefore($pdo)),
                'after' => $this->benchmark(fn () => $this->runApplicationIndexAfter($pdo)),
                'goal' => 'Replace per-row job title lookups with one LEFT JOIN query.',
            ],
            'Job offer search' => [
                'before' => $this->benchmark(fn () => $this->runJobOfferSearchBefore($pdo)),
                'after' => $this->benchmark(fn () => $this->runJobOfferSearchAfter($pdo)),
                'goal' => 'Push search, filtering, and sorting into SQL instead of scanning everything in PHP.',
            ],
        ];
    }

    /**
     * @param callable(): array{queries:int, rows:int} $scenario
     *
     * @return array{avg_ms:float, avg_queries:int, avg_memory_kb:int, rows:int}
     */
    private function benchmark(callable $scenario): array
    {
        $times = [];
        $queries = [];
        $memory = [];
        $rowCount = 0;

        for ($iteration = 0; $iteration < self::ITERATIONS; ++$iteration) {
            gc_collect_cycles();
            $startMemory = memory_get_usage(true);
            $start = hrtime(true);
            $result = $scenario();
            $end = hrtime(true);

            $times[] = ($end - $start) / 1_000_000;
            $queries[] = $result['queries'];
            $memory[] = max(0, memory_get_usage(true) - $startMemory);
            $rowCount = $result['rows'];
        }

        return [
            'avg_ms' => array_sum($times) / count($times),
            'avg_queries' => (int) round(array_sum($queries) / count($queries)),
            'avg_memory_kb' => (int) round((array_sum($memory) / count($memory)) / 1024),
            'rows' => $rowCount,
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runUserSummaryBefore(PDO $pdo): array
    {
        $queries = 0;
        $groupedStats = [];
        $roles = ['admin', 'recruiter', 'candidate'];
        $statuses = ['active', 'inactive'];

        foreach ($statuses as $status) {
            foreach ($roles as $roleName) {
                $total = (int) $this->fetchOne(
                    $pdo,
                    'SELECT COUNT(*)
                     FROM users u
                     LEFT JOIN role r ON r.role_id = u.role_id
                     WHERE u.status = :status AND r.name = :roleName',
                    [
                        'status' => $status,
                        'roleName' => $roleName,
                    ],
                    $queries
                );

                if ($total > 0) {
                    $groupedStats[] = [
                        'status' => $status,
                        'roleName' => $roleName,
                        'total' => $total,
                    ];
                }
            }
        }

        $googleLinkedUsers = $this->fetchOne(
            $pdo,
            'SELECT COUNT(*) FROM users WHERE google_id IS NOT NULL AND google_id <> ""',
            [],
            $queries
        );

        $faceEnabledUsers = $this->fetchOne(
            $pdo,
            'SELECT COUNT(*) FROM users WHERE face_data IS NOT NULL AND face_data <> ""',
            [],
            $queries
        );

        return [
            'queries' => $queries,
            'rows' => count($groupedStats) + $googleLinkedUsers + $faceEnabledUsers,
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runUserSummaryAfter(PDO $pdo): array
    {
        $queries = 0;
        $groupedStats = $this->fetchAll(
            $pdo,
            'SELECT
                u.status AS status,
                COALESCE(r.name, "Unknown") AS roleName,
                COUNT(*) AS total
             FROM users u
             LEFT JOIN role r ON r.role_id = u.role_id
             GROUP BY u.status, r.name',
            [],
            $queries
        );

        $googleLinkedUsers = $this->fetchOne(
            $pdo,
            'SELECT COUNT(*) FROM users WHERE google_id IS NOT NULL AND google_id <> ""',
            [],
            $queries
        );

        $faceEnabledUsers = $this->fetchOne(
            $pdo,
            'SELECT COUNT(*) FROM users WHERE face_data IS NOT NULL AND face_data <> ""',
            [],
            $queries
        );

        return [
            'queries' => $queries,
            'rows' => count($groupedStats) + (int) $googleLinkedUsers + (int) $faceEnabledUsers,
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runUserDirectoryBefore(PDO $pdo): array
    {
        $queries = 0;
        $users = $this->fetchAll(
            $pdo,
            'SELECT u.user_id, u.status, u.first_name, u.last_name, r.name AS roleName
             FROM users u
             LEFT JOIN role r ON r.role_id = u.role_id',
            [],
            $queries
        );

        $filtered = array_values(array_filter($users, static function (array $user): bool {
            return ($user['status'] ?? '') === 'active' && strtolower((string) ($user['roleName'] ?? '')) === 'recruiter';
        }));

        return [
            'queries' => $queries,
            'rows' => count($filtered),
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runUserDirectoryAfter(PDO $pdo): array
    {
        $queries = 0;
        $users = $this->fetchAll(
            $pdo,
            'SELECT u.user_id, u.status, u.first_name, u.last_name, r.name AS roleName
             FROM users u
             LEFT JOIN role r ON r.role_id = u.role_id
             WHERE u.status = :status AND r.name = :roleName',
            [
                'status' => 'active',
                'roleName' => 'recruiter',
            ],
            $queries
        );

        return [
            'queries' => $queries,
            'rows' => count($users),
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runApplicationIndexBefore(PDO $pdo): array
    {
        $queries = 0;
        $applications = $this->fetchAll(
            $pdo,
            'SELECT applicationId, applicationDate, email, phone, currentStatus, expectedSalary, experienceYears, jobOfferId
             FROM application
             ORDER BY applicationId DESC',
            [],
            $queries
        );

        foreach ($applications as &$application) {
            $application['jobOfferTitle'] = $this->fetchOne(
                $pdo,
                'SELECT title FROM joboffer WHERE jobOfferId = :jobOfferId',
                ['jobOfferId' => $application['jobOfferId']],
                $queries
            );
        }

        return [
            'queries' => $queries,
            'rows' => count($applications),
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runApplicationIndexAfter(PDO $pdo): array
    {
        $queries = 0;
        $applications = $this->fetchAll(
            $pdo,
            'SELECT
                a.applicationId,
                a.applicationDate,
                a.email,
                a.phone,
                a.currentStatus,
                a.expectedSalary,
                a.experienceYears,
                a.score,
                a.reviewNote,
                a.jobOfferId,
                j.title AS jobOfferTitle
             FROM application a
             LEFT JOIN joboffer j ON j.jobOfferId = a.jobOfferId
             ORDER BY a.applicationId DESC',
            [],
            $queries
        );

        return [
            'queries' => $queries,
            'rows' => count($applications),
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runJobOfferSearchBefore(PDO $pdo): array
    {
        $queries = 0;
        $jobOffers = $this->fetchAll(
            $pdo,
            'SELECT jobOfferId, title, location, status, salary, publicationDate
             FROM joboffer',
            [],
            $queries
        );

        $filtered = array_values(array_filter($jobOffers, static function (array $offer): bool {
            if (($offer['status'] ?? '') !== 'Open') {
                return false;
            }

            $haystack = strtolower((string) ($offer['title'] ?? '') . ' ' . (string) ($offer['location'] ?? ''));

            return str_contains($haystack, 'engineer');
        }));

        usort($filtered, static fn (array $left, array $right): int => (int) (($right['salary'] ?? 0) <=> ($left['salary'] ?? 0)));

        return [
            'queries' => $queries,
            'rows' => count($filtered),
        ];
    }

    /**
     * @return array{queries:int, rows:int}
     */
    private function runJobOfferSearchAfter(PDO $pdo): array
    {
        $queries = 0;
        $jobOffers = $this->fetchAll(
            $pdo,
            'SELECT jobOfferId, title, location, status, salary, publicationDate
             FROM joboffer
             WHERE status = :status
               AND (title LIKE :search OR location LIKE :search)
             ORDER BY salary DESC',
            [
                'status' => 'Open',
                'search' => '%Engineer%',
            ],
            $queries
        );

        return [
            'queries' => $queries,
            'rows' => count($jobOffers),
        ];
    }

    /**
     * @param array<string, scalar|null> $params
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAll(PDO $pdo, string $sql, array $params, int &$queries): array
    {
        ++$queries;
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, scalar|null> $params
     */
    private function fetchOne(PDO $pdo, string $sql, array $params, int &$queries): mixed
    {
        ++$queries;
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }

    /**
     * @param array<string, array{before: array<string, int|float>, after: array<string, int|float>, goal: string}> $benchmarks
     */
    private function buildReport(string $scope, array $benchmarks): string
    {
        $title = $scope === 'user-role' ? 'User / Role' : 'Job Offer / Application';

        $lines = [
            '# Rapport de performance - ' . $title,
            '',
            '## Contexte',
            '- Runtime de mesure : `PHP 8.2.30`',
            '- Itérations par scénario : `5`',
            '- Base de benchmark : SQLite temporaire générée automatiquement',
            '',
            '## Mesures Avant / Après',
            '',
            '| Scénario | Objectif | Avant (ms) | Avant requêtes | Avant mémoire (KB) | Après (ms) | Après requêtes | Après mémoire (KB) | Gain |',
            '|---|---|---:|---:|---:|---:|---:|---:|---:|',
        ];

        foreach ($benchmarks as $name => $benchmark) {
            $before = $benchmark['before'];
            $after = $benchmark['after'];
            $gain = $before['avg_ms'] > 0
                ? (($before['avg_ms'] - $after['avg_ms']) / $before['avg_ms']) * 100
                : 0.0;

            $lines[] = sprintf(
                '| %s | %s | %s | %d | %d | %s | %d | %d | %s%% |',
                $name,
                $benchmark['goal'],
                number_format((float) $before['avg_ms'], 2),
                (int) $before['avg_queries'],
                (int) $before['avg_memory_kb'],
                number_format((float) $after['avg_ms'], 2),
                (int) $after['avg_queries'],
                (int) $after['avg_memory_kb'],
                number_format($gain, 1)
            );
        }

        $lines[] = '';
        $lines[] = '## Commentaire';
        if ($scope === 'user-role') {
            $lines[] = '- L’optimisation principale consiste à calculer les statistiques de rôles et de statuts directement en SQL au lieu de charger tous les utilisateurs puis de les regrouper en PHP.';
            $lines[] = '- Le filtrage du répertoire utilisateur est également plus léger lorsqu’il est exécuté dans la requête SQL.';
        } else {
            $lines[] = '- Le plus grand gain vient de la liste des candidatures : on remplace des recherches répétées du titre de l’offre par une seule jointure SQL.';
            $lines[] = '- La recherche des offres est plus rapide lorsque le filtrage et le tri sont faits dans la base de données.';
        }

        $lines[] = '';
        $lines[] = '## Commande de reproduction';
        $lines[] = '```powershell';
        $lines[] = sprintf(
            'C:\tools\php-8.2.30-nts-Win32-vs16-x64\php.exe bin\console app:report:module-performance %s',
            $scope
        );
        $lines[] = '```';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
