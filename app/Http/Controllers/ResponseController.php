<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Response;
use Illuminate\Http\Request;
use App\Services\GoogleSheetsService;

class ResponseController extends Controller
{
    public function store(Request $request, $id)
    {
        $form = Form::with('questions')->findOrFail($id);

        $answers = [];

        foreach ($form->questions as $question) {
            $answers[$question->id] = $request->input('answers')[$question->id] ?? null;
        }

        // Save the response to the database
        Response::create([
            'form_id' => $form->id,
            'answers' => json_encode($answers),
        ]);

        // Append the response to the Google Sheet
        $spreadsheetId = env('GOOGLE_SHEET_ID'); // Make sure this is set in your .env
        app(GoogleSheetsService::class)->appendFormResponse($spreadsheetId, $form, $answers);

        return redirect()->route('forms.thankyou', $form->id)->with('success', 'Your response has been recorded.');
    }

    public function thankyou($id)
    {
        $form = Form::findOrFail($id);
        return view('forms.thankyou', compact('form'));
    }
}
