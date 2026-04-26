<?php

use App\CsvImport\DTO\ImportPlan;
use App\CsvImport\Importers\LocadoresCsvImporter;
use App\Models\CsvImportJob;
use App\Models\Locador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('simula criar/atualizar e faz upsert por documento no importador de locadores', function () {
    $importer = app(LocadoresCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'locadores',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/fake.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'locadores', mapping: [
        'nome' => 'Nome',
        'documento' => 'Documento',
    ]);

    $row1 = ['nome' => 'Maria', 'documento' => '123.456.789-01'];
    $sim1 = $importer->simulateRow($row1, 2, $plan, $job);
    expect($sim1->status)->toBe('ok')
        ->and($sim1->action)->toBe('criar');

    $imp1 = $importer->importRow($row1, 2, $plan, $job);
    expect($imp1->status)->toBe('ok')
        ->and($imp1->action)->toBe('criar');

    expect(Locador::query()->where('documento', '12345678901')->exists())->toBeTrue();

    $row2 = ['nome' => 'Maria da Silva', 'documento' => '12345678901'];
    $sim2 = $importer->simulateRow($row2, 3, $plan, $job);
    expect($sim2->status)->toBe('ok')
        ->and($sim2->action)->toBe('atualizar');

    $imp2 = $importer->importRow($row2, 3, $plan, $job);
    expect($imp2->status)->toBe('ok')
        ->and($imp2->action)->toBe('atualizar');

    $locador = Locador::query()->where('documento', '12345678901')->first();
    expect($locador)->not->toBeNull()
        ->and($locador->nome)->toBe('Maria da Silva');
});

it('retorna erro quando documento está ausente', function () {
    $importer = app(LocadoresCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'locadores',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/fake.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'locadores', mapping: []);
    $row = ['nome' => 'João', 'documento' => ''];
    $res = $importer->simulateRow($row, 2, $plan, $job);

    expect($res->status)->toBe('erro')
        ->and($res->errors)->toContain('documento ausente');
});
