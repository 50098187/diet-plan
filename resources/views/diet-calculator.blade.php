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

    <style>
        .day-tab.active {
            color: rgb(5 150 105);
            border-bottom-color: rgb(5 150 105);
        }
    </style>
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
            <div class="grid gap-12 lg:grid-cols-5 lg:gap-16 lg:items-start">
                <!-- Hero Section -->
                <div class="space-y-6 lg:col-span-2">
                    <div class="inline-block rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-medium text-emerald-700">
                        Smart Nutrition Planning
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        Optimize Your Diet, <br>
                        <span class="text-emerald-600">Minimize Your Cost</span>
                    </h1>

                    <p class="text-lg text-gray-600">
                        Get a personalized, budget-friendly meal plan based on your fitness goals. This advanced optimization model creates the perfect nutrition strategy tailored to your body and objectives.
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
                <div class="rounded-2xl bg-white p-8 shadow-xl lg:col-span-3">
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
                    <div id="results-state" class="hidden space-y-6">
                        <!-- Metabolic Summary -->
                        <div class="rounded-lg bg-gradient-to-br from-emerald-50 to-teal-50 p-6 border border-emerald-200">
                            <h3 class="mb-4 text-lg font-semibold text-emerald-900">Your Metabolic Profile</h3>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-lg bg-white p-4 shadow-sm">
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">BMR</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-bmr">-</p>
                                    <p class="text-xs text-gray-500 mt-1">Basal Metabolic Rate</p>
                                </div>
                                <div class="rounded-lg bg-white p-4 shadow-sm">
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">TDEE</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-tdee">-</p>
                                    <p class="text-xs text-gray-500 mt-1">Total Daily Energy</p>
                                </div>
                                <div class="rounded-lg bg-white p-4 shadow-sm">
                                    <p class="text-xs text-gray-600 uppercase tracking-wide">Avg Daily</p>
                                    <p class="text-2xl font-bold text-gray-900" id="result-avg-calories">-</p>
                                    <p class="text-xs text-gray-500 mt-1">Average Calories</p>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Shopping List -->
                        <div class="rounded-lg bg-white p-6 shadow-lg border border-gray-200">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">🛒 Weekly Shopping List</h3>
                                <div class="rounded-lg bg-emerald-100 px-4 py-2">
                                    <p class="text-sm font-medium text-emerald-900">Total: <span id="weekly-cost" class="text-lg font-bold">-</span></p>
                                </div>
                            </div>
                            <p class="mb-4 text-sm text-gray-600">Buy these items once for the entire week:</p>
                            <div id="weekly-shopping-list" class="space-y-2">
                                <!-- Weekly shopping items will be populated here -->
                            </div>
                        </div>

                        <!-- 7-Day Meal Plans -->
                        <div class="rounded-lg bg-white p-6 shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">📋 7-Day Meal Plans</h3>
                            <p class="mb-4 text-sm text-gray-600">Each day has a unique optimized meal plan with variety:</p>

                            <!-- Day Tabs -->
                            <div class="flex overflow-x-auto border-b border-gray-200 mb-4">
                                <button id="tab-btn-1" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition active" onclick="switchDay(1)">
                                    Monday
                                </button>
                                <button id="tab-btn-2" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(2)">
                                    Tuesday
                                </button>
                                <button id="tab-btn-3" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(3)">
                                    Wednesday
                                </button>
                                <button id="tab-btn-4" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(4)">
                                    Thursday
                                </button>
                                <button id="tab-btn-5" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(5)">
                                    Friday
                                </button>
                                <button id="tab-btn-6" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(6)">
                                    Saturday
                                </button>
                                <button id="tab-btn-7" class="day-tab px-4 py-2 text-sm font-medium text-gray-700 hover:text-emerald-600 border-b-2 border-transparent hover:border-emerald-600 transition" onclick="switchDay(7)">
                                    Sunday
                                </button>
                            </div>

                            <!-- Day Content Container -->
                            <div id="days-container">
                                <!-- Daily meal plans will be populated here -->
                            </div>
                        </div>

                        <button onclick="resetForm()" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-emerald-700">
                            Calculate New Plan
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

                        <!-- Diet Type -->
                        <div>
                            <label for="diet_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Diet Type
                            </label>
                            <select
                                id="diet_type"
                                name="diet_type"
                                required
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="" disabled>Select your diet preference</option>
                                <option value="normal" selected>Normal (includes all foods)</option>
                                <option value="vegetarian">Vegetarian (no meat, includes dairy & eggs)</option>
                                <option value="vegan">Vegan (plant-based only)</option>
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
                    console.log('Polling response:', data);

                    if (data.success) {
                        if (data.status === 'completed' && data.data) {
                            // Job completed, show results
                            console.log('Job completed! Stopping polling and showing results...');
                            console.log('Full completed data:', data.data);
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

        // Global variable to track selected day
        let selectedDay = 0;

        // Show weekly results
        function showResults(data) {
            console.log('Showing weekly results:', data);

            // Hide loading state
            document.getElementById('loading-state').classList.add('hidden');

            // Update metabolic profile
            document.getElementById('result-bmr').textContent = Math.round(data.bmr || 0) + ' kcal';
            document.getElementById('result-tdee').textContent = Math.round(data.tdee || 0) + ' kcal';
            document.getElementById('result-avg-calories').textContent = Math.round(data.avg_target_calories || 0) + ' kcal';

            // Update weekly cost
            document.getElementById('weekly-cost').textContent = 'R' + (data.optimal_weekly_cost || 0).toFixed(2);

            // Display weekly shopping list
            const shoppingList = document.getElementById('weekly-shopping-list');
            shoppingList.innerHTML = '';

            // Function will be redefined below with complete weekly meal plan logic
            // This is just a placeholder that will be overridden
        }

        // (Old showResults code removed - see complete implementation below)
        function resetForm() {
            stopPolling();
            document.getElementById('diet-form').classList.remove('hidden');
            document.getElementById('loading-state').classList.add('hidden');
            document.getElementById('results-state').classList.add('hidden');
            document.getElementById('diet-form').reset();
        }

        // Helper function to format serving sizes
        function formatServingSize(food) {
            let totalText = '';
            if (food.serving_size) {
                const cleanServingSize = food.serving_size.replace(/\\\//g, '/');
                const match = cleanServingSize.match(/^(.+?)\s*\((\d+)\s*g\)$/);

                if (match) {
                    const friendlyUnit = match[1].trim();
                    const gramsPerServing = parseFloat(match[2]);
                    const totalGrams = (food.servings * gramsPerServing).toFixed(0);

                    const unitMatch = friendlyUnit.match(/^(\d+\/\d+|\d+\.\d+|\d+)\s+(.+)$/);
                    if (unitMatch) {
                        const baseAmount = unitMatch[1].includes('/') ?
                            (parseFloat(unitMatch[1].split('/')[0]) / parseFloat(unitMatch[1].split('/')[1])) :
                            parseFloat(unitMatch[1]);
                        const unit = unitMatch[2];
                        const totalAmount = food.servings * baseAmount;
                        const displayAmount = totalAmount % 1 === 0 ? Math.round(totalAmount).toString() : totalAmount.toFixed(1);

                        let pluralUnit = unit;
                        if (parseFloat(displayAmount) > 1) {
                            const words = unit.split(' ');
                            if (words[0] && !words[0].endsWith('s')) {
                                words[0] = words[0] + 's';
                            }
                            pluralUnit = words.join(' ');
                        }
                        totalText = `${displayAmount} ${pluralUnit} (${totalGrams}g)`;
                    } else {
                        const servingsRounded = food.servings % 1 === 0 ? Math.round(food.servings).toString() : food.servings.toFixed(1);
                        totalText = `${servingsRounded} ${friendlyUnit}${food.servings > 1 ? 's' : ''} (${totalGrams}g)`;
                    }
                } else {
                    const gramMatch = cleanServingSize.match(/(\d+)\s*g/);
                    if (gramMatch) {
                        const gramsPerServing = parseFloat(gramMatch[1]);
                        const totalGrams = (food.servings * gramsPerServing).toFixed(0);
                        totalText = `${totalGrams}g`;
                    } else {
                        totalText = `${food.servings} servings`;
                    }
                }
            } else {
                totalText = `${food.servings} servings`;
            }
            return totalText;
        }

        // Helper function to get store badge HTML
        function getStoreBadge(source) {
            const storeIcon = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>`;

            let storeName, storeColor;
            if (source === 'woolworths') {
                storeName = 'Woolworths';
                storeColor = 'bg-green-100 text-green-700';
            } else if (source === 'checkers') {
                storeName = 'Checkers';
                storeColor = 'bg-blue-100 text-blue-700';
            } else if (source === 'crowd-sourced') {
                storeName = 'Verified';
                storeColor = 'bg-purple-100 text-purple-700';
            } else {
                storeName = 'Various stores';
                storeColor = 'bg-gray-100 text-gray-600';
            }

            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium ${storeColor}">
                ${storeIcon} ${storeName}
            </span>`;
        }

        // Render weekly shopping list with smart quantities
        function renderShoppingList(data) {
            console.log('renderShoppingList called with data:', data);
            const shoppingList = document.getElementById('weekly-shopping-list');
            shoppingList.innerHTML = '';

            if (!data.weekly_shopping_list || data.weekly_shopping_list.length === 0) {
                console.warn('No shopping list items found!');
                shoppingList.innerHTML = '<p class="text-sm text-gray-500">No shopping items found.</p>';
                return;
            }

            console.log('Processing', data.weekly_shopping_list.length, 'shopping items...');
            data.weekly_shopping_list.forEach(item => {
                const cleanName = item.name.replace(/\s*\([^)]*\)/g, '').trim();
                // Use smart_quantity if available, otherwise format servings
                const quantityText = item.smart_quantity || formatServingSize(item);

                const itemEl = document.createElement('div');
                itemEl.className = 'flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition';
                itemEl.innerHTML = `
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">${cleanName}</p>
                        <p class="text-sm text-gray-600">${quantityText}</p>
                        ${getStoreBadge(item.source)}
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-emerald-600">R${item.weekly_cost.toFixed(2)}</p>
                    </div>
                `;
                shoppingList.appendChild(itemEl);
            });
        }

        // Store daily plans data globally for tab switching
        let allDailyPlans = [];
        let currentlySelectedDay = 1;

        // Switch between days
        function switchDay(dayNumber) {
            console.log('Switching to day:', dayNumber);
            currentlySelectedDay = dayNumber;

            // Update tab styles
            document.querySelectorAll('.day-tab').forEach((tab, idx) => {
                if (idx + 1 === dayNumber) {
                    tab.classList.add('text-emerald-600', 'border-emerald-600');
                    tab.classList.remove('text-gray-700', 'border-transparent');
                } else {
                    tab.classList.remove('text-emerald-600', 'border-emerald-600');
                    tab.classList.add('text-gray-700', 'border-transparent');
                }
            });

            // Show/hide day content
            document.querySelectorAll('[id^="day-content-"]').forEach((content, idx) => {
                if (idx + 1 === dayNumber) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });
        }

        // Render all 7 daily meal plans
        function renderSevenDayPlans(data) {
            console.log('renderSevenDayPlans called with data:', data);
            const container = document.getElementById('days-container');
            container.innerHTML = '';

            if (!data.daily_plans || data.daily_plans.length === 0) {
                console.warn('No daily plans found!');
                container.innerHTML = '<p class="text-sm text-gray-500">No daily plans found.</p>';
                return;
            }

            console.log('Rendering', data.daily_plans.length, 'daily plans...');
            allDailyPlans = data.daily_plans;

            // Create a container for each day
            data.daily_plans.forEach((dayPlan, idx) => {
                const dayNumber = idx + 1;
                const dayContainer = document.createElement('div');
                dayContainer.id = `day-content-${dayNumber}`;
                dayContainer.className = dayNumber === 1 ? 'space-y-4' : 'space-y-4 hidden';

                // Day header with workout/rest indicator
                const isWorkoutDay = data.workout_schedule && data.workout_schedule[idx];
                const dayHeader = document.createElement('div');
                dayHeader.className = `rounded-lg p-3 ${isWorkoutDay ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-200'}`;
                dayHeader.innerHTML = `
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-bold text-gray-900">${dayPlan.day_name}</h4>
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${isWorkoutDay ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'}">
                            ${isWorkoutDay ? '💪 Workout Day' : '😌 Rest Day'}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Target: ${Math.round(data.daily_calories_targets[idx])} kcal</p>
                `;
                dayContainer.appendChild(dayHeader);

                // Day macros summary
                const summary = document.createElement('div');
                summary.className = 'grid gap-3 sm:grid-cols-3 md:grid-cols-6';
                summary.innerHTML = `
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Cost</p>
                        <p class="text-xl font-bold text-emerald-600">R${dayPlan.cost.toFixed(2)}</p>
                    </div>
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Calories</p>
                        <p class="text-xl font-bold text-gray-900">${Math.round(dayPlan.totals.calories)}</p>
                    </div>
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Protein</p>
                        <p class="text-xl font-bold text-blue-600">${Math.round(dayPlan.totals.protein)}g</p>
                    </div>
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Carbs</p>
                        <p class="text-xl font-bold text-orange-600">${Math.round(dayPlan.totals.carbs)}g</p>
                    </div>
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Fat</p>
                        <p class="text-xl font-bold text-yellow-600">${Math.round(dayPlan.totals.fat)}g</p>
                    </div>
                    <div class="rounded-lg bg-white border border-gray-200 p-3">
                        <p class="text-xs text-gray-600">Fiber</p>
                        <p class="text-xl font-bold text-green-600">${Math.round(dayPlan.totals.fiber)}g</p>
                    </div>
                `;
                dayContainer.appendChild(summary);

                // Foods list
                const foodsContainer = document.createElement('div');
                foodsContainer.className = 'space-y-2';

                dayPlan.foods.forEach(food => {
                    const cleanName = food.name.replace(/\s*\([^)]*\)/g, '').trim();
                    const totalText = formatServingSize(food);

                    const foodEl = document.createElement('div');
                    foodEl.className = 'flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition';
                    foodEl.innerHTML = `
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${cleanName}</p>
                            <p class="text-sm text-gray-600">${totalText}</p>
                            ${getStoreBadge(food.source)}
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-700">R${food.cost.toFixed(2)}</p>
                        </div>
                    `;
                    foodsContainer.appendChild(foodEl);
                });

                dayContainer.appendChild(foodsContainer);
                container.appendChild(dayContainer);
            });
        }

        // Updated showResults to handle weekly data
        const originalShowResults = showResults;
        showResults = function(data) {
            console.log('=== showResults called ===');
            console.log('Full data object:', data);
            console.log('Data keys:', Object.keys(data));

            try {
                // Hide loading state
                console.log('Hiding loading state...');
                document.getElementById('loading-state').classList.add('hidden');

                // Update metabolic profile
                console.log('Updating metabolic profile...');
                console.log('BMR:', data.bmr, 'TDEE:', data.tdee, 'Avg calories:', data.avg_target_calories);
                document.getElementById('result-bmr').textContent = Math.round(data.bmr || 0) + ' kcal';
                document.getElementById('result-tdee').textContent = Math.round(data.tdee || 0) + ' kcal';
                document.getElementById('result-avg-calories').textContent = Math.round(data.avg_target_calories || data.target_calories || 0) + ' kcal';

                // Update weekly cost
                console.log('Updating weekly cost:', data.optimal_weekly_cost);
                document.getElementById('weekly-cost').textContent = 'R' + (data.optimal_weekly_cost || 0).toFixed(2);

                // Render weekly shopping list
                console.log('Rendering shopping list...');
                console.log('Shopping list items:', data.weekly_shopping_list ? data.weekly_shopping_list.length : 'undefined');
                renderShoppingList(data);

                // Render 7-day meal plans
                console.log('Rendering 7-day plans...');
                console.log('Daily plans:', data.daily_plans ? data.daily_plans.length : 'undefined');
                renderSevenDayPlans(data);

                // Show results
                console.log('Showing results state...');
                document.getElementById('results-state').classList.remove('hidden');
                console.log('=== showResults complete ===');
            } catch (error) {
                console.error('ERROR in showResults:', error);
                console.error('Error stack:', error.stack);
                alert('Error displaying results: ' + error.message);
            }
        };

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopPolling();
        });
    </script>
</body>
</html>
