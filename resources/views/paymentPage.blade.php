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
        <button type="button" id="paymeButton"
                class="transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300 p-4 bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl flex flex-col items-center">
            <img src="/payme_color.svg" alt="payze" class="h-10">
        </button>


        <!-- Payze button remains unchanged -->

        <!-- Card Details Modal -->
        <div id="cardModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Karta ma'lumotlaringizni </h3>
                    <div class="mt-2 px-4 py-3">
                        <p class="text-sm text-gray-500">Данные вашей карты не передаются данному сервису и сохраняются
                            на безопасной территории сервиса Payme</p>
                        <form id="cardForm" class="mt-4">
                            <div class="mb-4">
                                <label for="cardNumber" class="block text-sm font-medium text-gray-700 text-left">НОМЕР
                                    КАРТЫ</label>
                                <input type="text" id="cardNumber" name="cardNumber"
                                       class="mt-1 p-2 w-full border rounded-md" placeholder="0000 0000 0000 0000"
                                       required>
                            </div>
                            <div class="flex space-x-4 mb-4">
                                <div class="w-1/2">
                                    <label for="expiryMonth" class="block text-sm font-medium text-gray-700 text-left">МЕСЯЦ</label>
                                    <select id="expiryMonth" name="expiryMonth"
                                            class="mt-1 p-2 w-full border rounded-md" required>
                                        <option value="" disabled selected>MM</option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option
                                                value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="w-1/2">
                                    <label for="expiryYear" class="block text-sm font-medium text-gray-700 text-left">ГОД</label>
                                    <select id="expiryYear" name="expiryYear" class="mt-1 p-2 w-full border rounded-md"
                                            required>
                                        <option value="" disabled selected>YYYY</option>
                                        @for ($i = date('Y'); $i <= date('Y') + 10; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="cvv" class="block text-sm font-medium text-gray-700 text-left">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="mt-1 p-2 w-full border rounded-md"
                                       placeholder="123" required>
                            </div>
                            <div class="items-center px-4 py-3">
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Продолжить
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('cardModal');
        const paymeButton = document.getElementById('paymeButton');
        const cardForm = document.getElementById('cardForm');
        const paymeForm = document.getElementById('paymeForm');

        // Show modal when Payme button is clicked
        paymeButton.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        // Handle card form submission
        cardForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Here you would normally process payment details
            // For demonstration, submit the original Payme form
            paymeForm.submit();
            modal.classList.add('hidden');
        });

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        }
    });
</script>
</body>
</html>
