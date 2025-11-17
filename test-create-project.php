<?php

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['ELYTICA_TOKEN'];
$applicationId = $_ENV['ELYTICA_APPLICATION_ID'] ?? 14;

echo "Testing Elytica Project Creation...\n";
echo "Token: " . substr($token, 0, 10) . "...\n";
echo "Application ID: " . $applicationId . "\n\n";

try {
    $client = new \Elytica\ComputeClient\ComputeService($token);

    echo "1. Getting current projects...\n";
    $projects = $client->getProjects();
    echo "Current projects: " . json_encode($projects, JSON_PRETTY_PRINT) . "\n\n";

    // Check if diet-plan project exists
    $existingProject = null;
    if ($projects) {
        foreach ($projects as $project) {
            if (isset($project->name) && $project->name === 'diet-plan') {
                $existingProject = $project;
                echo "Found existing 'diet-plan' project with ID: " . $project->id . "\n\n";
                break;
            }
        }
    }

    if (!$existingProject) {
        echo "2. Creating new 'diet-plan' project...\n";
        echo "   Project name: diet-plan\n";
        echo "   Description: Diet plan optimization project\n";
        echo "   Application ID: " . $applicationId . "\n\n";

        $response = $client->createNewProject(
            'diet-plan',
            'Diet plan optimization project',
            $applicationId,
            null, // webhook_url
            null  // webhook_secret
        );

        echo "Project creation response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

        if ($response && isset($response->id)) {
            echo "✓ SUCCESS! Project created with ID: " . $response->id . "\n";
        } else {
            echo "✗ FAILED! No project ID returned.\n";
            echo "Response type: " . gettype($response) . "\n";
        }
    } else {
        echo "Project already exists, skipping creation.\n";
    }

    echo "\n3. Getting updated projects list...\n";
    $updatedProjects = $client->getProjects();
    echo "Updated projects: " . json_encode($updatedProjects, JSON_PRETTY_PRINT) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
