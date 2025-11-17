<?php

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['ELYTICA_TOKEN'];

echo "Testing Elytica connection...\n";
echo "Token: " . substr($token, 0, 10) . "...\n\n";

try {
    $client = new \Elytica\ComputeClient\ComputeService($token);

    echo "1. Getting user info...\n";
    $user = $client->whoami();
    echo "User: " . json_encode($user, JSON_PRETTY_PRINT) . "\n\n";

    echo "2. Getting applications...\n";
    $apps = $client->getApplications();
    echo "Applications: " . json_encode($apps, JSON_PRETTY_PRINT) . "\n\n";

    echo "3. Getting projects...\n";
    $projects = $client->getProjects();
    echo "Projects: " . json_encode($projects, JSON_PRETTY_PRINT) . "\n\n";

    echo "SUCCESS! Connection working.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
