<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\GoogleSheetsService;

class FormController extends Controller
{
    protected $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    public function create()
    {
        $existingForm = Form::with('questions')->first();

        if ($existingForm && $existingForm->questions->isNotEmpty()) {
            return redirect()->route('forms.edit', $existingForm->id)
                ->with('info', 'A form already exists. Redirected to edit.');
        }

        return view('forms.create');
    }

    public function edit($id)
    {
        $form = Form::with('questions')->find($id);

        if (!$form) {
            return redirect()->route('forms.create')->with('error', 'No form found. Please create one.');
        }

        return view('forms.create', [
            'editMode' => true,
            'form' => $form,
        ]);
    }

    public function store(Request $request)
    {
        if (Form::exists()) {
            return redirect()->route('forms.index')
                ->with('error', 'A form already exists. You cannot create another one.');
        }

        $validated = $this->validateForm($request);

        DB::beginTransaction();

        try {
            $form = Form::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            $this->saveQuestions($form, $validated['questions']);

            $form->load('questions');

            $spreadsheetId = env('GOOGLE_SHEET_ID');
            $this->googleSheetsService->saveFormStructure($spreadsheetId, $form);

            DB::commit();

            return redirect()->route('forms.show', $form->id)
                ->with('success', 'Form saved and published to Google Sheets!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Form store failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to save form: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Form $form)
    {
        $validated = $this->validateForm($request);

        DB::beginTransaction();

        try {
            $form->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            $form->questions()->delete();
            $this->saveQuestions($form, $validated['questions']);

            $form->load('questions');

            $spreadsheetId = env('GOOGLE_SHEET_ID');
            $this->googleSheetsService->saveFormStructure($spreadsheetId, $form);

            DB::commit();

            return redirect()->route('forms.edit', $form->id)
                ->with('success', 'Form updated and synced with Google Sheets!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Form update failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to update form: ' . $e->getMessage());
        }
    }

    public function show(Form $form)
    {
        $form->load('questions');
        return view('forms.show', compact('form'));
    }

    protected function validateForm(Request $request)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|string|in:short_answer,paragraph,multiple_choice,checkbox,dropdown',
            'questions.*.is_required' => 'nullable|in:on,1,true,false,0',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'string|nullable',
        ]);
    }

    protected function saveQuestions(Form $form, array $questions)
    {
        foreach ($questions as $question) {
            $createdQuestion = $form->questions()->create([
                'question_text' => $question['question_text'],
                'question_type' => $question['question_type'],
                'is_required' => isset($question['is_required']) && in_array($question['is_required'], ['on', '1', 1, true, 'true']),
            ]);

            $options = [];
            if (in_array($question['question_type'], ['multiple_choice', 'checkbox', 'dropdown'])) {
                $options = array_filter(array_map('trim', $question['options'] ?? []), fn($opt) => $opt !== '');
            }

            $createdQuestion->update(['options' => $options]);
        }
    }
}
