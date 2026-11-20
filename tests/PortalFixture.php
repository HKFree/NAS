<?php

declare(strict_types=1);

namespace NasTests;

use CurlHandle;
use Nette\Utils\FileSystem;
use PDO;
use RuntimeException;
use Tester\Assert;

final class PortalFixture
{
    private string $directory;
    private string $portalUrl;
    private string $storageUrl;
    private CurlHandle $client;
    private array $processes = [];

    public function __construct()
    {
        $root = dirname(__DIR__);
        $fixtures = __DIR__ . '/fixtures';
        $this->directory = sys_get_temp_dir() . '/hkfree-nas-' . bin2hex(random_bytes(12));
        if (!mkdir($this->directory, 0700)) {
            throw new RuntimeException('Could not create test directory.');
        }
        register_shutdown_function($this->close(...));

        $directory = $this->directory;
        // Copy application code and shared defaults. Leave deployment configuration and privileged scripts out.
        foreach (['forms', 'model', 'presenters', 'router', 'bootstrap.php', 'config/config.neon'] as $path) {
            FileSystem::copy("$root/app/$path", "$directory/app/$path");
        }
        FileSystem::copy("$fixtures/FixtureAuthenticator.php", "$directory/app/FixtureAuthenticator.php");
        FileSystem::copy("$root/www/index.php", "$directory/www/index.php");
        FileSystem::copy("$fixtures/router.php", "$directory/router.php");
        FileSystem::copy("$fixtures/sudo.sh", "$directory/bin/sudo");
        chmod("$directory/bin/sudo", 0700);
        symlink("$root/vendor", "$directory/vendor");
        foreach (['log', 'temp', 'sessions'] as $path) {
            FileSystem::createDir("$directory/$path");
        }
        $this->database()->exec(file_get_contents("$fixtures/portal.sql"));

        $storagePort = $this->start(
            ['python3', "$fixtures/otesanek.py", '--port', '0'],
            'storage',
            '/^(\d+)$/m',
            'stdout',
            $directory,
        );
        $this->storageUrl = "http://127.0.0.1:$storagePort";
        FileSystem::write("$directory/app/config/config.local.neon", <<<NEON
            parameters:
                ldapServerURL: 'ldap://127.0.0.1:1'
                storageServerURL: '$this->storageUrl'

            database:
                dsn: 'sqlite:$directory/portal.sqlite'
                options:
                    lazy: true

            services:
                authenticator!:
                    create: NasTests\FixtureAuthenticator
            NEON);

        // Only the fake sudo can be found by commands invoked by the application.
        $portalPort = $this->start(
            [PHP_BINARY, '-d', "session.save_path=$directory/sessions",
                '-S', '127.0.0.1:0', '-t', "$directory/www", "$directory/router.php"],
            'portal',
            '/\(http:\/\/127\.0\.0\.1:(\d+)\) started/',
            'stderr',
            "$directory/www",
            ['PATH' => "$directory/bin", 'NAS_MOCK_COMMANDS_LOG' => "$directory/commands.log"],
        );
        $this->portalUrl = "http://127.0.0.1:$portalPort";
        $this->client = curl_init();
        curl_setopt_array($this->client, [
            CURLOPT_PROXY => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_HTTPHEADER => ['Host: localhost'],
        ]);
    }

    public function request(string $path, int $status, ?array $form = null): string
    {
        curl_setopt($this->client, CURLOPT_URL, $this->portalUrl . $path);
        if ($form === null) {
            curl_setopt($this->client, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->client, CURLOPT_POSTFIELDS, http_build_query($form));
        }

        $body = curl_exec($this->client);
        if ($body === false) {
            throw new RuntimeException(curl_error($this->client));
        }
        $actualStatus = curl_getinfo($this->client, CURLINFO_RESPONSE_CODE);
        $diagnostics = $actualStatus === $status ? '' : $this->diagnostics();
        Assert::same($status, $actualStatus, $path . $diagnostics);
        return $body;
    }

    public function signIn(): void
    {
        $this->request('/sign/in', 200);
        $this->request('/sign/in', 303, [
            'username' => '1234',
            'password' => 'fixture-password',
            'remember' => '1',
            '_do' => 'signInForm-submit',
        ]);
    }

    public function database(): PDO
    {
        return new PDO("sqlite:$this->directory/portal.sqlite");
    }

    public function storageFolders(): array
    {
        return array_column(json_decode(file_get_contents($this->storageUrl . '/list'), true, flags: JSON_THROW_ON_ERROR), null, 'name');
    }

    public function commands(): array
    {
        return file("$this->directory/commands.log", FILE_IGNORE_NEW_LINES);
    }

    private function start(array $command, string $name, string $readyPattern, string $readyStream, string $cwd, ?array $env = null): int
    {
        $stdout = "$this->directory/$name.stdout";
        $stderr = "$this->directory/$name.stderr";
        $process = proc_open($command, [
            ['file', '/dev/null', 'r'],
            ['file', $stdout, 'w'],
            ['file', $stderr, 'w'],
        ], $pipes, $cwd, $env);
        if (!is_resource($process)) {
            throw new RuntimeException("Could not start $name.");
        }
        $this->processes[] = $process;
        $readyFile = $readyStream === 'stdout' ? $stdout : $stderr;
        $deadline = microtime(true) + 5;
        do {
            if (preg_match($readyPattern, file_get_contents($readyFile), $matches)) {
                return (int) $matches[1];
            }
            if (!proc_get_status($process)['running']) {
                break;
            }
            usleep(20_000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException("$name did not start: " . file_get_contents($stderr));
    }

    private function diagnostics(): string
    {
        $log = "$this->directory/log/exception.log";
        return "\n" . file_get_contents(is_file($log) ? $log : "$this->directory/portal.stderr");
    }

    private function close(): void
    {
        foreach (array_reverse($this->processes) as $process) {
            proc_terminate($process);
            proc_close($process);
        }
        FileSystem::delete($this->directory);
    }
}
