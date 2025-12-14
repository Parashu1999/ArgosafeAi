<?php
// includes/AI_Trainer.php
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure Composer autoload

use Phpml\Classification\KNearestNeighbors;
use Phpml\Dataset\CsvDataset;
use Phpml\ModelManager;

class AI_Trainer {
    public static function train() {
        // 1. Increase memory for training
        ini_set('memory_limit', '-1');
        set_time_limit(300); // Allow 5 minutes max

        try {
            // 2. Load Dataset
            // Assumes diseases.csv has 4 symptoms (cols 0-3) and 1 label (col 4)
            $dataset = new CsvDataset(__DIR__ . '/../data/diseases.csv', 4, true); // true = has header

            // 3. Train Model (Using KNN as it's robust for this size)
            $classifier = new KNearestNeighbors();
            $classifier->train($dataset->getSamples(), $dataset->getTargets());

            // 4. Save Model
            $modelManager = new ModelManager();
            $modelPath = __DIR__ . '/../models/disease_model.phpml';
            $modelManager->saveToFile($classifier, $modelPath);

            return ["success" => true, "message" => "AI Model successfully retrained with latest data."];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Training Failed: " . $e->getMessage()];
        }
    }
}
?>