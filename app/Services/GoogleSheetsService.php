<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use App\Models\Form;

class GoogleSheetsService
{
    protected $service;
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google-service-account.json'));
        $this->client->addScope(Sheets::SPREADSHEETS);
        $this->service = new Sheets($this->client);
    }

    public function createSpreadsheet($title)
    {
        $spreadsheet = new Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);

        $spreadsheet = $this->service->spreadsheets->create($spreadsheet);
        return $spreadsheet->spreadsheetId;
    }

    public function saveFormStructure($spreadsheetId, Form $form)
    {
        $form->load('questions');

        $headers = [
            ['Question ID', 'Question Text', 'Question Type', 'Required', 'Options']
        ];

        $rows = $form->questions->map(function ($question) {
            $options = $question->options;
            if (is_string($options)) {
                $options = json_decode($options, true);
            }
            $optionsText = is_array($options) ? implode(', ', $options) : '';

            return [
                $question->id,
                $question->question_text,
                $question->question_type,
                $question->is_required ? 'Yes' : 'No',
                $optionsText
            ];
        })->toArray();

        $values = array_merge($headers, $rows);

        // Make sure we're writing to a separate "Structure" sheet
        $sheetName = 'Structure';

        $this->ensureSheetExists($spreadsheetId, $sheetName);

        $body = new \Google\Service\Sheets\ValueRange([
            'values' => $values
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->service->spreadsheets_values->update(
            $spreadsheetId,
            $sheetName . '!A1',
            $body,
            $params
        );
    }


   public function appendFormResponse($spreadsheetId, Form $form, $answers)
    {
        $form->load('questions');
        $sheetName = 'Responses';

        $this->ensureSheetExists($spreadsheetId, $sheetName);

        // Sync headers & clear responses if needed
        $this->syncResponsesSheetHeaders($spreadsheetId, $form, $sheetName);

        // Prepare response row (ensure order matches questionTexts)
        $responseRow = [];
        foreach ($form->questions as $question) {
            $responseRow[] = $answers[$question->id] ?? '';
        }

        // Append response
        $responseBody = new \Google\Service\Sheets\ValueRange([
            'values' => [$responseRow]
        ]);

        $this->service->spreadsheets_values->append(
            $spreadsheetId,
            $sheetName,
            $responseBody,
            ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
        );
    }

    /**
     * Synchronize the "Responses" sheet headers with the current form questions.
     * If headers differ (order or questions changed), clear sheet and write new headers.
     */
    private function syncResponsesSheetHeaders($spreadsheetId, Form $form, string $sheetName)
    {
        $questionTexts = $form->questions->pluck('question_text')->toArray();

        // Get existing headers from the sheet
        $existing = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName . '!A1:1');
        $existingHeaders = $existing->getValues()[0] ?? [];

        // Compare existing headers with current question texts
        if ($existingHeaders !== $questionTexts) {
            // Clear the entire sheet first (including old responses)
            $clearRequest = new \Google\Service\Sheets\ClearValuesRequest();
            $this->service->spreadsheets_values->clear($spreadsheetId, $sheetName, $clearRequest);

            // Write new headers
            $headerBody = new \Google\Service\Sheets\ValueRange([
                'values' => [$questionTexts]
            ]);
            $this->service->spreadsheets_values->update(
                $spreadsheetId,
                $sheetName . '!A1',
                $headerBody,
                ['valueInputOption' => 'RAW']
            );
        }
    }


    private function ensureSheetExists($spreadsheetId, $sheetName)
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        $sheetExists = collect($spreadsheet->getSheets())->contains(function ($sheet) use ($sheetName) {
            return $sheet->getProperties()->getTitle() === $sheetName;
        });

        if (!$sheetExists) {
            $addSheetRequest = new \Google\Service\Sheets\Request([
                'addSheet' => [
                    'properties' => [
                        'title' => $sheetName
                    ]
                ]
            ]);

            $batchUpdateRequest = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [$addSheetRequest]
            ]);

            $this->service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }
    }


    public function service()
    {
        return $this->service;
    }
}
