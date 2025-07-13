@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md text-center">
        <h2 class="text-2xl font-semibold text-green-600">Thank you for your response!</h2>
        <p class="mt-2">Your submission to <strong>{{ $form->title }}</strong> has been recorded.</p>
        <a href="{{ route('forms.show', $form->id) }}" class="mt-4 inline-block text-blue-600 hover:underline">Back to Form</a>
    </div>
</div>
@endsection
