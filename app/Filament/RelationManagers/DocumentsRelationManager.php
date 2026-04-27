<?php

namespace App\Filament\RelationManagers;

use App\Enums\DocumentType;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

    private function hasPerm(string $action): bool
    {
        $owner = $this->getOwnerRecord();
        if ($owner instanceof \App\Models\Acolhimento) {
            return \App\Filament\Resources\AcolhimentoResource::hasActionPermission($action);
        }
        if ($owner instanceof \App\Models\PessoaAcolhida) {
            return \App\Filament\Resources\PessoaAcolhidaResource::hasActionPermission($action);
        }
        return true;
    }

    public function isReadOnly(): bool
    {
        // Permite criar/excluir também na página de Visualização do registro pai
        return false;
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título')
                            ->maxLength(255)
                            ->placeholder('Ex.: Vistoria inicial, IPTU 2026, Comprovante de pagamento...'),
                        Select::make('document_type')
                            ->label('Tipo do documento')
                            ->options(DocumentType::options())
                            ->required(),
                        FileUpload::make('file')
                            ->label('Arquivo')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes(config('documents.accepted_mime_types'))
                            ->maxSize((int) config('documents.max_size_kb')),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->columns([
                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state) => DocumentType::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('titulo')
                    ->label('Título')
                    ->getStateUsing(fn (Document $record): string => $record->titulo ?: $record->original_name)
                    ->searchable(['titulo', 'original_name']),
                TextColumn::make('original_name')
                    ->label('Nome original')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_size')
                    ->label('Tamanho')
                    ->formatStateUsing(function ($state) {
                        $bytes = (int) $state;
                        if ($bytes <= 0) {
                            return '-';
                        }

                        $kb = $bytes / 1024;
                        if ($kb < 1024) {
                            return number_format($kb, 1, ',', '.') . ' KB';
                        }

                        $mb = $kb / 1024;
                        return number_format($mb, 2, ',', '.') . ' MB';
                    }),
                TextColumn::make('created_at')
                    ->label('Enviado em')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('uploadedByUser.name')
                    ->label('Enviado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Anexar')
                    ->visible(fn () => $this->hasPerm('Editar'))
                    ->using(function (array $data) {
                        /** @var TemporaryUploadedFile $file */
                        $file = $data['file'];

                        $owner = $this->getOwnerRecord();
                        $disk = config('documents.disk', config('filesystems.default', 'local'));
                        $directory = method_exists($owner, 'documentsStorageDirectory')
                            ? $owner->documentsStorageDirectory()
                            : 'documents/' . Str::kebab(class_basename($owner)) . '/' . $owner->getKey();

                        $storedName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                        $path = Storage::disk($disk)->putFileAs($directory, $file, $storedName);

                        /** @var Document $document */
                        $document = $owner->documents()->create([
                            'document_type' => $data['document_type'],
                            'titulo' => $data['titulo'] ?? null,
                            'original_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'uploaded_by' => auth()->id(),
                        ]);

                        return $document;
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Document $record) => route('documents.view', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record) => $record->isViewable() && $this->hasPerm('Visualizar')),
                Action::make('download')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record) => route('documents.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => $this->hasPerm('Visualizar')),
                DeleteAction::make()
                    ->visible(fn () => $this->hasPerm('Deletar')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => $this->hasPerm('Deletar')),
                ]),
            ]);
    }
}
