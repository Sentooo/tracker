@extends('layouts.app')

@section('content')
<div class="bg-[#fbfbfb] min-h-screen p-6">
  <div class="max-w-3xl mx-auto bg-white shadow-md rounded-md p-6">
    <h1 class="text-2xl font-bold text-[#05339f]">{{ $form->title }}</h1>
    <p class="text-sm text-gray-600 mb-6">{{ $form->description }}</p>

    <form action="{{ url('/forms/' . $form->id . '/response') }}" method="POST" id="responseForm">
      @csrf
      @foreach ($form->questions as $question)
        <div class="mb-4">
          <label class="block text-[#303337] font-medium">
            {{ $question->question_text }} @if($question->is_required)*@endif
          </label>

          @php
            $options = $question->options;
            if (is_string($options)) {
              $options = json_decode($options, true);
            }
            if (!is_array($options)) {
              $options = [];
            }
          @endphp

          @switch($question->question_type)
            @case('short_answer')
              <input
                type="text"
                name="answers[{{ $question->id }}]"
                class="w-full border rounded mt-1"
                @if($question->is_required) required @endif
              >
              @break

            @case('paragraph')
              <textarea
                name="answers[{{ $question->id }}]"
                class="w-full border rounded mt-1"
                @if($question->is_required) required @endif
              ></textarea>
              @break

            @case('multiple_choice')
              @foreach ($options as $option)
                <label class="block">
                  <input
                    type="radio"
                    name="answers[{{ $question->id }}]"
                    value="{{ $option }}"
                    @if($question->is_required) required @endif
                  >
                  {{ $option }}
                </label>
              @endforeach
              @break

            @case('checkbox')
              @foreach ($options as $option)
                <label class="block">
                  <input
                    type="checkbox"
                    name="answers[{{ $question->id }}][]"
                    value="{{ $option }}"
                    @if($question->is_required) required @endif
                  >
                  {{ $option }}
                </label>
              @endforeach
              @break

            @case('dropdown')
              <select
                name="answers[{{ $question->id }}]"
                class="w-full border rounded mt-1"
                @if($question->is_required) required @endif
              >
                @foreach ($options as $option)
                  <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
              </select>
              @break
          @endswitch

          {{-- Inline error message placeholder --}}
          <p class="mt-1 text-sm text-red-600 hidden error-message">This field is required.</p>
        </div>
      @endforeach
      <div class="flex justify-end">
        <button
          type="submit"
          id="submitBtn"
          class="bg-[#05339f] hover:bg-[#042c87] text-white px-6 py-2 rounded-md flex items-center justify-center gap-2"
        >
          <svg
            id="spinner"
            class="hidden animate-spin h-5 w-5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            ></path>
          </svg>
          <span id="submitText">Submit</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    const submitText = document.getElementById('submitText');
    const form = document.getElementById('responseForm');

    submitBtn.addEventListener('click', (e) => {
      e.preventDefault(); // prevent immediate submit

      // Hide all previous error messages
      const errorMessages = form.querySelectorAll('.error-message');
      errorMessages.forEach(msg => msg.classList.add('hidden'));

      let isValid = true;

      // Select all required inputs, textareas, and selects
      const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');

      for (const field of requiredFields) {
        const fieldWrapper = field.closest('div.mb-4');
        const errorMsg = fieldWrapper.querySelector('.error-message');

        if (field.type === 'checkbox' || field.type === 'radio') {
          // Check if any option is checked for this name
          const checked = form.querySelector(`input[name="${field.name}"]:checked`);
          if (!checked) {
            errorMsg.classList.remove('hidden');
            isValid = false;
          }
        } else {
          if (!field.value.trim()) {
            errorMsg.classList.remove('hidden');
            isValid = false;
          }
        }
      }

      if (!isValid) {
        // Scroll to the first error message for better UX
        const firstError = form.querySelector('.error-message:not(.hidden)');
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      // If valid, show spinner, disable button and submit
      spinner.classList.remove('hidden');
      submitText.textContent = 'Submitting...';
      submitBtn.disabled = true;
      submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

      form.submit();
    });
  });
</script>

@endsection
