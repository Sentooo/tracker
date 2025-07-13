<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LNUGoogleForms') }}</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins&display=swap">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-[#fbfbfb] text-[#303337]">

    {{-- Header --}}
    <header class="bg-[#05339f] text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="text-xl font-bold">Leyte Normal University - Form</h1>
            
            @if(session('form_builder_unlocked'))
            <div class="flex items-center space-x-4">
                <a href="{{ route('forms.edit', $form->id ?? 0) }}" 
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm">
                    Edit
                </a>

                <form action="{{ route('forms.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm">
                        Logout
                    </button>
                </form>
            </div>
            @endif
        </div>
</header>



    {{-- Main Content --}}
    <main class="mt-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-[#303337] text-[#fbfbfb] mt-12 py-4 text-center text-sm">
        © {{ date('Y') }} Leyte Normal University. All rights reserved.
    </footer>

</body>
</html>
