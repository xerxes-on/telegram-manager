<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Methods</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    @endif
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-200 to-blue">
<div class="w-full max-w-xl p-8 bg-purple-200 shadow-2xl rounded-2xl">
    <h2 class="text-2xl font-semibold text-center mb-8 text-gray-800">Choose Payment Method</h2>
    <div class="flex justify-center space-x-6">
        <form action="https://checkout.payme.uz" method="POST">
            @csrf
            <input type="hidden" name="merchant" value="{{env('PAYME_MERCHANT_ID')}}">
            <input type="hidden" name="amount" value="{{$amount}}">
            <input type="hidden" name="account[order_id]" value="{{$orderId}}">
            <input type="hidden" name="lang" value="uz"/>
            <button type="submit" class="transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300 p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl flex flex-col items-center">
                <img src="/payme_color.svg" alt="payze" class="h-10">
            </button>
        </form>

        <button @disabled(true) class="transition-all relative  duration-300 transform  p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl flex flex-col items-center">
            <img src="/payze.svg" alt="payze" class="h-10">
            <p class="text-red-600 text-xl bg-white rounded-sm px-2 font-bold absolute top-0 -rotate-45 -left-3">Soon</p>
        </button>
{{--        <button class="transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300 p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl flex flex-col items-center">--}}
{{--            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">--}}
{{--                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />--}}
{{--            </svg>--}}
{{--            <span class="text-gray-700 font-medium">Apple Pay</span>--}}
{{--        </button>--}}
{{--        <button class="transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300 p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl flex flex-col items-center">--}}
{{--            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">--}}
{{--                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />--}}
{{--            </svg>--}}
{{--            <span class="text-gray-700 font-medium">Google Pay</span>--}}
{{--        </button>--}}
    </div>
</div>
</body>
</html>
