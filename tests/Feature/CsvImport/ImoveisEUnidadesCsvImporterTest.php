<?php

use App\CsvImport\DTO\ImportPlan;
use App\CsvImport\Importers\ImoveisCsvImporter;
use App\CsvImport\Importers\UnidadesCsvImporter;
use App\Models\CsvImportJob;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\UnidadeImovel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('simula e importa imóveis com upsert por codigo_legado', function () {
    $locador = Locador::query()->create([
        'nome' => 'Locador 1',
        'documento' => '12345678901',
    ]);

    $importer = app(ImoveisCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'imoveis',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/imoveis.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'imoveis', mapping: [
        'locador_documento' => 'locador_documento',
        'codigo_legado' => 'codigo_legado',
        'titulo' => 'titulo',
    ]);

    $row = [
        'locador_documento' => '123.456.789-01',
        'codigo_legado' => 'IMV-001',
        'titulo' => 'Casa Centro',
        'endereco' => 'Rua A',
        'numero' => '10',
    ];

    $sim1 = $importer->simulateRow($row, 2, $plan, $job);
    expect($sim1->status)->toBe('ok')
        ->and($sim1->action)->toBe('criar');

    $imp1 = $importer->importRow($row, 2, $plan, $job);
    expect($imp1->status)->toBe('ok')
        ->and($imp1->action)->toBe('criar');

    $row['titulo'] = 'Casa Centro Atualizada';

    $sim2 = $importer->simulateRow($row, 3, $plan, $job);
    expect($sim2->status)->toBe('ok')
        ->and($sim2->action)->toBe('atualizar');

    $imp2 = $importer->importRow($row, 3, $plan, $job);
    expect($imp2->status)->toBe('ok')
        ->and($imp2->action)->toBe('atualizar');

    $imovel = Imovel::query()->where('codigo_legado', 'IMV-001')->first();
    expect($imovel)->not->toBeNull()
        ->and($imovel->locador_id)->toBe($locador->id)
        ->and($imovel->titulo)->toBe('Casa Centro Atualizada');
});

it('retorna erro quando imóvel referencia locador inexistente', function () {
    $importer = app(ImoveisCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'imoveis',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/imoveis.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'imoveis', mapping: []);
    $row = [
        'locador_documento' => '00000000000',
        'titulo' => 'Casa sem locador',
    ];

    $result = $importer->simulateRow($row, 2, $plan, $job);

    expect($result->status)->toBe('erro')
        ->and($result->errors)->toContain('locador não encontrado para o documento informado');
});

it('simula e importa unidades com upsert por codigo_legado e fallback por imovel + identificador', function () {
    $locador = Locador::query()->create([
        'nome' => 'Locador 2',
        'documento' => '98765432100',
    ]);

    $imovel = Imovel::query()->create([
        'locador_id' => $locador->id,
        'codigo_legado' => 'IMV-010',
        'titulo' => 'Edifício Central',
        'endereco' => 'Av. Principal',
        'numero' => '100',
    ]);

    $importer = app(UnidadesCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'unidades',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/unidades.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'unidades', mapping: [
        'imovel_codigo_legado' => 'imovel_codigo_legado',
        'codigo_legado' => 'codigo_legado',
        'identificador' => 'identificador',
    ]);

    $row = [
        'imovel_codigo_legado' => 'IMV-010',
        'codigo_legado' => 'UNI-001',
        'identificador' => 'Apto 101',
        'aluguel_base' => '1.234,56',
        'vaga_estacionamento' => 'sim',
    ];

    $sim1 = $importer->simulateRow($row, 2, $plan, $job);
    expect($sim1->status)->toBe('ok')
        ->and($sim1->action)->toBe('criar');

    $imp1 = $importer->importRow($row, 2, $plan, $job);
    expect($imp1->status)->toBe('ok')
        ->and($imp1->action)->toBe('criar');

    $row['aluguel_base'] = '2.000,00';

    $sim2 = $importer->simulateRow($row, 3, $plan, $job);
    expect($sim2->status)->toBe('ok')
        ->and($sim2->action)->toBe('atualizar');

    $imp2 = $importer->importRow($row, 3, $plan, $job);
    expect($imp2->status)->toBe('ok')
        ->and($imp2->action)->toBe('atualizar');

    $unidade = UnidadeImovel::query()->where('codigo_legado', 'UNI-001')->first();
    expect($unidade)->not->toBeNull()
        ->and($unidade->imovel_id)->toBe($imovel->id)
        ->and((string) $unidade->aluguel_base)->toBe('2000.00')
        ->and($unidade->vaga_estacionamento)->toBeTrue();
});

it('retorna erro quando unidade referencia imóvel inexistente', function () {
    $importer = app(UnidadesCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'unidades',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/unidades.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'unidades', mapping: []);
    $row = [
        'imovel_codigo_legado' => 'NAO-EXISTE',
        'identificador' => 'Apto 999',
    ];

    $result = $importer->simulateRow($row, 2, $plan, $job);

    expect($result->status)->toBe('erro')
        ->and($result->errors)->toContain('imóvel não encontrado para o código legado informado');
});
