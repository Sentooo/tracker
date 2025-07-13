@extends('layouts.app')

@section('content')
<div class="bg-[#fbfbfb] min-h-screen p-6">
  <div class="max-w-4xl mx-auto bg-white shadow-md rounded-md">

    <form 
      action="{{ (isset($editMode) && !empty($form)) ? route('forms.update', $form->id) : route('forms.store') }}" 
      method="POST"
      x-data="formBuilder({
        initialQuestions: {{ isset($form) ? json_encode($form->questions) : '[]' }},
        submitting: false
      })"
      @submit.prevent="submitForm"
    >
      @csrf
      @if(isset($editMode))
        @method('PUT')
      @endif

      {{-- Form Title & Description --}}
      <div class="bg-[#05339f] text-white p-4 rounded-t-md">
        <input 
          name="title" 
          x-model="title"
          class="w-full bg-transparent text-2xl font-semibold focus:outline-none" 
          placeholder="Untitled form" 
          required
        >
        <textarea 
          name="description" 
          x-model="description"
          class="w-full bg-transparent text-sm mt-1 focus:outline-none" 
          placeholder="Form description"
        ></textarea>
      </div>

      {{-- Questions --}}
      <div class="p-6 space-y-6">
        <template x-for="(question, index) in questions" :key="index">
          <div class="border-l-4 border-[#c7102e] pl-4 bg-gray-50 p-4 rounded relative">
            <button type="button" @click="removeQuestion(index)" class="absolute top-2 right-2 text-gray-500 hover:text-red-500">✕</button>

            <input 
              type="text" 
              x-model="question.question_text" 
              :name="`questions[${index}][question_text]`" 
              class="w-full border-b border-gray-300 focus:outline-none bg-transparent" 
              placeholder="Question text" 
              required
            >

            <select 
              x-model="question.question_type" 
              :name="`questions[${index}][question_type]`" 
              class="mt-3 w-full border border-gray-300 rounded p-2"
            >
              <option value="short_answer">Short Answer</option>
              <option value="paragraph">Paragraph</option>
              <option value="multiple_choice">Multiple Choice</option>
              <option value="checkbox">Checkbox</option>
              <option value="dropdown">Dropdown</option>
              <option value="number">Number</option>
              <option value="date">Date</option>
              <option value="email">Email</option>
            </select>

            <div x-show="needsOptions(question.question_type)" class="mt-3 space-y-2" x-transition>
              <template x-for="(option, optionIndex) in question.options" :key="optionIndex">
                <div class="flex items-center">
                  <input 
                    type="text" 
                    x-model="question.options[optionIndex]" 
                    :name="`questions[${index}][options][${optionIndex}]`" 
                    class="flex-1 border border-gray-300 rounded p-2" 
                    placeholder="Option" 
                    required
                  >
                  <button type="button" @click="removeOption(question, optionIndex)" class="ml-2 text-red-500 hover:text-red-700">✕</button>
                </div>
              </template>
              <button type="button" @click="addOption(question)" class="text-sm text-[#05339f] hover:underline">+ Add Option</button>
            </div>

            <label class="mt-3 block">
              <input 
                type="checkbox" 
                x-model="question.is_required" 
                :name="`questions[${index}][is_required]`" 
                class="accent-[#05339f]"
              > Required
            </label>

            <template x-if="!needsOptions(question.question_type)">
              <input type="hidden" :name="`questions[${index}][options]`" value="">
            </template>
          </div>
        </template>

        <button 
          type="button" 
          @click="addQuestion()" 
          class="flex items-center justify-center w-full border-2 border-dashed border-gray-300 p-4 rounded hover:bg-gray-50"
        >
          <span class="text-[#05339f] font-medium">+ Add Question</span>
        </button>
      </div>

      {{-- Submit --}}
      <div class="flex bg-gray-100 p-4 rounded-b-md text-right justify-end">
        <button
          type="submit"
          :disabled="submitting"
          class="bg-[#05339f] hover:bg-[#042c87] text-white px-6 py-2 rounded-md flex items-center justify-center gap-2"
        >
          <svg
            x-show="submitting"
            class="animate-spin h-5 w-5 text-white"
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
          <span x-text="submitting ? '{{ isset($editMode) ? 'Updating...' : 'Submitting...' }}' : '{{ isset($editMode) ? 'Update Form' : 'Publish Form' }}'"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function formBuilder({ initialQuestions = [], submitting = false }) {
    return {
      title: '{{ old('title', $form->title ?? '') }}',
      description: '{{ old('description', $form->description ?? '') }}',
      questions: [],
      submitting: submitting,

      init() {
        this.questions = initialQuestions.length
          ? initialQuestions.map(q => ({
              question_text: q.question_text || '',
              question_type: q.question_type || 'short_answer',
              is_required: q.is_required ? true : false,
              options: Array.isArray(q.options) ? q.options : []
            }))
          : [{
              question_text: '',
              question_type: 'short_answer',
              is_required: false,
              options: []
            }];
      },
      addQuestion() {
        this.questions.push({
          question_text: '',
          question_type: 'short_answer',
          is_required: false,
          options: []
        });
      },
      removeQuestion(index) {
        this.questions.splice(index, 1);
      },
      addOption(question) {
        question.options.push('');
      },
      removeOption(question, index) {
        question.options.splice(index, 1);
      },
      needsOptions(type) {
        return ['multiple_choice', 'checkbox', 'dropdown'].includes(type);
      },
      submitForm() {
        if (this.submitting) return;
        this.submitting = true;
        // Wait a tiny bit to let spinner show
        setTimeout(() => {
          this.$el.submit();
        }, 100);
      }
    };
  }
</script>
@endsection
