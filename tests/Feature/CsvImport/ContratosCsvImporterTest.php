<?php

use App\CsvImport\DTO\ImportPlan;
use App\CsvImport\Importers\ContratosCsvImporter;
use App\Models\ContratoLocacao;
use App\Models\CsvImportJob;
use App\Models\Imovel;
use App\Models\Locador;
use App\Models\Locatario;
use App\Models\UnidadeImovel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('simula e importa contrato com criação conservadora quando não há código legado', function () {
    $importer = app(ContratosCsvImporter::class);

    $locador = Locador::query()->create([
        'nome' => 'Locador Teste',
        'documento' => '11111111111',
    ]);

    $imovel = Imovel::query()->create([
        'locador_id' => $locador->id,
        'titulo' => 'Casa Azul',
        'codigo_legado' => 'IMV-10',
    ]);

    $unidade = UnidadeImovel::query()->create([
        'imovel_id' => $imovel->id,
        'codigo_legado' => 'UNI-10',
        'identificador' => '101',
    ]);

    $locatario = Locatario::query()->create([
        'nome' => 'Locatário Teste',
        'documento' => '12345678901',
    ]);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'contratos',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/contratos.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'contratos', mapping: []);

    $row = [
        'locatario_documento' => '123.456.789-01',
        'unidade_codigo_legado' => 'UNI-10',
        'data_inicio' => '15/04/2026',
        'valor_aluguel' => '1.800,50',
        'valor_condominio' => '350,25',
        'dia_vencimento' => '5',
        'responsavel_condominio' => 'locatario',
        'ativo' => 'sim',
    ];

    $sim = $importer->simulateRow($row, 2, $plan, $job);
    expect($sim->status)->toBe('ok')
        ->and($sim->action)->toBe('criar')
        ->and($sim->warnings)->toContain('sem codigo_legado: operação será apenas criação conservadora');

    $imp = $importer->importRow($row, 2, $plan, $job);
    expect($imp->status)->toBe('ok')
        ->and($imp->action)->toBe('criar');

    $contrato = ContratoLocacao::query()->first();
    expect($contrato)->not->toBeNull()
        ->and($contrato->locatario_id)->toBe($locatario->id)
        ->and($contrato->unidade_imovel_id)->toBe($unidade->id)
        ->and((string) $contrato->valor_aluguel)->toBe('1800.50')
        ->and((string) $contrato->valor_condominio)->toBe('350.25')
        ->and($contrato->ativo)->toBeTrue();
});

it('retorna erros de simulação para relacionamento e campos inválidos', function () {
    $importer = app(ContratosCsvImporter::class);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'contratos',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/contratos.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'contratos', mapping: []);

    $row = [
        'locatario_documento' => '12345678901',
        'unidade_codigo_legado' => 'UNI-NAO-EXISTE',
        'data_inicio' => '31/31/2026',
        'valor_aluguel' => 'abc',
        'dia_vencimento' => '45',
    ];

    $result = $importer->simulateRow($row, 2, $plan, $job);

    expect($result->status)->toBe('erro')
        ->and($result->errors)->toContain('locatário não encontrado para o documento informado')
        ->and($result->errors)->toContain('unidade não encontrada para o código legado informado')
        ->and($result->errors)->toContain('data_inicio inválida')
        ->and($result->errors)->toContain('valor_aluguel inválido')
        ->and($result->errors)->toContain('dia_vencimento ausente ou inválido');
});

it('bloqueia duplicidade potencial sem chave segura', function () {
    $importer = app(ContratosCsvImporter::class);

    $locador = Locador::query()->create([
        'nome' => 'Locador Duplicidade',
        'documento' => '22222222222',
    ]);

    $imovel = Imovel::query()->create([
        'locador_id' => $locador->id,
        'titulo' => 'Prédio',
        'codigo_legado' => 'IMV-20',
    ]);

    $unidade = UnidadeImovel::query()->create([
        'imovel_id' => $imovel->id,
        'codigo_legado' => 'UNI-20',
        'identificador' => '202',
    ]);

    $locatario = Locatario::query()->create([
        'nome' => 'Locatário Duplicidade',
        'documento' => '98765432100',
    ]);

    ContratoLocacao::query()->create([
        'locatario_id' => $locatario->id,
        'unidade_imovel_id' => $unidade->id,
        'data_inicio' => '2026-04-01',
        'valor_aluguel' => '1200.00',
        'dia_vencimento' => 10,
        'responsavel_condominio' => 'LOCATARIO',
        'responsavel_iptu' => 'LOCADOR',
        'ativo' => true,
    ]);

    $job = CsvImportJob::query()->create([
        'tipo_importacao' => 'contratos',
        'arquivo_disk' => 'local',
        'arquivo_path' => 'csv-imports/contratos.csv',
        'delimiter' => ';',
        'has_header' => true,
        'status' => 'uploaded',
    ]);

    $plan = new ImportPlan(type: 'contratos', mapping: []);

    $row = [
        'locatario_documento' => '987.654.321-00',
        'unidade_codigo_legado' => 'UNI-20',
        'data_inicio' => '01/04/2026',
        'valor_aluguel' => '1.300,00',
        'dia_vencimento' => '10',
    ];

    $result = $importer->simulateRow($row, 2, $plan, $job);

    expect($result->status)->toBe('erro')
        ->and($result->errors)->toContain('duplicidade potencial sem chave segura (contrato já existente para locatário + unidade + data_inicio)');
});
