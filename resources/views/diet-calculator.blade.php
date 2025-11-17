<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimize Your Diet - DietPlan</title>

    <!-- Vite CSS -->
    @vite('resources/css/app.css')

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50">
        <!-- Navigation -->
        <header class="w-full px-6 py-4 lg:px-8">
            <nav class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="text-2xl font-bold text-emerald-700">
                    DietPlan
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-20">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-16 lg:items-center">
                <!-- Hero Section -->
                <div class="space-y-6">
                    <div class="inline-block rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-medium text-emerald-700">
                        Smart Nutrition Planning
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Optimize Your Diet,
                        <span class="text-emerald-600">Minimize Your Cost</span>
                    </h1>

                    <p class="text-lg text-gray-600">
                        Get a personalized, budget-friendly meal plan based on your fitness goals. Our advanced optimization model creates the perfect nutrition strategy tailored to your body and objectives.
                    </p>

                    <!-- Features List -->
                    <div class="space-y-3 pt-4">
                        <div class="flex items-start gap-3">
                            <svg class="mt-1 h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Personalized macronutrient targets</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="mt-1 h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Cost-optimized meal suggestions</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="mt-1 h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-gray-700">Science-backed recommendations</span>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="rounded-2xl bg-white p-8 shadow-xl">
                    <h2 class="mb-6 text-2xl font-bold text-gray-900" id="form-title">
                        Get Your Custom Plan
                    </h2>

                    <!-- Loading State -->
                    <div id="loading-state" class="hidden space-y-4">
                        <div class="flex items-center justify-center py-8">
                            <div class="h-16 w-16 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></div>
                        </div>
                        <p class="text-center text-gray-600" id="loading-message">Calculating your optimal diet plan...</p>
                    </div>

                    <!-- Results State -->
                    <div id="results-state" class="hidden space-y-4">
                        <div class="rounded-lg bg-emerald-50 p-6">
                            <h3 class="mb-4 text-lg font-semibold text-emerald-900">Your Personalized Diet Plan</h3>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">Daily Calories</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-calories">-</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">BMR</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-bmr">-</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">Protein</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-protein">-</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">Carbs</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-carbs">-</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">Fats</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-fats">-</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-sm text-gray-600">TDEE</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-tdee">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- Shopping Summary -->
                        <div id="shopping-summary" class="hidden rounded-lg bg-blue-50 border border-blue-200 p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-blue-900 mb-1">Where to Shop</p>
                                    <p class="text-sm text-blue-800" id="shopping-stores-text">Visit the stores indicated below to get these prices.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Store Information -->
                        <div id="store-info" class="hidden rounded-lg bg-blue-50 border border-blue-200 p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-blue-900 mb-1">About Prices</p>
                                    <p class="text-sm text-blue-800">Each item shows a store badge indicating where you can purchase it at the listed price.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Food Servings List -->
                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900">Your Optimal Shopping List</h3>
                            <p class="mb-4 text-sm text-gray-600">Buy these items daily to meet your nutrition goals at minimum cost:</p>
                            <div id="foods-list" class="space-y-3">
                                <!-- Foods will be populated here by JavaScript -->
                            </div>
                            <div class="mt-4 rounded-lg bg-emerald-50 p-4">
                                <p class="text-sm font-medium text-emerald-900">Total Daily Cost: <span id="result-cost" class="text-lg font-bold">-</span></p>
                            </div>
                        </div>

                        <button onclick="resetForm()" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-base font-semibold text-white transition hover:bg-emerald-700">
                            Calculate Again
                        </button>
                    </div>

                    <form id="diet-form" action="/diet-plan/calculate" method="POST" class="space-y-5">
                        @csrf

                        <!-- Weight -->
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                                Weight (kg)
                            </label>
                            <input
                                id="weight"
                                name="weight"
                                type="number"
                                step="0.1"
                                required
                                value="75"
                                placeholder="e.g., 70"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <!-- Height -->
                        <div>
                            <label for="height" class="block text-sm font-medium text-gray-700 mb-2">
                                Height (cm)
                            </label>
                            <input
                                id="height"
                                name="height"
                                type="number"
                                required
                                value="175"
                                placeholder="e.g., 175"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <!-- Age -->
                        <div>
                            <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                                Age (years)
                            </label>
                            <input
                                id="age"
                                name="age"
                                type="number"
                                required
                                value="28"
                                placeholder="e.g., 30"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            />
                        </div>

                        <!-- Gender -->
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                Gender
                            </label>
                            <select
                                id="gender"
                                name="gender"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="" disabled>Select your gender</option>
                                <option value="male" selected>Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <!-- Activity Factor -->
                        <div>
                            <label for="activity_factor" class="block text-sm font-medium text-gray-700 mb-3">
                                Activity Factor
                            </label>

                            <!-- Activity Factor Guide Table (Above Selection) -->
                            <div class="mb-3 overflow-hidden rounded-lg border border-emerald-100 bg-gradient-to-br from-emerald-50 to-teal-50 shadow-sm">
                                <table class="w-full text-sm">
                                    <thead class="bg-emerald-100/50">
                                        <tr>
                                            <th class="px-3 py-2.5 text-left font-semibold text-emerald-900">Level</th>
                                            <th class="px-3 py-2.5 text-left font-semibold text-emerald-900">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-emerald-100">
                                        <tr class="hover:bg-emerald-100/30 transition">
                                            <td class="px-3 py-2.5 font-medium text-emerald-900">Sedentary</td>
                                            <td class="px-3 py-2.5 text-emerald-800">Little to no exercise, desk job, minimal daily movement</td>
                                        </tr>
                                        <tr class="hover:bg-emerald-100/30 transition">
                                            <td class="px-3 py-2.5 font-medium text-emerald-900">Lightly Active</td>
                                            <td class="px-3 py-2.5 text-emerald-800">Light exercise 1-3 days/week, or moderate daily activity</td>
                                        </tr>
                                        <tr class="hover:bg-emerald-100/30 transition">
                                            <td class="px-3 py-2.5 font-medium text-emerald-900">Moderately Active</td>
                                            <td class="px-3 py-2.5 text-emerald-800">Moderate exercise 3-5 days/week, active lifestyle</td>
                                        </tr>
                                        <tr class="hover:bg-emerald-100/30 transition">
                                            <td class="px-3 py-2.5 font-medium text-emerald-900">Very Active</td>
                                            <td class="px-3 py-2.5 text-emerald-800">Hard exercise 6-7 days/week, or physical job</td>
                                        </tr>
                                        <tr class="hover:bg-emerald-100/30 transition">
                                            <td class="px-3 py-2.5 font-medium text-emerald-900">Extremely Active</td>
                                            <td class="px-3 py-2.5 text-emerald-800">Very hard daily exercise/sports & physical job, or training twice per day</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <select
                                id="activity_factor"
                                name="activity_factor"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="" disabled>Select your activity level</option>
                                <option value="sedentary">Sedentary</option>
                                <option value="lightly_active">Lightly Active</option>
                                <option value="moderately_active" selected>Moderately Active</option>
                                <option value="very_active">Very Active</option>
                                <option value="extremely_active">Extremely Active</option>
                            </select>
                        </div>

                        <!-- Goal -->
                        <div>
                            <label for="goal" class="block text-sm font-medium text-gray-700 mb-2">
                                Goal
                            </label>
                            <select
                                id="goal"
                                name="goal"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="" disabled>Select your goal</option>
                                <option value="lose_fat">Lose Fat</option>
                                <option value="maintain_weight" selected>Maintain Weight</option>
                                <option value="gain_muscle">Gain Muscle</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-emerald-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        >
                            Generate My Diet Plan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Additional Info Section -->
            <div class="mt-20 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl bg-white p-6 shadow-md">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Precise Calculations</h3>
                    <p class="text-sm text-gray-600">
                        We use scientifically proven formulas to calculate your daily caloric needs and macronutrient ratios.
                    </p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-md">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Budget Optimized</h3>
                    <p class="text-sm text-gray-600">
                        Our algorithm finds the most cost-effective foods that meet your nutritional requirements.
                    </p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-md">
                    <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Goal Focused</h3>
                    <p class="text-sm text-gray-600">
                        Whether you want to lose fat, maintain, or gain muscle, we adjust your plan accordingly.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-20 border-t border-gray-200 py-8">
            <div class="mx-auto max-w-7xl px-6 text-center text-sm text-gray-600 lg:px-8">
                Built with smart optimization algorithms to help you achieve your fitness goals affordably.
            </div>
        </footer>
    </div>

    <script>
        let pollingInterval = null;
        let currentJobId = null;

        // Form submission handler
        document.getElementById('diet-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const weight = parseFloat(document.getElementById('weight').value);
            const height = parseFloat(document.getElementById('height').value);
            const age = parseFloat(document.getElementById('age').value);

            // Basic validation
            if (weight < 30 || weight > 300) {
                alert('Please enter a valid weight between 30 and 300 kg');
                return;
            }

            if (height < 100 || height > 250) {
                alert('Please enter a valid height between 100 and 250 cm');
                return;
            }

            if (age < 15 || age > 100) {
                alert('Please enter a valid age between 15 and 100 years');
                return;
            }

            // Get form data
            const formData = new FormData(this);

            // Show loading state
            document.getElementById('diet-form').classList.add('hidden');
            document.getElementById('loading-state').classList.remove('hidden');

            try {
                // Submit the form
                const response = await fetch('/diet-plan/calculate', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    currentJobId = data.job_id;
                    // Start polling for job completion
                    startPolling(data.job_id);
                } else {
                    alert('Error: ' + data.message);
                    resetForm();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
                resetForm();
            }
        });

        // Start polling for job status
        function startPolling(jobId) {
            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch('/diet-plan/status?job_id=' + encodeURIComponent(jobId), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (data.status === 'completed' && data.data) {
                            // Job completed, show results
                            stopPolling();
                            showResults(data.data);
                        } else if (data.status === 'failed' || data.status === 'error') {
                            // Job failed
                            stopPolling();
                            const errorMsg = data.error || 'Unknown error';
                            alert('Calculation failed: ' + errorMsg + '\n\nPlease try again or contact support if the problem persists.');
                            console.error('Job failed with status:', data.status, 'Error:', errorMsg, 'Full data:', data);
                            resetForm();
                        }
                        // If status is 'running', 'pending', or 'queued', keep polling
                        console.log('Job status:', data.status);
                    } else {
                        console.error('Polling error:', data.message);
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 4000); // Poll every 4 seconds
        }

        // Stop polling
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        // Show results
        function showResults(data) {
            console.log('Showing results:', data);

            // Hide loading state
            document.getElementById('loading-state').classList.add('hidden');

            // Update results (handle both old and new data formats)
            document.getElementById('result-calories').textContent = Math.round(data.target_calories || 0) + ' kcal';
            document.getElementById('result-bmr').textContent = Math.round(data.bmr || 0) + ' kcal';

            // Try both field names for macros (totals.protein vs protein_grams)
            const protein = data.totals?.protein || data.protein_grams || 0;
            const carbs = data.totals?.carbs || data.carb_grams || 0;
            const fats = data.totals?.fat || data.fat_grams || 0;

            document.getElementById('result-protein').textContent = Math.round(protein) + 'g';
            document.getElementById('result-carbs').textContent = Math.round(carbs) + 'g';
            document.getElementById('result-fats').textContent = Math.round(fats) + 'g';
            document.getElementById('result-tdee').textContent = Math.round(data.tdee || 0) + ' kcal';

            // Display optimal cost
            if (data.optimal_cost) {
                document.getElementById('result-cost').textContent = 'R' + data.optimal_cost.toFixed(2);
            }

            // Display food servings
            const foodsList = document.getElementById('foods-list');
            foodsList.innerHTML = ''; // Clear existing content

            if (data.foods && Array.isArray(data.foods) && data.foods.length > 0) {
                // Collect unique stores and check for manual prices
                const stores = new Set();
                let hasManualPrices = false;
                let hasRealStores = false;

                data.foods.forEach(food => {
                    if (food.source) {
                        stores.add(food.source);
                        if (food.source === 'manual' || food.source === 'unknown') {
                            hasManualPrices = true;
                        } else if (food.source === 'woolworths' || food.source === 'checkers') {
                            hasRealStores = true;
                        }
                    }
                });

                // Show shopping summary only if we have real store data
                const summaryEl = document.getElementById('shopping-summary');
                const storesTextEl = document.getElementById('shopping-stores-text');

                if (hasRealStores) {
                    const storeNames = Array.from(stores)
                        .filter(s => s === 'woolworths' || s === 'checkers' || s === 'crowd-sourced')
                        .map(s => {
                            if (s === 'woolworths') return 'Woolworths';
                            if (s === 'checkers') return 'Checkers';
                            if (s === 'crowd-sourced') return 'various stores (crowd-sourced prices)';
                            return s.charAt(0).toUpperCase() + s.slice(1);
                        });

                    if (storeNames.length === 1) {
                        storesTextEl.textContent = `Shop at ${storeNames[0]} to get these prices.`;
                    } else if (storeNames.length === 2) {
                        storesTextEl.textContent = `Shop at ${storeNames[0]} and ${storeNames[1]} to get the best prices.`;
                    } else if (storeNames.length > 2) {
                        const lastStore = storeNames.pop();
                        storesTextEl.textContent = `Shop at ${storeNames.join(', ')}, and ${lastStore} to get the best prices.`;
                    }
                    summaryEl.classList.remove('hidden');
                }

                // Always show store info
                const storeInfoEl = document.getElementById('store-info');
                storeInfoEl.classList.remove('hidden');

                data.foods.forEach(food => {
                    // Extract grams from serving size string (with null check)
                    let totalGramsText = '';
                    if (food.serving_size) {
                        const gramMatch = food.serving_size.match(/(\d+)\s*g/);
                        if (gramMatch) {
                            const gramsPerServing = parseFloat(gramMatch[1]);
                            const total = (food.servings * gramsPerServing).toFixed(0);
                            totalGramsText = `${total}g`;
                        } else {
                            // If no grams found, show servings
                            totalGramsText = `${food.servings} servings`;
                        }
                    } else {
                        // If serving_size is missing, just show servings
                        totalGramsText = `${food.servings} servings`;
                    }

                    // Remove text in parentheses from food name
                    const cleanName = food.name.replace(/\s*\([^)]*\)/g, '').trim();

                    // Format store badge - ALWAYS show source
                    let storeHTML = '';
                    let storeName = '';
                    let storeColor = '';
                    let storeIcon = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>`;

                    if (food.source === 'woolworths') {
                        storeName = 'Woolworths';
                        storeColor = 'bg-green-100 text-green-700';
                    } else if (food.source === 'checkers') {
                        storeName = 'Checkers';
                        storeColor = 'bg-blue-100 text-blue-700';
                    } else if (food.source === 'crowd-sourced') {
                        storeName = 'Verified by users';
                        storeColor = 'bg-purple-100 text-purple-700';
                    } else if (food.source === 'manual' || food.source === 'unknown' || !food.source) {
                        storeName = 'Available at various stores';
                        storeColor = 'bg-gray-100 text-gray-600';
                    } else {
                        // Any other source, capitalize and show it
                        storeName = food.source.charAt(0).toUpperCase() + food.source.slice(1);
                        storeColor = 'bg-gray-100 text-gray-700';
                    }

                    storeHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${storeColor} mt-1">
                        ${storeIcon}
                        ${storeName}
                    </span>`;

                    const foodItem = document.createElement('div');
                    foodItem.className = 'flex items-center justify-between rounded-lg border border-gray-200 p-4 hover:bg-gray-50 transition';
                    foodItem.innerHTML = `
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">${cleanName}</p>
                            <p class="text-sm text-gray-600">${totalGramsText}</p>
                            ${storeHTML}
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-emerald-600">R${food.cost.toFixed(2)}</p>
                        </div>
                    `;
                    foodsList.appendChild(foodItem);
                });
            } else {
                foodsList.innerHTML = '<p class="text-sm text-gray-500">No food recommendations available.</p>';
            }

            // Show results
            document.getElementById('results-state').classList.remove('hidden');
        }

        // Reset form
        function resetForm() {
            stopPolling();
            document.getElementById('diet-form').classList.remove('hidden');
            document.getElementById('loading-state').classList.add('hidden');
            document.getElementById('results-state').classList.add('hidden');
            document.getElementById('shopping-summary').classList.add('hidden');
            document.getElementById('store-info').classList.add('hidden');
            document.getElementById('diet-form').reset();
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopPolling();
        });
    </script>
</body>
</html>
