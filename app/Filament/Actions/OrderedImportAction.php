<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader as CsvReader;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Actions\View\ActionsIconAlias;

class OrderedImportAction extends ImportAction
{
    protected array|Closure|null $optionsFormSchema = null;

    public function optionsFormSchema(array|Closure|null $schema): static
    {
        $this->optionsFormSchema = $schema;

        return $this;
    }

    public function getOptionsFormSchema(): ?array
    {
        return $this->evaluate($this->optionsFormSchema);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema(fn(OrderedImportAction $action): array => array_merge(
            // 1. Options First
            $action->getOptionsFormSchema() ?? $action->getImporter()::getOptionsFormComponents(),

            // 2. File Upload & Mapping
            [
                FileUpload::make('file')
                    ->label(__('filament-actions::import.modal.form.file.label'))
                    ->placeholder(__('filament-actions::import.modal.form.file.placeholder'))
                    ->acceptedFileTypes(['text/csv', 'text/x-csv', 'application/csv', 'application/x-csv', 'text/comma-separated-values', 'text/x-comma-separated-values', 'text/plain', 'application/vnd.ms-excel'])
                    ->rules($action->getFileValidationRules())
                    ->afterStateUpdated(function (FileUpload $component, Component $livewire, Set $set, ?TemporaryUploadedFile $state) use ($action): void {
                        if (!$state instanceof TemporaryUploadedFile) {
                            return;
                        }

                        try {
                            $livewire->validateOnly($component->getStatePath());
                        } catch (ValidationException $exception) {
                            $component->state([]);

                            throw $exception;
                        }

                        $csvStream = $this->getUploadedFileStream($state);

                        if (!$csvStream) {
                            return;
                        }

                        $csvReader = CsvReader::createFromStream($csvStream);

                        if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                            $csvReader->setDelimiter($csvDelimiter);
                        }

                        $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                        $csvColumns = $csvReader->getHeader();

                        $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                        $lowercaseCsvColumnKeys = array_combine(
                            $lowercaseCsvColumnValues,
                            $csvColumns,
                        );

                        $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                            $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                                Arr::first(
                                    array_intersect(
                                        $lowercaseCsvColumnValues,
                                        $column->getGuesses(),
                                    ),
                                )
                            ] ?? null;

                            return $carry;
                        }, []));
                    })
                    ->storeFiles(false)
                    ->visibility('private')
                    ->required()
                    ->hiddenLabel(),
                Fieldset::make(__('filament-actions::import.modal.form.columns.label'))
                    ->columns(1)
                    ->inlineLabel()
                    ->schema(function (Get $get) use ($action): array {
                        $csvFile = $get('file');

                        if (!$csvFile instanceof TemporaryUploadedFile) {
                            return [];
                        }

                        $csvStream = $this->getUploadedFileStream($csvFile);

                        if (!$csvStream) {
                            return [];
                        }

                        $csvReader = CsvReader::createFromStream($csvStream);

                        if (filled($csvDelimiter = $this->getCsvDelimiter($csvReader))) {
                            $csvReader->setDelimiter($csvDelimiter);
                        }

                        $csvReader->setHeaderOffset($action->getHeaderOffset() ?? 0);

                        $csvColumns = $csvReader->getHeader();
                        $csvColumnOptions = array_combine($csvColumns, $csvColumns);

                        return array_map(
                            fn(ImportColumn $column): Select => $column->getSelect()->options($csvColumnOptions),
                            $action->getImporter()::getColumns(),
                        );
                    })
                    ->statePath('columnMap')
                    ->visible(fn(Get $get): bool => $get('file') instanceof TemporaryUploadedFile),
            ]
        ));
    }
}
