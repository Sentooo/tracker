@extends('layouts.app')

@section('content')
<div class="min-h-screen flex justify-center items-center bg-gray-100">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4 text-center text-blue-800">Enter Access Code</h2>

    @if(session('error'))
      <div class="text-red-600 text-sm mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('forms.authorize') }}">
      @csrf
      <input type="password" name="access_code" placeholder="Access code"
             class="w-full border rounded p-2 mb-4" required>
      <button type="submit"
              class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
        Access Form Builder
      </button>
    </form>
  </div>
</div>
@endsection
