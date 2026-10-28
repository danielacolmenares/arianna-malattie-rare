<?php

namespace Database\Seeders;

use App\Services\PathologyDataImporter;
use Illuminate\Database\Seeder;

class PathologySeeder extends Seeder
{
    public function run(PathologyDataImporter $importer): void
    {
        $files = glob(database_path('data/pathologies/*.json')) ?: [];
        sort($files);

        foreach ($files as $file) {
            $importer->importFile($file);
        }
    }
}
