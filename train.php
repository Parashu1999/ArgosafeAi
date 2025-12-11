<?php
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\Classification\NaiveBayes;
use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;

echo "🌱 Starting AgroSafeAI Training...\n";

$modelManager = new ModelManager();

// ==========================================
// 1. TRAIN DISEASE CLASSIFIER (Feature 1)
// ==========================================
echo "🔹 Training Disease Diagnosis Model... ";

// Load Data manually to handle text properly
$diseaseSamples = [];
$diseaseTargets = [];

if (($handle = fopen("data/diseases.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $diseaseSamples[] = [$data[0]]; // Symptom (wrapped in array)
        $diseaseTargets[] = $data[1];   // Disease Name
    }
    fclose($handle);
}

$classifier = new NaiveBayes();
$classifier->train($diseaseSamples, $diseaseTargets);

// Save the trained model
$modelManager->saveToFile($classifier, 'models/disease_classifier.phpml');
echo "Done! ✅\n";

// ==========================================
// 2. TRAIN RESOURCE PREDICTOR (Feature 2)
// ==========================================
echo "🔹 Training Treatment Resource Models... ";

$resourceSamples = [];
$waterTargets = [];
$fungicideTargets = [];

if (($handle = fopen("data/treatments.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Input: [Farm Size, Severity]
        $resourceSamples[] = [(int)$data[1], (int)$data[2]]; 
        
        // Targets: Water and Fungicide
        $waterTargets[] = (int)$data[3];
        $fungicideTargets[] = (int)$data[4];
    }
    fclose($handle);
}

// Train Water Model
$waterModel = new LeastSquares();
$waterModel->train($resourceSamples, $waterTargets);
$modelManager->saveToFile($waterModel, 'models/water_predictor.phpml');

// Train Fungicide Model
$fungicideModel = new LeastSquares();
$fungicideModel->train($resourceSamples, $fungicideTargets);
$modelManager->saveToFile($fungicideModel, 'models/fungicide_predictor.phpml');

echo "Done! ✅\n";
echo "\n🎉 All models trained and saved to /models folder.\n";