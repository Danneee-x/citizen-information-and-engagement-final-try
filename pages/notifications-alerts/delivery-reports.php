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
        <span class="text-brand-dark">Delivery Reports</span>
    </div>

    <!-- Top KPI Summary Cards Row (5 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        
        <!-- Card 1: Total Recipients -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Recipients</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">2,450</h3>
                <span class="text-[10px] font-bold text-emerald-600">100%</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">All recipients</p>
        </div>

        <!-- Card 2: Delivered -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Delivered</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">2,254</h3>
                <span class="text-[10px] font-bold text-emerald-600">92.0%</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Successfully delivered</p>
        </div>

        <!-- Card 3: Read (If Trackable) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Read <span class="text-[9px] text-slate-400 font-normal">(Trackable)</span></span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">1,478</h3>
                <span class="text-[10px] font-bold text-purple-600">60.3%</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Read by recipients</p>
        </div>

        <!-- Card 4: Failed -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Failed</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">147</h3>
                <span class="text-[10px] font-bold text-rose-600">6.0%</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Could not be delivered</p>
        </div>

        <!-- Card 5: Pending -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="flex items-baseline justify-between">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">49</h3>
                <span class="text-[10px] font-bold text-amber-600">2.0%</span>
            </div>
            <p class="text-[10px] text-slate-400 font-medium">Delivery in progress</p>
        </div>

    </div>

    <!-- Analytics Dashboard Grid (3 Cards: Rate Summary, Rate by Channel, Failure Reasons) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Card A: Delivery Rate Summary (4 Cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4 flex flex-col justify-between">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Delivery Rate Summary</h4>
            
            <div class="flex items-center gap-4">
                <!-- SVG Donut Chart -->
                <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <path class="text-slate-100" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Pending (Amber 2.0%) -->
                        <path class="text-amber-400" stroke-dasharray="100, 100" stroke-dashoffset="0" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Failed (Rose 6.0%) -->
                        <path class="text-rose-500" stroke-dasharray="98, 100" stroke-dashoffset="0" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Read (Purple 60.3%) -->
                        <path class="text-purple-500" stroke-dasharray="92, 100" stroke-dashoffset="0" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <!-- Delivered (Green 92.0%) -->
                        <path class="text-emerald-500" stroke-dasharray="92.0, 100" stroke-dashoffset="0" stroke-width="4" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-sm font-black text-slate-900 leading-none">92.0%</span>
                        <span class="text-[8px] font-bold text-slate-400 leading-tight mt-0.5 max-w-[50px]">Overall Delivery Rate</span>
                    </div>
                </div>

                <!-- Donut Legend -->
                <div class="space-y-1.5 text-xs flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-slate-600 font-medium text-[11px]">Delivered</span>
                        </div>
                        <span class="font-bold text-slate-900 text-[11px]">2,254 (92.0%)</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span class="text-slate-600 font-medium text-[11px]">Read</span>
                        </div>
                        <span class="font-bold text-slate-900 text-[11px]">1,478 (60.3%)</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span class="text-slate-600 font-medium text-[11px]">Failed</span>
                        </div>
                        <span class="font-bold text-slate-900 text-[11px]">147 (6.0%)</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <span class="text-slate-600 font-medium text-[11px]">Pending</span>
                        </div>
                        <span class="font-bold text-slate-900 text-[11px]">49 (2.0%)</span>
                    </div>
                </div>
            </div>

            <!-- Light Green Callout Banner -->
            <div class="p-2.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl flex items-center gap-2 text-[11px] text-emerald-800 font-medium mt-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xs shrink-0"></i>
                <span>Great! Your message reached most of your recipients.</span>
            </div>
        </div>

        <!-- Card B: Delivery Rate by Channel (4 Cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Delivery Rate by Channel</h4>

            <div class="space-y-4 pt-1">
                
                <!-- Channel 1: SMS -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-bold text-slate-800">
                            <i class="fa-solid fa-comment-dots text-emerald-500"></i>
                            <span>SMS</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-emerald-600">94.6%</span>
                            <span class="text-[10px] text-slate-400 font-semibold">1,694 / 1,790</span>
                        </div>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: 94.6%"></div>
                    </div>
                </div>

                <!-- Channel 2: In-App / Push -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-bold text-slate-800">
                            <i class="fa-solid fa-bell text-[#0f53d1]"></i>
                            <span>In-App / Push</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-[#0f53d1]">90.8%</span>
                            <span class="text-[10px] text-slate-400 font-semibold">1,102 / 1,214</span>
                        </div>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-[#0f53d1] rounded-full" style="width: 90.8%"></div>
                    </div>
                </div>

                <!-- Channel 3: Email -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-bold text-slate-800">
                            <i class="fa-solid fa-envelope text-purple-600"></i>
                            <span>Email</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-purple-600">88.2%</span>
                            <span class="text-[10px] text-slate-400 font-semibold">842 / 956</span>
                        </div>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-600 rounded-full" style="width: 88.2%"></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Card C: Failure Reasons (4 Cols) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Failure Reasons</h4>

            <div class="space-y-2.5 text-xs text-slate-700 font-medium">
                
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Invalid phone number</span>
                    <span class="font-bold text-slate-900">68 <span class="text-slate-400 font-normal">(46.3%)</span></span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">No internet connection</span>
                    <span class="font-bold text-slate-900">34 <span class="text-slate-400 font-normal">(23.1%)</span></span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Number not reachable</span>
                    <span class="font-bold text-slate-900">27 <span class="text-slate-400 font-normal">(18.4%)</span></span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Blocked by carrier</span>
                    <span class="font-bold text-slate-900">11 <span class="text-slate-400 font-normal">(7.5%)</span></span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Other / Unknown</span>
                    <span class="font-bold text-slate-900">7 <span class="text-slate-400 font-normal">(4.8%)</span></span>
                </div>

            </div>
        </div>

    </div>

    <!-- Select Broadcast & Filters Row Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Select Broadcast (4 Cols) -->
            <div class="lg:col-span-4">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Select Broadcast</label>
                <div class="relative">
                    <select id="broadcastSelect" onchange="onBroadcastChange(this.value)" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-bold rounded-xl py-2.5 px-3 text-xs outline-none cursor-pointer pr-8">
                        <option value="1">Dengue Prevention Week Advisory (Jun 8, 2025 • Delivered)</option>
                        <option value="2">Heavy Rainfall Warning (Jun 7, 2025 • Delivered)</option>
                        <option value="3">Free Medical Check-up (Jun 7, 2025 • Delivered)</option>
                    </select>
                </div>
            </div>

            <!-- Channel Filter (2 Cols) -->
            <div class="lg:col-span-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Channel</label>
                <select id="channelFilter" onchange="filterRecipientsTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-2.5 text-xs outline-none cursor-pointer">
                    <option value="">All Channels</option>
                    <option value="SMS">SMS</option>
                    <option value="In-App">In-App / Push</option>
                    <option value="Email">Email</option>
                </select>
            </div>

            <!-- Status Filter (2 Cols) -->
            <div class="lg:col-span-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Status</label>
                <select id="statusFilter" onchange="filterRecipientsTable()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl py-2.5 px-2.5 text-xs outline-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Read">Read</option>
                    <option value="Failed">Failed</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            <!-- Search Recipient (3 Cols) -->
            <div class="lg:col-span-3">
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Search Recipient</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchRecipientInput" oninput="filterRecipientsTable()" placeholder="Search name, number, email..." class="w-full pr-8 pl-3 py-2 bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl text-xs outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                </div>
            </div>

            <!-- Filter Action Button (1 Col) -->
            <div class="lg:col-span-1 pt-4">
                <button type="button" onclick="filterRecipientsTable()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1 cursor-pointer">
                    <i class="fa-solid fa-sliders text-slate-400 text-xs"></i>
                    <span>Filter</span>
                </button>
            </div>

        </div>
    </div>

    <!-- Main Table & Recipient Details Side Panel Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Table Container (8 Cols when drawer open, 12 Cols when closed) -->
        <div id="tableContainer" class="lg:col-span-12 space-y-4 transition-all duration-300">
            
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                
                <!-- Table Header Bar -->
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Recipients <span class="text-xs text-slate-400 font-semibold">(2,450)</span></h3>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Recipient</th>
                                <th class="py-3.5 px-3">Contact / Account</th>
                                <th class="py-3.5 px-3">Channel</th>
                                <th class="py-3.5 px-3">Status</th>
                                <th class="py-3.5 px-3">Delivered / Read At</th>
                                <th class="py-3.5 px-3">Failure Reason (if any)</th>
                                <th class="py-3.5 px-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recipientsTableBody" class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            
                            <!-- Row 1 -->
                            <tr onclick="selectRecipientRow(this, 1)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="1" data-channel="SMS" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Maria Santos</p>
                                    <p class="text-[10px] text-slate-400">Barangay 178, District 3</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">0917 123 4567</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">SMS</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-check text-[9px]"></i> Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">Jun 8, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:43 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 2 -->
                            <tr onclick="selectRecipientRow(this, 2)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="2" data-channel="In-App" data-status="Read">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Juan Dela Cruz</p>
                                    <p class="text-[10px] text-slate-400">Barangay 12, District 2</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">In-App User</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">* In-App / Push</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-600 font-bold text-[10px] border border-purple-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-check-double text-[9px]"></i> Read</span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">Jun 8, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:44 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 3 -->
                            <tr onclick="selectRecipientRow(this, 3)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="3" data-channel="Email" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Ana Reyes</p>
                                    <p class="text-[10px] text-slate-400">Barangay 1, District 1</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">anareyes88@gmail.com</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-600 font-bold text-[10px] border border-purple-100">Email</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-check text-[9px]"></i> Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">Jun 8, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:45 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 4 -->
                            <tr onclick="selectRecipientRow(this, 4)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="4" data-channel="SMS" data-status="Failed">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Pedro Ramos</p>
                                    <p class="text-[10px] text-slate-400">Barangay 77, District 1</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">0998 765 4321</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">SMS</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 font-bold text-[10px] border border-rose-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Failed</span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-rose-600 font-semibold">Invalid phone number</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 5 -->
                            <tr onclick="selectRecipientRow(this, 5)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="5" data-channel="In-App" data-status="Pending">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Liza Gonzales</p>
                                    <p class="text-[10px] text-slate-400">Barangay 188, District 3</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">In-App User</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">* In-App / Push</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 font-bold text-[10px] border border-amber-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-clock text-[9px]"></i> Pending</span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-slate-500 font-semibold">No internet connection</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 6 -->
                            <tr onclick="selectRecipientRow(this, 6)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="6" data-channel="Email" data-status="Failed">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Roderick Lim</p>
                                    <p class="text-[10px] text-slate-400">Barangay 176, District 1</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">roderick.lim@email.com</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-600 font-bold text-[10px] border border-purple-100">Email</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 font-bold text-[10px] border border-rose-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Failed</span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-rose-600 font-semibold">Mailbox not reachable</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                            <!-- Row 7 -->
                            <tr onclick="selectRecipientRow(this, 7)" class="recipient-row hover:bg-slate-50 transition cursor-pointer" data-id="7" data-channel="SMS" data-status="Delivered">
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900">Carla Dela Vega</p>
                                    <p class="text-[10px] text-slate-400">Barangay 1, District 1</p>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-800">0916 111 2222</td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">SMS</span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-check text-[9px]"></i> Delivered</span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">Jun 8, 2025</p>
                                    <p class="text-[10px] text-slate-400">9:46 AM</p>
                                </td>
                                <td class="py-3.5 px-3 text-slate-400">-</td>
                                <td class="py-3.5 px-3 text-center">
                                    <button class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition mx-auto"><i class="fa-regular fa-eye text-xs"></i></button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <!-- Table Footer Pagination -->
                <div class="px-4 py-3 bg-slate-50/50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
                    <div>
                        <span>Showing 1 to 10 of 2,450 recipients</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-400 hover:text-slate-700 transition cursor-pointer text-xs"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <button class="w-7 h-7 rounded-lg bg-[#0f53d1] text-white font-bold flex items-center justify-center shadow-xs text-xs">1</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">2</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">3</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">4</button>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">5</button>
                        <span class="px-1 text-slate-400 font-bold">...</span>
                        <button class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 font-bold transition cursor-pointer text-xs">245</button>
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

        <!-- Right Side Panel: Recipient Details (4 Cols) -->
        <div id="recipientDetailsDrawer" class="hidden lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 sticky top-6">
            
            <!-- Drawer Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-black text-slate-900 tracking-tight">Recipient Details</h2>
                <button onclick="closeRecipientDrawer()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Recipient User Profile Card -->
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-[#0f53d1] flex items-center justify-center text-base font-bold shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="min-w-0">
                    <h3 id="drawerRecipientName" class="text-sm font-black text-slate-900 leading-tight truncate">Maria Santos</h3>
                    <p id="drawerRecipientAddress" class="text-xs text-slate-400 font-medium truncate">Barangay 178, District 3</p>
                </div>
            </div>

            <!-- Recipient Info Grid -->
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Contact</span>
                    <span id="drawerRecipientContact" class="font-bold text-slate-900">0917 123 4567</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Channel</span>
                    <span id="drawerRecipientChannel" class="font-bold text-slate-900">SMS</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Status</span>
                    <span id="drawerRecipientStatus" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Delivered</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-medium">Delivered At</span>
                    <span id="drawerRecipientDeliveredAt" class="font-bold text-slate-900">Jun 8, 2025 &bull; 9:43 AM</span>
                </div>
            </div>

            <!-- Alert Information Card -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5 text-xs">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ALERT INFORMATION</h4>
                
                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block">Alert Title</span>
                    <p id="drawerAlertTitle" class="font-bold text-slate-900 text-xs">Dengue Prevention Week Advisory</p>
                </div>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block mb-1">Category</span>
                    <span id="drawerAlertCategory" class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold text-[10px] border border-blue-100">General Announcement</span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                    <div>
                        <span class="text-slate-400 font-semibold block">Sent By</span>
                        <p class="font-bold text-slate-800 leading-snug">Juan Dela Cruz <br><span class="text-slate-400 font-normal">(Barangay Admin)</span></p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Date / Time Sent</span>
                        <p class="font-bold text-slate-800">Jun 8, 2025 &bull; 9:42 AM</p>
                    </div>
                </div>

                <div class="pt-1">
                    <span class="text-slate-400 font-semibold block text-[11px]">Total Recipients</span>
                    <p class="font-bold text-slate-800 text-xs">2,450</p>
                </div>

                <div>
                    <span class="text-slate-400 font-semibold block text-[11px] mb-1">Delivery Channels</span>
                    <div class="flex items-center gap-2 text-[10px]">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold border border-emerald-200"><i class="fa-solid fa-comment-dots"></i> SMS</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-[#0f53d1] font-bold border border-blue-200"><i class="fa-solid fa-bell"></i> In-App / Push</span>
                        <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 font-bold border border-purple-200"><i class="fa-solid fa-envelope"></i> Email</span>
                    </div>
                </div>

                <button type="button" onclick="viewFullMessageModal()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer mt-1">
                    <i class="fa-regular fa-eye text-slate-400"></i>
                    <span>View Full Message</span>
                </button>
            </div>

            <!-- Delivery Timeline Stepper -->
            <div class="space-y-2 border-t border-slate-100 pt-3">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DELIVERY TIMELINE</h4>
                
                <div class="relative pl-5 space-y-3 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-emerald-200 text-xs">
                    
                    <!-- Timeline 1 -->
                    <div class="relative">
                        <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></div>
                        <p class="font-bold text-slate-900 text-xs">Sent</p>
                        <p class="text-[10px] text-slate-400 font-medium">Jun 8, 2025 &bull; 9:42 AM</p>
                    </div>

                    <!-- Timeline 2 -->
                    <div class="relative">
                        <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></div>
                        <p class="font-bold text-slate-900 text-xs">Delivered to Network</p>
                        <p class="text-[10px] text-slate-400 font-medium">Jun 8, 2025 &bull; 9:43 AM</p>
                    </div>

                    <!-- Timeline 3 -->
                    <div class="relative">
                        <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></div>
                        <p class="font-bold text-slate-900 text-xs">Delivered to Device</p>
                        <p class="text-[10px] text-slate-400 font-medium">Jun 8, 2025 &bull; 9:43 AM</p>
                    </div>

                </div>
            </div>

            <!-- Action Button -->
            <div class="border-t border-slate-100 pt-3">
                <button type="button" onclick="downloadRecipientReport()" class="w-full py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-download text-slate-400"></i>
                    <span>Download Recipient Report</span>
                </button>
            </div>

        </div>

    </div>

</main>

<script>
const recipientData = {
    1: {
        name: 'Maria Santos',
        address: 'Barangay 178, District 3',
        contact: '0917 123 4567',
        channel: 'SMS',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        deliveredAt: 'Jun 8, 2025 • 9:43 AM'
    },
    2: {
        name: 'Juan Dela Cruz',
        address: 'Barangay 12, District 2',
        contact: 'In-App User',
        channel: 'In-App / Push',
        status: 'Read',
        statusClass: 'bg-purple-50 text-purple-600 border-purple-200',
        deliveredAt: 'Jun 8, 2025 • 9:44 AM'
    },
    3: {
        name: 'Ana Reyes',
        address: 'Barangay 1, District 1',
        contact: 'anareyes88@gmail.com',
        channel: 'Email',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        deliveredAt: 'Jun 8, 2025 • 9:45 AM'
    },
    4: {
        name: 'Pedro Ramos',
        address: 'Barangay 77, District 1',
        contact: '0998 765 4321',
        channel: 'SMS',
        status: 'Failed',
        statusClass: 'bg-rose-50 text-rose-600 border-rose-200',
        deliveredAt: '-'
    },
    5: {
        name: 'Liza Gonzales',
        address: 'Barangay 188, District 3',
        contact: 'In-App User',
        channel: 'In-App / Push',
        status: 'Pending',
        statusClass: 'bg-amber-50 text-amber-600 border-amber-200',
        deliveredAt: '-'
    },
    6: {
        name: 'Roderick Lim',
        address: 'Barangay 176, District 1',
        contact: 'roderick.lim@email.com',
        channel: 'Email',
        status: 'Failed',
        statusClass: 'bg-rose-50 text-rose-600 border-rose-200',
        deliveredAt: '-'
    },
    7: {
        name: 'Carla Dela Vega',
        address: 'Barangay 1, District 1',
        contact: '0916 111 2222',
        channel: 'SMS',
        status: 'Delivered',
        statusClass: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        deliveredAt: 'Jun 8, 2025 • 9:46 AM'
    }
};

let activeRecipientId = null;

function selectRecipientRow(rowElement, id) {
    const drawer = document.getElementById('recipientDetailsDrawer');
    const tableContainer = document.getElementById('tableContainer');

    if (activeRecipientId === id && !drawer.classList.contains('hidden')) {
        closeRecipientDrawer();
        return;
    }

    activeRecipientId = id;
    document.querySelectorAll('.recipient-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    rowElement.classList.add('bg-blue-50/40');

    const data = recipientsData[id];
    if (!data) return;

    document.getElementById('drawerRecipientName').innerText = data.name;
    document.getElementById('drawerRecipientAddress').innerText = data.address;
    document.getElementById('drawerRecipientContact').innerText = data.contact;
    document.getElementById('drawerRecipientChannel').innerText = data.channel;
    document.getElementById('drawerRecipientDeliveredAt').innerText = data.deliveredAt;

    const statusSpan = document.getElementById('drawerRecipientStatus');
    statusSpan.innerText = data.status;
    statusSpan.className = `px-2.5 py-0.5 rounded-full font-bold text-[10px] border ${data.statusClass}`;

    drawer.classList.remove('hidden');
    tableContainer.className = "lg:col-span-8 space-y-4 transition-all duration-300";
}

function closeRecipientDrawer() {
    activeRecipientId = null;
    document.querySelectorAll('.recipient-row').forEach(r => {
        r.classList.remove('bg-blue-50/40', 'bg-blue-50/60');
    });
    const drawer = document.getElementById('recipientDetailsDrawer');
    const tableContainer = document.getElementById('tableContainer');
    drawer.classList.add('hidden');
    tableContainer.className = "lg:col-span-12 space-y-4 transition-all duration-300";
}

function filterRecipientsTable() {
    const searchVal = document.getElementById('searchRecipientInput').value.toLowerCase();
    const channelVal = document.getElementById('channelFilter').value.toLowerCase();
    const statusVal = document.getElementById('statusFilter').value.toLowerCase();

    const rows = document.querySelectorAll('.recipient-row');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const channel = r.getAttribute('data-channel').toLowerCase();
        const status = r.getAttribute('data-status').toLowerCase();

        const matchesSearch = !searchVal || text.includes(searchVal);
        const matchesChannel = !channelVal || channel.includes(channelVal);
        const matchesStatus = !statusVal || status.includes(statusVal);

        if (matchesSearch && matchesChannel && matchesStatus) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function onBroadcastChange(broadcastId) {
    alert(`Switched broadcast view to Broadcast ID #${broadcastId}. Recipient list refreshed.`);
}

function viewFullMessageModal() {
    alert(`Full Message Preview:\n\nTitle: Dengue Prevention Week Advisory\nBody: Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang:\n• Tanggalin ang pangatlong tubig sa paligid\n• Takpan ang imbakan ng tubig\n• Linisin ang paligid ng inyong tahanan`);
}

function downloadRecipientReport() {
    const name = document.getElementById('drawerRecipientName').innerText;
    alert(`Downloading delivery logs report for recipient: ${name}...`);
}
</script>

<?php include '../../includes/footer.php'; ?>
