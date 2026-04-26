<?php

use App\CsvImport\DTO\ImportPlan;
use App\CsvImport\Importers\LocatariosCsvImporter;
use App\Models\CsvImportJob;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('simula criar/atualizar e faz upsert por documento no importador de locatários', function () {
    $importer = app(LocatariosCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'locatarios',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/locatarios.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'locatarios', mapping: [
        'nome' => 'Nome',
        'documento' => 'Documento',
    ]);

    $row1 = [
        'nome' => 'Ana',
        'documento' => '123.456.789-01',
        'telefone' => '(11) 3333-4444',
        'status' => 'ativo',
    ];

    $sim1 = $importer->simulateRow($row1, 2, $plan, $job);
    expect($sim1->status)->toBe('ok')
        ->and($sim1->action)->toBe('criar');

    $imp1 = $importer->importRow($row1, 2, $plan, $job);
    expect($imp1->status)->toBe('ok')
        ->and($imp1->action)->toBe('criar');

    $locatario = Locatario::query()->where('documento', '12345678901')->first();
    expect($locatario)->not->toBeNull()
        ->and($locatario->telefone)->toBe('1133334444')
        ->and((string) $locatario->status)->toBe('1');

    $row2 = [
        'nome' => 'Ana Maria',
        'documento' => '12345678901',
        'status' => 'inativo',
    ];

    $sim2 = $importer->simulateRow($row2, 3, $plan, $job);
    expect($sim2->status)->toBe('ok')
        ->and($sim2->action)->toBe('atualizar');

    $imp2 = $importer->importRow($row2, 3, $plan, $job);
    expect($imp2->status)->toBe('ok')
        ->and($imp2->action)->toBe('atualizar');

    $locatario = Locatario::query()->where('documento', '12345678901')->first();
    expect($locatario)->not->toBeNull()
        ->and($locatario->nome)->toBe('Ana Maria')
        ->and((string) $locatario->status)->toBe('0');
});

it('retorna erro de simulação quando documento está ausente', function () {
    $importer = app(LocatariosCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'locatarios',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/locatarios.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'locatarios', mapping: []);
    $row = ['nome' => 'João', 'documento' => ''];

    $result = $importer->simulateRow($row, 2, $plan, $job);

    expect($result->status)->toBe('erro')
        ->and($result->errors)->toContain('documento ausente');
});

it('retorna erro de simulação para e-mail inválido, status inconsistente e linha vazia', function () {
    $importer = app(LocatariosCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'locatarios',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/locatarios.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'locatarios', mapping: []);

    $rowInvalido = [
        'nome' => 'Teste',
        'documento' => '12345678901',
        'email' => 'email-sem-formato',
        'status' => 'talvez',
    ];

    $invalidResult = $importer->simulateRow($rowInvalido, 2, $plan, $job);
    expect($invalidResult->status)->toBe('erro')
        ->and($invalidResult->errors)->toContain('e-mail inválido')
        ->and($invalidResult->errors)->toContain('status inconsistente');

    $emptyResult = $importer->simulateRow([], 3, $plan, $job);
    expect($emptyResult->status)->toBe('erro')
        ->and($emptyResult->errors)->toContain('linha vazia');
});
