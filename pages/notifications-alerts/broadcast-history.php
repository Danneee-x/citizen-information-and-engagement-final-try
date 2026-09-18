<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        height: 6px;
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Breadcrumb Header -->
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
        <span>Notifications & Alerts</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Broadcast History</span>
    </div>

    <!-- Top Action Row -->
    <div class="flex items-center justify-end">
        <button type="button" onclick="exportReport()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
            <i class="fa-solid fa-download text-slate-400"></i>
            <span>Export Report</span>
        </button>
    </div>

    <!-- KPI Summary Cards Row (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Broadcasts -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Broadcasts</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">128</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>12% vs last month</span>
                </p>
            </div>
        </div>

        <!-- Card 2: Total Recipients Reached -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Recipients Reached</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">48,562</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>18% vs last month</span>
                </p>
            </div>
        </div>

        <!-- Card 3: Successful Deliveries -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Successful Deliveries</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">95.2%</h3>
                <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <span>2.4% vs last month</span>
                </p>
            </div>
        </div>

        <!-- Card 4: Scheduled Broadcasts -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Scheduled Broadcasts</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">6</h3>
                <a href="#" class="text-[11px] font-bold text-[#0f53d1] hover:underline inline-block mt-1">View upcoming &rarr;</a>
            </div>
        </div>

    </div>

    <!-- Main Content Layout Grid (Table + Alert Details Side Panel) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Table Container (8 Cols when drawer open, 12 Cols when closed) -->
        <div id="tableContainer" class="lg:col-span-12 space-y-4 transition-all duration-300">
            
            <!-- Filters & Search Bar Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                    
                    <!-- Search Input (4 Cols) -->
                    <div class="lg:col-span-4 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="searchInput" oninput="filterTable()" placeholder="Search alerts by title or content..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Category Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="categoryFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Categories</option>
                            <option value="General Announcement">General Announcement</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Health Advisory">Health Advisory</option>
                            <option value="Event">Event</option>
                            <option value="Curfew">Curfew / Ordinance</option>
                        </select>
                    </div>

                    <!-- Channel Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="channelFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Channels</option>
                            <option value="SMS">SMS</option>
                            <option value="In-App">In-App / Push</option>
                            <option value="Email">Email</option>
                        </select>
                    </div>

                    <!-- Status Select (2 Cols) -->
                    <div class="lg:col-span-2">
                        <select id="statusFilter" onchange="filterTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2 px-2.5 text-xs outline-none cursor-pointer">
                            <option value="">All Statuses</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Partial">Partial</option>
                            <option value="Scheduled">Scheduled</option>
                        </select>
                    </div>

                    <!-- Filter Button (2 Cols) -->
                    <div class="lg:col-span-2">
                        <button type="button" onclick="filterTable()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-sliders text-slate-400"></i>
                            <span>Filter</span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Broadcast History Data Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Alert Title</th>
                                <th class="py-3.5 px-3">Category</th>
                                <th class="py-3.5 px-3">Sender</th>
                                <th class="py-3.5 px-3">Date / Time Sent</th>
                                <th class="py-3.5 px-3 text-center">Recipients</th>
                                <th class="py-3.5 px-3 text-center">Channels</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="broadcastTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            
                            <!-- Row 1 -->
                            <tr onclick="selectBroadcastRow(this, 1)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="1" data-category="General Announcement" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-bullhorn"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Dengue Prevention Week Advisory</p>
                                            <p class="text-[10px] text-slate-400 truncate">Mag-ingat sa dengue! Upang maiwasan...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100 whitespace-nowrap">General Announcement</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Juan Dela Cruz</p>
                                    <p class="text-[10px] text-slate-400">(Barangay Admin)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 8, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:42 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,450</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 2 -->
                            <tr onclick="selectBroadcastRow(this, 2)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="2" data-category="Emergency" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Heavy Rainfall Warning</p>
                                            <p class="text-[10px] text-slate-400 truncate">Nakaabang po tayo ng malakas na ulan...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 font-bold text-[10px] border border-rose-100 whitespace-nowrap">Emergency</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Maria Santos</p>
                                    <p class="text-[10px] text-slate-400">(Health Officer)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 7, 2025</p>
                                    <p class="text-[10px] text-slate-400">5:30 PM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,615</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 3 -->
                            <tr onclick="selectBroadcastRow(this, 3)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="3" data-category="Health Advisory" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-heart-pulse"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Free Medical Check-up</p>
                                            <p class="text-[10px] text-slate-400 truncate">Libreng medical check-up para sa lahat...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 font-bold text-[10px] border border-amber-100 whitespace-nowrap">Health Advisory</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Pedro Reyes</p>
                                    <p class="text-[10px] text-slate-400">(Barangay Staff)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 7, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:00 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">1,980</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 4 -->
                            <tr onclick="selectBroadcastRow(this, 4)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="4" data-category="Event" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Barangay Fiesta 2025</p>
                                            <p class="text-[10px] text-slate-400 truncate">Inaanyayahan po ang lahat ng residente...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-600 font-bold text-[10px] border border-purple-100 whitespace-nowrap">Event</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Juan Dela Cruz</p>
                                    <p class="text-[10px] text-slate-400">(Barangay Admin)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 6, 2025</p>
                                    <p class="text-[10px] text-slate-400">3:15 PM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,350</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 5 -->
                            <tr onclick="selectBroadcastRow(this, 5)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="5" data-category="Curfew" data-status="Partial">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-shield-halved"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Curfew Ordinance Reminder</p>
                                            <p class="text-[10px] text-slate-400 truncate">Paalaala po sa lahat ng kabataan...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-100 whitespace-nowrap">Curfew Notice</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Maria Santos</p>
                                    <p class="text-[10px] text-slate-400">(Health Officer)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 6, 2025</p>
                                    <p class="text-[10px] text-slate-400">8:00 PM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,100</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 font-bold text-[10px] border border-amber-200">Partial</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 6 -->
                            <tr onclick="selectBroadcastRow(this, 6)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="6" data-category="General Announcement" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-bullhorn"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Garbage Collection Advisory</p>
                                            <p class="text-[10px] text-slate-400 truncate">Paalaala po sa schedule ng basura...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100 whitespace-nowrap">General Announcement</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Pedro Reyes</p>
                                    <p class="text-[10px] text-slate-400">(Barangay Staff)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 5, 2025</p>
                                    <p class="text-[10px] text-slate-400">7:30 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,480</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 7 -->
                            <tr onclick="selectBroadcastRow(this, 7)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="7" data-category="Health Advisory" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-heart-pulse"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Heat Index Advisory</p>
                                            <p class="text-[10px] text-slate-400 truncate">Mataas ang heat index ngayong araw...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 font-bold text-[10px] border border-amber-100 whitespace-nowrap">Health Advisory</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Maria Santos</p>
                                    <p class="text-[10px] text-slate-400">(Health Officer)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 4, 2025</p>
                                    <p class="text-[10px] text-slate-400">11:00 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">2,620</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-comment-dots text-emerald-500" title="SMS"></i>
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 8 -->
                            <tr onclick="selectBroadcastRow(this, 8)" class="broadcast-row hover:bg-slate-50 transition cursor-pointer" data-id="8" data-category="Event" data-status="Scheduled">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 text-sm">
                                            <i class="fa-solid fa-calendar-star"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900 truncate">Youth Sports Week</p>
                                            <p class="text-[10px] text-slate-400 truncate">Registration is now open for all...</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-600 font-bold text-[10px] border border-purple-100 whitespace-nowrap">Event</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-800">Juan Dela Cruz</p>
                                    <p class="text-[10px] text-slate-400">(Barangay Admin)</p>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-bold text-slate-800">Jun 3, 2025</p>
                                    <p class="text-[10px] text-slate-400">2:00 PM</p>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">1,750</td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-xs">
                                        <i class="fa-solid fa-bell text-[#0f53d1]" title="In-App"></i>
                                        <i class="fa-solid fa-envelope text-purple-600" title="Email"></i>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-200">Scheduled</span>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1 text-slate-400">
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="w-7 h-7 rounded-lg hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                <div class="px-4 py-3 bg-slate-50/50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
                    <div>
                        <span>Showing 1 to 8 of 128 results</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer text-xs"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <button class="w-7 h-7 rounded-lg bg-[#0f53d1] text-white font-bold flex items-center justify-center shadow-xs text-xs">1</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">2</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">3</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">4</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">5</button>
                        <span class="px-1 text-slate-400 font-bold">...</span>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">16</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer text-xs"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px]">Rows per page</span>
                        <select class="bg-white border border-slate-200 rounded-lg text-xs font-bold px-2 py-1 outline-none cursor-pointer">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Side Panel: Alert Details (4 Cols) -->
        <div id="alertDetailsDrawer" class="hidden lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 sticky top-6">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-black text-slate-900 tracking-tight">Alert Details</h2>
                <button onclick="closeDetailsDrawer()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Title & Category Badge Header -->
            <div class="space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div id="drawerCategoryIcon" class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h3 id="drawerTitle" class="text-sm font-black text-slate-900 leading-snug">Dengue Prevention Week Advisory</h3>
                            <p id="drawerCategoryText" class="text-[11px] font-bold text-[#0f53d1]">General Announcement</p>
                        </div>
                    </div>
                    <span id="drawerStatusBadge" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200 shrink-0">Delivered</span>
                </div>
                <p id="drawerTimestamp" class="text-[10px] text-slate-400 font-semibold flex items-center gap-1.5 pl-1">
                    <i class="fa-regular fa-calendar"></i>
                    <span>Jun 8, 2025 &bull; 9:42 AM</span>
                </p>
            </div>

            <!-- Message Content Box -->
            <div class="space-y-1.5">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">MESSAGE CONTENT</h4>
                <div id="drawerMessageContent" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 font-medium leading-relaxed space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                    <p>Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang:</p>
                    <ul class="list-disc pl-4 space-y-1">
                        <li>Tanggalin ang pangatlong tubig sa paligid</li>
                        <li>Takpan ang imbakan ng tubig</li>
                        <li>Linisin ang paligid ng inyong tahanan</li>
                    </ul>
                    <p>Kung may sintomas tulad ng lagnat, sakit ng ulo, pananakit ng katawan, o pantal, kumonsulta agad sa pinakamalapit na health center.</p>
                    <p>Maraming salamat sa inyong pakikiisa!</p>
                </div>
            </div>

            <!-- Recipients Summary -->
            <div class="space-y-1.5">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">RECIPIENTS SUMMARY</h4>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-xs">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="drawerRecipientsTarget" class="text-xs font-bold text-slate-800">All Residents</span>
                        <span id="drawerRecipientsBadge" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">2,450 recipients</span>
                    </div>
                </div>
            </div>

            <!-- Delivery Channels -->
            <div class="space-y-1.5">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DELIVERY CHANNELS</h4>
                <div id="drawerChannelsList" class="grid grid-cols-3 gap-2">
                    
                    <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                        <div class="text-emerald-500 text-sm"><i class="fa-solid fa-comment-dots"></i></div>
                        <p class="text-[10px] font-bold text-slate-800">SMS</p>
                        <p class="text-[9px] text-emerald-600 font-bold">Delivered</p>
                    </div>

                    <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                        <div class="text-[#0f53d1] text-sm"><i class="fa-solid fa-bell"></i></div>
                        <p class="text-[10px] font-bold text-slate-800">In-App / Push</p>
                        <p class="text-[9px] text-emerald-600 font-bold">Delivered</p>
                    </div>

                    <div class="p-2 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-0.5">
                        <div class="text-purple-600 text-sm"><i class="fa-solid fa-envelope"></i></div>
                        <p class="text-[10px] font-bold text-slate-800">Email</p>
                        <p class="text-[9px] text-emerald-600 font-bold">Delivered</p>
                    </div>

                </div>
            </div>

            <!-- Delivery Summary Donut Chart -->
            <div class="space-y-2 border-t border-slate-100 pt-3">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DELIVERY SUMMARY</h4>
                
                <div class="flex items-center gap-4">
                    <!-- SVG Donut Chart -->
                    <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-slate-300" stroke-dasharray="100, 100" stroke-dashoffset="0" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-amber-400" stroke-dasharray="98.5, 100" stroke-dashoffset="0" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-emerald-500" stroke-dasharray="95.1, 100" stroke-dashoffset="0" stroke-width="4.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span id="donutTotalRecipients" class="text-xs font-black text-slate-900 leading-none">2,450</span>
                            <span class="text-[8px] font-bold text-slate-400 leading-none mt-0.5">Total</span>
                        </div>
                    </div>

                    <!-- Donut Legend -->
                    <div class="space-y-1.5 text-xs min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-600 font-medium">Delivered</span>
                            </div>
                            <span id="donutDeliveredCount" class="font-bold text-slate-900">2,330 (95.1%)</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span class="text-slate-600 font-medium">Failed</span>
                            </div>
                            <span id="donutFailedCount" class="font-bold text-slate-900">83 (3.4%)</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span class="text-slate-600 font-medium">Pending</span>
                            </div>
                            <span id="donutPendingCount" class="font-bold text-slate-900">37 (1.5%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata Details -->
            <div class="border-t border-slate-100 pt-3 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Created By</span>
                    <p id="drawerCreatedBy" class="font-bold text-slate-800 text-[11px] truncate">Juan Dela Cruz <span class="text-slate-400 font-normal">(Barangay Admin)</span></p>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block uppercase">Alert ID</span>
                    <p id="drawerAlertId" class="font-bold text-slate-800 text-[11px] flex items-center gap-1 truncate">
                        <span>ALERT-2025-0608-0942</span>
                        <i class="fa-regular fa-copy text-slate-400 hover:text-slate-700 cursor-pointer" onclick="copyAlertId()"></i>
                    </p>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="resendAlert()" class="flex-1 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-slate-400"></i>
                    <span>Resend Alert</span>
                </button>
                <button type="button" onclick="downloadAlertReport()" class="flex-1 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-download text-slate-400"></i>
                    <span>Download Report</span>
                </button>
            </div>

        </div>

    </div>

</main>

<script>
const broadcastData = {
    1: {
        title: 'Dengue Prevention Week Advisory',
        category: 'General Announcement',
        categoryClass: 'bg-blue-50 text-[#0f53d1]',
        iconClass: 'bg-blue-50 text-[#0f53d1] fa-bullhorn',
        sender: 'Juan Dela Cruz (Barangay Admin)',
        timestamp: 'Jun 8, 2025 • 9:42 AM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'All Residents',
        recipientsCount: '2,450',
        deliveredCount: '2,330 (95.1%)',
        failedCount: '83 (3.4%)',
        pendingCount: '37 (1.5%)',
        alertId: 'ALERT-2025-0608-0942',
        bodyHTML: `<p>Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang:</p>
            <ul class="list-disc pl-4 space-y-1 my-1">
                <li>Tanggalin ang pangatlong tubig sa paligid</li>
                <li>Takpan ang imbakan ng tubig</li>
                <li>Linisin ang paligid ng inyong tahanan</li>
            </ul>
            <p>Kung may sintomas tulad ng lagnat, sakit ng ulo, pananakit ng katawan, o pantal, kumonsulta agad sa pinakamalapit na health center.</p>
            <p>Maraming salamat sa inyong pakikiisa!</p>`
    },
    2: {
        title: 'Heavy Rainfall Warning',
        category: 'Emergency',
        categoryClass: 'bg-rose-50 text-rose-600',
        iconClass: 'bg-rose-50 text-rose-500 fa-triangle-exclamation',
        sender: 'Maria Santos (Health Officer)',
        timestamp: 'Jun 7, 2025 • 5:30 PM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'All Residents',
        recipientsCount: '2,615',
        deliveredCount: '2,510 (96.0%)',
        failedCount: '65 (2.5%)',
        pendingCount: '40 (1.5%)',
        alertId: 'ALERT-2025-0607-1730',
        bodyHTML: `<p>Nakaabang po tayo ng malakas na ulan ngayong gabi. Pinapaalalahan ang lahat na manatili sa ligtas na lugar at ihanda ang emergency kit.</p>`
    },
    3: {
        title: 'Free Medical Check-up',
        category: 'Health Advisory',
        categoryClass: 'bg-amber-50 text-amber-600',
        iconClass: 'bg-amber-50 text-amber-500 fa-heart-pulse',
        sender: 'Pedro Reyes (Barangay Staff)',
        timestamp: 'Jun 7, 2025 • 9:00 AM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'Senior Citizens & PWDs',
        recipientsCount: '1,980',
        deliveredCount: '1,910 (96.5%)',
        failedCount: '45 (2.2%)',
        pendingCount: '25 (1.3%)',
        alertId: 'ALERT-2025-0607-0900',
        bodyHTML: `<p>Libreng medical check-up at konsultasyon sa Barangay Health Center sa Sabado. Magparehistro sa inyong Purok Leader.</p>`
    },
    4: {
        title: 'Barangay Fiesta 2025',
        category: 'Event',
        categoryClass: 'bg-purple-50 text-purple-600',
        iconClass: 'bg-purple-50 text-purple-600 fa-calendar-star',
        sender: 'Juan Dela Cruz (Barangay Admin)',
        timestamp: 'Jun 6, 2025 • 3:15 PM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'All Residents',
        recipientsCount: '2,350',
        deliveredCount: '2,240 (95.3%)',
        failedCount: '70 (3.0%)',
        pendingCount: '40 (1.7%)',
        alertId: 'ALERT-2025-0606-1515',
        bodyHTML: `<p>Inaanyayahan po ang lahat ng residente na dumalo sa pagdiriwang ng Barangay Fiesta 2025. Maraming papremyo at palabas ang naghihintay!</p>`
    },
    5: {
        title: 'Curfew Ordinance Reminder',
        category: 'Curfew / Ordinance Notice',
        categoryClass: 'bg-emerald-50 text-emerald-600',
        iconClass: 'bg-emerald-50 text-emerald-600 fa-shield-halved',
        sender: 'Maria Santos (Health Officer)',
        timestamp: 'Jun 6, 2025 • 8:00 PM',
        status: 'Partial',
        statusClass: 'bg-amber-50 text-amber-600 border-amber-200',
        targetRecipients: 'Youth (15-24 yrs)',
        recipientsCount: '2,100',
        deliveredCount: '1,890 (90.0%)',
        failedCount: '150 (7.1%)',
        pendingCount: '60 (2.9%)',
        alertId: 'ALERT-2025-0606-2000',
        bodyHTML: `<p>Paalala: Ang curfew para sa mga kabataan (18 pababa) ay magsisimula sa ganap na 10:00 PM hanggang 4:00 AM.</p>`
    },
    6: {
        title: 'Garbage Collection Advisory',
        category: 'General Announcement',
        categoryClass: 'bg-blue-50 text-[#0f53d1]',
        iconClass: 'bg-blue-50 text-[#0f53d1] fa-bullhorn',
        sender: 'Pedro Reyes (Barangay Staff)',
        timestamp: 'Jun 5, 2025 • 7:30 AM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'District 1 - All Barangays',
        recipientsCount: '2,480',
        deliveredCount: '2,380 (96.0%)',
        failedCount: '60 (2.4%)',
        pendingCount: '40 (1.6%)',
        alertId: 'ALERT-2025-0605-0730',
        bodyHTML: `<p>Paalala po sa schedule ng koleksyon ng nabubulok na basura ngayong Martes at Huwebes. Ilabas po ang basura sa ganap na 6:00 AM.</p>`
    },
    7: {
        title: 'Heat Index Advisory',
        category: 'Health Advisory',
        categoryClass: 'bg-amber-50 text-amber-600',
        iconClass: 'bg-amber-50 text-amber-500 fa-heart-pulse',
        sender: 'Maria Santos (Health Officer)',
        timestamp: 'Jun 4, 2025 • 11:00 AM',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        targetRecipients: 'All Residents',
        recipientsCount: '2,620',
        deliveredCount: '2,520 (96.2%)',
        failedCount: '65 (2.5%)',
        pendingCount: '35 (1.3%)',
        alertId: 'ALERT-2025-0604-1100',
        bodyHTML: `<p>Mataas ang heat index ngayong araw (42°C). Uminom ng maraming tubig at iwasan ang matagal na pagstay sa ilalim ng araw.</p>`
    },
    8: {
        title: 'Youth Sports Week',
        category: 'Event',
        categoryClass: 'bg-purple-50 text-purple-600',
        iconClass: 'bg-purple-50 text-purple-600 fa-calendar-star',
        sender: 'Juan Dela Cruz (Barangay Admin)',
        timestamp: 'Jun 3, 2025 • 2:00 PM',
        status: 'Scheduled',
        statusClass: 'bg-blue-50 text-[#0f53d1] border-blue-200',
        targetRecipients: 'Youth (15-24 yrs)',
        recipientsCount: '1,750',
        deliveredCount: '0 (0%)',
        failedCount: '0 (0%)',
        pendingCount: '1,750 (100%)',
        alertId: 'ALERT-2025-0603-1400',
        bodyHTML: `<p>Bukas na ang rehistrasyon para sa Youth Basketball at Volleyball League! Bisitahin ang Barangay Hall para sa karagdagang detalye.</p>`
    }
};

let activeBroadcastId = null;

function selectBroadcastRow(rowElement, id) {
    const drawer = document.getElementById('alertDetailsDrawer');
    const tableContainer = document.getElementById('tableContainer');

    if (activeBroadcastId === id && !drawer.classList.contains('hidden')) {
        closeDetailsDrawer();
        return;
    }

    activeBroadcastId = id;
    document.querySelectorAll('.broadcast-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    rowElement.classList.add('bg-blue-50/40');

    const data = broadcastData[id];
    if (!data) return;

    // Update Drawer UI
    document.getElementById('drawerTitle').innerText = data.title;
    document.getElementById('drawerCategoryText').innerText = data.category;
    document.getElementById('drawerTimestamp').innerHTML = `<i class="fa-regular fa-calendar"></i> <span>${data.timestamp}</span>`;
    
    // Status Badge
    const statusBadge = document.getElementById('drawerStatusBadge');
    statusBadge.innerText = data.status;
    statusBadge.className = `px-2.5 py-0.5 rounded-full font-bold text-[10px] border shrink-0 ${data.statusClass}`;

    // Icon
    const iconContainer = document.getElementById('drawerCategoryIcon');
    iconContainer.className = `w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm ${data.iconClass.split(' ')[0]} ${data.iconClass.split(' ')[1]}`;
    iconContainer.innerHTML = `<i class="fa-solid ${data.iconClass.split(' ')[2]}"></i>`;

    // Message Content & Summary
    document.getElementById('drawerMessageContent').innerHTML = data.bodyHTML;
    document.getElementById('drawerRecipientsTarget').innerText = data.targetRecipients;
    document.getElementById('drawerRecipientsBadge').innerText = `${data.recipientsCount} recipients`;
    document.getElementById('donutTotalRecipients').innerText = data.recipientsCount;
    document.getElementById('donutDeliveredCount').innerText = data.deliveredCount;
    document.getElementById('donutFailedCount').innerText = data.failedCount;
    document.getElementById('donutPendingCount').innerText = data.pendingCount;

    // Metadata
    document.getElementById('drawerCreatedBy').innerHTML = `${data.sender}`;
    document.getElementById('drawerAlertId').innerHTML = `<span>${data.alertId}</span> <i class="fa-regular fa-copy text-slate-400 hover:text-slate-700 cursor-pointer" onclick="copyAlertId('${data.alertId}')"></i>`;

    // Unhide drawer if hidden
    drawer.classList.remove('hidden');
    tableContainer.className = "lg:col-span-8 space-y-4 transition-all duration-300";
}

function closeDetailsDrawer() {
    activeBroadcastId = null;
    document.querySelectorAll('.broadcast-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    const drawer = document.getElementById('alertDetailsDrawer');
    const tableContainer = document.getElementById('tableContainer');
    drawer.classList.add('hidden');
    tableContainer.className = "lg:col-span-12 space-y-4 transition-all duration-300";
}

function filterTable() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const catVal = document.getElementById('categoryFilter').value.toLowerCase();
    const statusVal = document.getElementById('statusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.broadcast-row');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const cat = r.getAttribute('data-category').toLowerCase();
        const status = r.getAttribute('data-status').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesCat = !catVal || cat.includes(catVal);
        const matchesStatus = !statusVal || status.includes(statusVal);

        if (matchesSearch && matchesCat && matchesStatus) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function exportReport() {
    alert('Exporting Broadcast History Report as CSV/PDF...');
}

function resendAlert() {
    const title = document.getElementById('drawerTitle').innerText;
    alert(`Initiating re-broadcast transmission for: "${title}".`);
}

function downloadAlertReport() {
    const alertId = document.getElementById('drawerAlertId').innerText.trim();
    alert(`Downloading delivery metrics report for ID: ${alertId}...`);
}

function copyAlertId(alertId) {
    const idText = alertId || document.getElementById('drawerAlertId').innerText.trim();
    navigator.clipboard.writeText(idText);
    alert(`Copied Alert ID: ${idText}`);
}
</script>

<?php include '../../includes/footer.php'; ?>
