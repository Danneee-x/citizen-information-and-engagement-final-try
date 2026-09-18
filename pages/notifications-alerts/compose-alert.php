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
    .rich-btn.active {
        background-color: #e2e8f0;
        color: #0f53d1;
    }
    [contenteditable="true"]:empty:before {
        content: attr(placeholder);
        color: #94a3b8;
        pointer-events: none;
        display: block;
    }
    #alertBodyInput ul {
        list-style-type: disc;
        padding-left: 1.25rem;
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    #alertBodyInput ol {
        list-style-type: decimal;
        padding-left: 1.25rem;
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    #alertBodyInput h1 {
        font-size: 1.25rem;
        font-weight: 800;
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    #alertBodyInput h2 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    #alertBodyInput a {
        color: #0f53d1;
        text-decoration: underline;
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/50 min-h-[calc(100vh-4rem)] space-y-6">

    <!-- Breadcrumb Header -->
    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
        <span>Notifications & Alerts</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Compose Alert</span>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT COLUMN (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Section 1: Message Details -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <span>1. Message Details</span>
                    </h2>
                </div>

                <div class="space-y-4">
                    <!-- Message Title -->
                    <div>
                        <label class="text-xs font-bold text-slate-800 uppercase block mb-1.5">
                            Message Title <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="alertTitleInput" oninput="updatePreviewSummary(); validateFormInputs()" placeholder="Enter a clear and short title for your alert" class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-medium text-xs rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#0f53d1]/40 focus:border-[#0f53d1]">
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label class="text-xs font-bold text-slate-800 uppercase block mb-1.5">
                            Message Body <span class="text-rose-500">*</span>
                        </label>
                        
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                            <!-- Editor Toolbar -->
                            <div class="flex items-center gap-1.5 p-2 bg-slate-50 border-b border-slate-200 flex-wrap text-xs text-slate-600">
                                <select id="btnHeadingSelect" onchange="formatHeading(this.value)" class="bg-white border border-slate-200 rounded-md text-[11px] font-semibold px-2 py-1 outline-none cursor-pointer">
                                    <option value="Normal">Normal</option>
                                    <option value="Heading 1">Heading 1</option>
                                    <option value="Heading 2">Heading 2</option>
                                </select>
                                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" id="btnBold" onclick="formatText('bold')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center font-bold transition cursor-pointer" title="Bold">B</button>
                                <button type="button" id="btnItalic" onclick="formatText('italic')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center italic transition cursor-pointer" title="Italic">I</button>
                                <button type="button" id="btnUnderline" onclick="formatText('underline')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center underline transition cursor-pointer" title="Underline">U</button>
                                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" id="btnBulletList" onclick="formatText('insertUnorderedList')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Bullet List"><i class="fa-solid fa-list-ul text-[10px]"></i></button>
                                <button type="button" id="btnNumberedList" onclick="formatText('insertOrderedList')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Numbered List"><i class="fa-solid fa-list-ol text-[10px]"></i></button>
                                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" id="btnAlignLeft" onclick="formatText('justifyLeft')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Align Left"><i class="fa-solid fa-align-left text-[10px]"></i></button>
                                <button type="button" id="btnAlignCenter" onclick="formatText('justifyCenter')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Align Center"><i class="fa-solid fa-align-center text-[10px]"></i></button>
                                <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                <button type="button" id="btnInsertLink" onclick="formatLink()" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Insert Link"><i class="fa-solid fa-link text-[10px]"></i></button>
                                <button type="button" id="btnClearFormat" onclick="formatText('removeFormat')" class="rich-btn w-7 h-7 rounded hover:bg-slate-200 flex items-center justify-center transition cursor-pointer" title="Clear Format"><i class="fa-solid fa-paragraph text-[10px]"></i></button>
                            </div>

                            <!-- Contenteditable Editor Container -->
                            <div id="alertBodyInput" contenteditable="true" oninput="updateCharCount(); updateToolbarActiveState()" onkeyup="updateCharCount(); updateToolbarActiveState()" onmouseup="updateToolbarActiveState()" placeholder="Type your message here..." class="w-full p-3.5 text-xs text-slate-800 font-medium outline-none min-h-[140px] border-0 overflow-y-auto"></div>
                            
                            <div class="px-3 py-1.5 bg-slate-50 border-t border-slate-100 text-right text-[10px] font-bold text-slate-400">
                                <span id="charCounter">0</span> / 1000 characters
                            </div>
                        </div>
                    </div>

                    <!-- Attach File & Tips Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-1">
                        
                        <!-- File Upload Box (7 Cols) -->
                        <div class="md:col-span-7 space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase block">
                                Attach Image / Document <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>

                            <!-- Interactive File Dropzone Container -->
                            <div id="fileDropzone" 
                                 onclick="triggerFileSelect()" 
                                 ondragover="handleDragOver(event)" 
                                 ondragleave="handleDragLeave(event)" 
                                 ondrop="handleFileDrop(event)" 
                                 class="border-2 border-dashed border-slate-200 hover:border-[#0f53d1] bg-slate-50/50 hover:bg-blue-50/30 rounded-2xl p-5 text-center cursor-pointer transition flex flex-col items-center justify-center min-h-[115px] relative group select-none">
                                
                                <input type="file" id="fileUploadInput" accept="image/*,.pdf" class="hidden" onchange="handleFileSelect(this)">
                                
                                <!-- Default Upload Prompt -->
                                <div id="uploadDefaultState" class="flex flex-col items-center justify-center">
                                    <div class="w-9 h-9 rounded-full bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm mb-2 border border-blue-100 group-hover:scale-110 transition-transform">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">
                                        Drag and drop file here or <span class="text-[#0f53d1] underline">click to browse</span>
                                    </p>
                                    <p class="text-[9px] font-semibold text-slate-400 mt-1" id="fileSelectedText">
                                        Supports: JPG, PNG, PDF (Max. 5MB)
                                    </p>
                                </div>

                                <!-- Uploaded File Preview Card -->
                                <div id="uploadSuccessState" class="hidden w-full flex items-center justify-between p-2 bg-white border border-slate-200 rounded-xl shadow-xs">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div id="filePreviewContainer" class="w-12 h-12 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 overflow-hidden border border-slate-200">
                                            <i id="fileTypeIcon" class="fa-solid fa-image text-sm"></i>
                                            <img id="filePreviewImage" class="hidden w-full h-full object-cover" src="" alt="Preview">
                                        </div>
                                        
                                        <div class="min-w-0 text-left">
                                            <div class="flex items-center gap-2">
                                                <p id="fileNameDisplay" class="text-xs font-bold text-slate-800 truncate">file.png</p>
                                                <span class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-extrabold text-[9px] border border-emerald-200 shrink-0">Uploaded</span>
                                            </div>
                                            <p id="fileSizeDisplay" class="text-[10px] text-slate-400 font-semibold mt-0.5">1.2 MB</p>
                                        </div>
                                    </div>

                                    <button type="button" onclick="removeAttachedFile(event)" class="w-7 h-7 rounded-lg bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition cursor-pointer shrink-0 ml-2" title="Remove file">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- Tips Callout Box (5 Cols) -->
                        <div class="md:col-span-5 bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-4 space-y-2">
                            <h4 class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                                Tips for effective alerts
                            </h4>
                            <ul class="text-[11px] text-emerald-700 font-medium space-y-1.5 leading-tight">
                                <li class="flex items-start gap-1.5">
                                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5"></i>
                                    <span>Use a clear and descriptive title.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5"></i>
                                    <span>Keep the message short and easy to understand.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5"></i>
                                    <span>Include important details (time, location, actions).</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <i class="fa-solid fa-check text-emerald-600 text-[10px] mt-0.5"></i>
                                    <span>Avoid using ALL CAPS.</span>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Section 4: Delivery Channels -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">4. Delivery Channels</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Select the channels where this alert will be delivered.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    
                    <!-- Channel 1: SMS -->
                    <label class="channel-card cursor-pointer bg-white border-2 border-slate-200 rounded-2xl p-3.5 flex items-start gap-3 hover:border-slate-300 transition select-none">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-sm border border-emerald-100">
                            <i class="fa-solid fa-comment-dots"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">SMS</span>
                                <input type="checkbox" id="channelSms" checked onchange="updatePreviewSummary()" class="w-4 h-4 text-[#0f53d1] rounded border-slate-300 focus:ring-[#0f53d1]/40 cursor-pointer">
                            </div>
                            <p class="text-[10px] text-slate-400 font-medium leading-snug mt-1">Deliver via SMS to registered mobile numbers</p>
                        </div>
                    </label>

                    <!-- Channel 2: In-App / Push Notification -->
                    <label class="channel-card cursor-pointer bg-white border-2 border-slate-200 rounded-2xl p-3.5 flex items-start gap-3 hover:border-slate-300 transition select-none">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm border border-blue-100">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">In-App / Push</span>
                                <input type="checkbox" id="channelPush" checked onchange="updatePreviewSummary()" class="w-4 h-4 text-[#0f53d1] rounded border-slate-300 focus:ring-[#0f53d1]/40 cursor-pointer">
                            </div>
                            <p class="text-[10px] text-slate-400 font-medium leading-snug mt-1">Send push notification to app users</p>
                        </div>
                    </label>

                    <!-- Channel 3: Email -->
                    <label class="channel-card cursor-pointer bg-white border-2 border-slate-200 rounded-2xl p-3.5 flex items-start gap-3 hover:border-slate-300 transition select-none">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 text-sm border border-purple-100">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">Email</span>
                                <input type="checkbox" id="channelEmail" onchange="updatePreviewSummary()" class="w-4 h-4 text-[#0f53d1] rounded border-slate-300 focus:ring-[#0f53d1]/40 cursor-pointer">
                            </div>
                            <p class="text-[10px] text-slate-400 font-medium leading-snug mt-1">Send via email to registered email addresses</p>
                        </div>
                    </label>

                </div>

                <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-xl text-[11px] text-slate-500 font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-[#0f53d1]"></i>
                    <span>Note: Delivery availability may vary depending on recipient contact information and settings.</span>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Section 2: Alert Category -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">2. Alert Category</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Select the most appropriate category for this alert.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-5 gap-2.5">
                    
                    <!-- Category 1: Emergency -->
                    <div onclick="selectCategory('Emergency', this)" class="category-card cursor-pointer border-2 border-slate-200 hover:border-slate-300 bg-white rounded-2xl p-3 text-center transition flex flex-col items-center justify-between relative min-h-[90px] select-none">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-sm mb-1">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <span class="text-[11px] font-bold text-rose-600 leading-tight">Emergency</span>
                    </div>

                    <!-- Category 2: Health Advisory -->
                    <div onclick="selectCategory('Health Advisory', this)" class="category-card cursor-pointer border-2 border-slate-200 hover:border-slate-300 bg-white rounded-2xl p-3 text-center transition flex flex-col items-center justify-between relative min-h-[90px] select-none">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-sm mb-1">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>
                        <span class="text-[11px] font-bold text-amber-600 leading-tight">Health Advisory</span>
                    </div>

                    <!-- Category 3: Event -->
                    <div onclick="selectCategory('Event', this)" class="category-card cursor-pointer border-2 border-slate-200 hover:border-slate-300 bg-white rounded-2xl p-3 text-center transition flex flex-col items-center justify-between relative min-h-[90px] select-none">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm mb-1">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <span class="text-[11px] font-bold text-purple-600 leading-tight">Event</span>
                    </div>

                    <!-- Category 4: General Announcement (Selected default) -->
                    <div onclick="selectCategory('General Announcement', this)" class="category-card active-category cursor-pointer border-2 border-[#0f53d1] bg-blue-50/20 rounded-2xl p-3 text-center transition flex flex-col items-center justify-between relative min-h-[90px] select-none shadow-xs">
                        <div class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-[#0f53d1] text-white flex items-center justify-center text-[9px] shadow-xs check-badge">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0f53d1] flex items-center justify-center text-sm mb-1">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <span class="text-[11px] font-bold text-[#0f53d1] leading-tight">General Announcement</span>
                    </div>

                    <!-- Category 5: Curfew / Ordinance Notice -->
                    <div onclick="selectCategory('Curfew / Ordinance Notice', this)" class="category-card cursor-pointer border-2 border-slate-200 hover:border-slate-300 bg-white rounded-2xl p-3 text-center transition flex flex-col items-center justify-between relative min-h-[90px] select-none">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm mb-1">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-600 leading-tight">Curfew / Ordinance</span>
                    </div>

                </div>
            </div>

            <!-- Section 3: Target Recipients -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">3. Target Recipients</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Choose who should receive this alert.</p>
                </div>

                <div class="space-y-3.5 text-xs">
                    <!-- Option 1: All Residents -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="targetRecipient" value="All Residents" checked onchange="toggleRecipientType(this.value)" class="w-4 h-4 text-[#0f53d1] border-slate-300 focus:ring-[#0f53d1]/40 mt-0.5 cursor-pointer">
                        <div>
                            <span class="font-bold text-slate-900 block">All Residents</span>
                            <span class="text-[11px] text-slate-400 font-medium">Send to all registered residents in the barangay</span>
                        </div>
                    </label>

                    <!-- Option 2: Specific District & Barangay -->
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="radio" name="targetRecipient" value="Specific District" onchange="toggleRecipientType(this.value)" class="w-4 h-4 text-[#0f53d1] border-slate-300 focus:ring-[#0f53d1]/40 mt-0.5 cursor-pointer">
                            <div>
                                <span class="font-bold text-slate-900 block">Specific District & Barangay</span>
                            </div>
                        </label>
                        <div class="ml-7 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <select id="districtSelect" disabled onchange="onDistrictSelectChange()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl p-2.5 outline-none disabled:opacity-50 text-xs cursor-pointer">
                                <option value="">Select District</option>
                                <option value="District 1">District 1</option>
                                <option value="District 2">District 2</option>
                                <option value="District 3">District 3</option>
                            </select>
                            <select id="barangaySelect" disabled onchange="updatePreviewSummary()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl p-2.5 outline-none disabled:opacity-50 text-xs cursor-pointer">
                                <option value="">Select Barangay</option>
                            </select>
                        </div>
                    </div>

                    <!-- Option 3: Specific Group -->
                    <div class="space-y-2">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="radio" name="targetRecipient" value="Specific Group" onchange="toggleRecipientType(this.value)" class="w-4 h-4 text-[#0f53d1] border-slate-300 focus:ring-[#0f53d1]/40 mt-0.5 cursor-pointer">
                            <div>
                                <span class="font-bold text-slate-900 block">Specific Group</span>
                            </div>
                        </label>
                        <div class="ml-7 space-y-1">
                            <select id="groupSelect" disabled onchange="updatePreviewSummary()" class="w-full bg-slate-50 border border-slate-200 text-slate-800 font-medium rounded-xl p-2.5 outline-none disabled:opacity-50 text-xs cursor-pointer">
                                <option value="">Select group</option>
                                <option value="Senior Citizens">Senior Citizens</option>
                                <option value="PWDs">PWDs</option>
                                <option value="Voters">Registered Voters</option>
                                <option value="Youth">Youth (15-24 yrs)</option>
                                <option value="Solo Parents">Solo Parents</option>
                            </select>
                            <span class="text-[10px] text-slate-400 font-normal block">Examples: Senior Citizens, PWDs, Voters, Youth, etc.</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Section 5: Schedule Send -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">5. Schedule Send</h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Choose when to send this alert.</p>
                </div>

                <div class="space-y-3.5 text-xs">
                    <!-- Option 1: Send Immediately -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="scheduleSend" value="Send Immediately" checked onchange="toggleScheduleType(this.value)" class="w-4 h-4 text-[#0f53d1] border-slate-300 focus:ring-[#0f53d1]/40 mt-0.5 cursor-pointer">
                        <div>
                            <span class="font-bold text-slate-900 block">Send Immediately</span>
                            <span class="text-[11px] text-slate-400 font-medium">Alert will be sent as soon as you confirm.</span>
                        </div>
                    </label>

                    <!-- Option 2: Schedule for Later -->
                    <div class="space-y-2.5">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="radio" name="scheduleSend" value="Schedule for Later" onchange="toggleScheduleType(this.value)" class="w-4 h-4 text-[#0f53d1] border-slate-300 focus:ring-[#0f53d1]/40 mt-0.5 cursor-pointer">
                            <div>
                                <span class="font-bold text-slate-900 block">Schedule for Later</span>
                            </div>
                        </label>

                        <div id="schedulePickers" class="ml-7 grid grid-cols-2 gap-3 opacity-50 pointer-events-none transition-all">
                            <input type="date" id="scheduleDateInput" value="2025-06-08" onchange="updatePreviewSummary()" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl p-2.5 outline-none cursor-pointer">
                            <input type="time" id="scheduleTimeInput" value="10:00" onchange="updatePreviewSummary()" class="w-full bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl p-2.5 outline-none cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Section 6: Preview Summary & Sticky Action Footer -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky bottom-4 z-40">
        
        <!-- Summary Chips Row -->
        <div class="space-y-2">
            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Preview Summary</h3>
            <div class="flex items-center gap-4 flex-wrap text-xs font-bold text-slate-800">
                <!-- Category Chip -->
                <div class="flex items-center gap-2">
                    <div id="summaryCategoryIconContainer" class="w-6 h-6 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-xs">
                        <i id="summaryCategoryIcon" class="fa-solid fa-bullhorn"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block leading-none">Category</span>
                        <span id="summaryCategory" class="text-slate-900 text-xs font-bold">General Announcement</span>
                    </div>
                </div>

                <div class="w-px h-6 bg-slate-200"></div>

                <!-- Recipients Chip -->
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center text-xs">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block leading-none">Recipients</span>
                        <span id="summaryRecipients" class="text-slate-900 text-xs font-bold">All Residents</span>
                    </div>
                </div>

                <div class="w-px h-6 bg-slate-200"></div>

                <!-- Channels Chip -->
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-tower-cell"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block leading-none">Channels</span>
                        <span id="summaryChannels" class="text-slate-900 text-xs font-bold">SMS, In-App</span>
                    </div>
                </div>

                <div class="w-px h-6 bg-slate-200"></div>

                <!-- Schedule Chip -->
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block leading-none">Schedule</span>
                        <span id="summarySchedule" class="text-slate-900 text-xs font-bold">Send Immediately</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" onclick="saveAlertDraft()" class="px-4 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer shadow-xs flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk text-slate-400"></i>
                <span>Save as Draft</span>
            </button>

            <button type="button" id="btnReviewSend" disabled onclick="reviewAndSendAlert()" class="px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] rounded-xl transition shadow-xs flex items-center gap-2 opacity-50 cursor-not-allowed">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Review & Send</span>
            </button>
        </div>

    </div>

</main>

<!-- Review Alert Before Sending Overlay Modal -->
<div id="reviewAlertModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[9999] flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-4xl w-full p-6 space-y-5 my-auto max-h-[92vh] flex flex-col justify-between custom-scrollbar">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">Review Alert Before Sending</h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Please review all details below before sending this alert.</p>
            </div>
            <button onclick="closeReviewModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body Grid (2 Columns: Left Alert Summary, Right Message Preview) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 overflow-y-auto custom-scrollbar p-1">
            
            <!-- Left Column: ALERT SUMMARY (5 Cols) -->
            <div class="md:col-span-5 space-y-4 border-r border-slate-100 pr-0 md:pr-4">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ALERT SUMMARY</h3>

                <!-- Title -->
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-solid fa-heading text-slate-400"></i> Title</span>
                    <p id="modalSummaryTitle" class="text-xs font-bold text-slate-900 leading-snug">Dengue Prevention Week Advisory</p>
                </div>

                <!-- Category -->
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i id="modalSummaryCategoryIcon" class="fa-solid fa-bullhorn text-[#0f53d1]"></i> Category</span>
                    <p id="modalSummaryCategory" class="text-xs font-bold text-slate-900">General Announcement</p>
                </div>

                <!-- Recipients -->
                <div class="space-y-1.5">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-solid fa-users text-slate-400"></i> Recipients</span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span id="modalSummaryRecipients" class="text-xs font-bold text-slate-900">All Residents</span>
                        <span id="modalRecipientsBadge" class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">2,450 recipients</span>
                    </div>
                </div>

                <!-- Delivery Channels -->
                <div class="space-y-1.5">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-solid fa-tower-cell text-slate-400"></i> Delivery Channels</span>
                    <div id="modalSummaryChannels" class="flex items-center gap-3 text-xs font-bold text-slate-800 flex-wrap">
                        <span class="flex items-center gap-1 text-emerald-600"><i class="fa-solid fa-comment-dots text-sm"></i> SMS</span>
                        <span class="flex items-center gap-1 text-[#0f53d1]"><i class="fa-solid fa-bell text-sm"></i> In-App / Push</span>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-regular fa-clock text-slate-400"></i> Schedule</span>
                    <p id="modalSummarySchedule" class="text-xs font-bold text-slate-900">Send Immediately</p>
                </div>

                <!-- Attachments -->
                <div class="space-y-1.5">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-solid fa-paperclip text-slate-400"></i> Attachments</span>
                    <div id="modalAttachmentContainer">
                        <p class="text-xs font-bold text-slate-400 italic">None attached</p>
                    </div>
                </div>

                <!-- Created By -->
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-regular fa-user text-slate-400"></i> Created By</span>
                    <p class="text-xs font-bold text-slate-900">Juan Dela Cruz <span class="text-slate-400 font-normal">(Barangay Admin)</span></p>
                </div>

                <!-- Date Created -->
                <div class="space-y-1">
                    <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5"><i class="fa-regular fa-calendar text-slate-400"></i> Date Created</span>
                    <p id="modalDateCreated" class="text-xs font-bold text-slate-900">June 8, 2025 &bull; 9:42 AM</p>
                </div>
            </div>

            <!-- Right Column: MESSAGE PREVIEW (7 Cols) -->
            <div class="md:col-span-7 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">MESSAGE PREVIEW</h3>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl text-xs font-bold text-slate-600">
                    <button onclick="switchPreviewTab('inapp')" id="tabInApp" class="flex-1 py-1.5 text-center rounded-lg bg-white text-[#0f53d1] shadow-xs transition cursor-pointer">In-App Preview</button>
                    <button onclick="switchPreviewTab('sms')" id="tabSms" class="flex-1 py-1.5 text-center rounded-lg hover:text-slate-900 transition cursor-pointer">SMS Preview</button>
                    <button onclick="switchPreviewTab('email')" id="tabEmail" class="flex-1 py-1.5 text-center rounded-lg hover:text-slate-900 transition cursor-pointer">Email Preview</button>
                </div>

                <!-- Preview Tab Content 1: In-App Preview -->
                <div id="contentInApp" class="border border-slate-200 rounded-2xl p-4 space-y-3 bg-white shadow-xs">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <i id="previewModalCategoryIcon" class="fa-solid fa-bullhorn text-[#0f53d1] text-xs"></i>
                            <span id="previewModalCategoryBadge" class="text-xs font-bold text-[#0f53d1]">General Announcement</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-medium">Just now</span>
                    </div>

                    <h4 id="previewModalTitle" class="text-sm font-black text-slate-900 leading-snug">Dengue Prevention Week Advisory</h4>
                    
                    <div id="previewModalBody" class="text-xs text-slate-700 font-medium leading-relaxed space-y-2 whitespace-pre-line">
                        Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang:
                        &bull; Tanggalin ang pangatlong tubig sa paligid
                        &bull; Takpan ang imbakan ng tubig
                        &bull; Linisin ang paligid ng inyong tahanan

                        Kung may sintomas tulad ng lagnat, sakit ng ulo, pananakit ng katawan, o pantal, kumonsulta agad sa pinakamalapit na health center.
                        
                        Maraming salamat sa inyong pakikiisa!
                    </div>

                    <!-- Image Graphic Banner -->
                    <div id="modalGraphicBannerContainer" class="hidden rounded-xl overflow-hidden border border-slate-200 bg-slate-100 max-h-48 flex items-center justify-center"></div>
                </div>

                <!-- Preview Tab Content 2: SMS Preview (Hidden by default) -->
                <div id="contentSms" class="hidden border border-slate-200 rounded-2xl p-4 space-y-3 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">SMS Text Message Preview</div>
                    <div class="p-3.5 bg-emerald-500 text-white rounded-2xl rounded-tl-none max-w-sm text-xs font-medium space-y-1.5 shadow-sm">
                        <p id="smsPreviewTitle" class="font-bold border-b border-emerald-400 pb-1">[BARANGAY ALERT] Dengue Prevention Week Advisory</p>
                        <p id="smsPreviewBody" class="leading-relaxed text-[11px] whitespace-pre-line">Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang: Linisin ang paligid ng inyong tahanan.</p>
                        <p class="text-[9px] text-emerald-100 text-right font-semibold">Just now &bull; Delivered</p>
                    </div>
                </div>

                <!-- Preview Tab Content 3: Email Preview (Hidden by default) -->
                <div id="contentEmail" class="hidden border border-slate-200 rounded-2xl p-4 space-y-3 bg-white">
                    <div class="border-b border-slate-100 pb-2 text-xs">
                        <p class="text-slate-500 font-medium"><span class="font-bold text-slate-700">From:</span> Barangay Notice System &lt;no-reply@lgu.gov.ph&gt;</p>
                        <p id="emailSubjectLine" class="text-slate-900 font-bold mt-1"><span class="text-slate-500 font-medium">Subject:</span> [OFFICIAL ADVISORY] Dengue Prevention Week Advisory</p>
                    </div>
                    <div id="emailPreviewContent" class="text-xs text-slate-700 font-medium leading-relaxed space-y-2 p-2 whitespace-pre-line">
                        Mag-ingat sa dengue! Upang maiwasan ang pagkalat ng sakit, sundin ang mga simpleng hakbang...
                    </div>
                </div>

            </div>

        </div>

        <!-- Warning Callout Banner -->
        <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-2.5 text-xs text-amber-800 font-medium">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm shrink-0"></i>
            <span>Please ensure that all information is correct. Once sent, this alert will be delivered to the selected recipients.</span>
        </div>

        <!-- Modal Action Footer Buttons -->
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <button onclick="closeReviewModal()" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-slate-400"></i>
                <span>Edit Alert</span>
            </button>

            <button onclick="confirmSendAlertNow()" class="px-6 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Send Alert Now</span>
            </button>
        </div>

    </div>
</div>

<script>
let selectedCategory = 'General Announcement';

const categoryDetailsMap = {
    'Emergency': { icon: 'fa-triangle-exclamation', colorClass: 'text-rose-500', bgClass: 'bg-rose-50' },
    'Health Advisory': { icon: 'fa-heart-pulse', colorClass: 'text-amber-500', bgClass: 'bg-amber-50' },
    'Event': { icon: 'fa-calendar-days', colorClass: 'text-purple-600', bgClass: 'bg-purple-50' },
    'General Announcement': { icon: 'fa-bullhorn', colorClass: 'text-[#0f53d1]', bgClass: 'bg-blue-50' },
    'Curfew / Ordinance Notice': { icon: 'fa-shield-halved', colorClass: 'text-emerald-600', bgClass: 'bg-emerald-50' }
};

function updatePreviewSummary() {
    // 1. Category
    const summaryCatText = document.getElementById('summaryCategory');
    const summaryCatIconContainer = document.getElementById('summaryCategoryIconContainer');
    const summaryCatIcon = document.getElementById('summaryCategoryIcon');
    
    const catConfig = categoryDetailsMap[selectedCategory] || categoryDetailsMap['General Announcement'];
    
    if (summaryCatText) summaryCatText.innerText = selectedCategory;
    if (summaryCatIconContainer) {
        summaryCatIconContainer.className = `w-6 h-6 rounded-lg ${catConfig.bgClass} ${catConfig.colorClass} flex items-center justify-center text-xs`;
    }
    if (summaryCatIcon) {
        summaryCatIcon.className = `fa-solid ${catConfig.icon}`;
    }

    // 2. Recipients
    const recipientRadio = document.querySelector('input[name="targetRecipient"]:checked');
    const summaryRecipText = document.getElementById('summaryRecipients');
    if (summaryRecipText && recipientRadio) {
        const val = recipientRadio.value;
        if (val === 'All Residents') {
            summaryRecipText.innerText = 'All Residents';
        } else if (val === 'Specific District') {
            const dist = document.getElementById('districtSelect').value;
            const brgy = document.getElementById('barangaySelect').value;
            if (brgy) {
                summaryRecipText.innerText = `${brgy} (${dist || 'District'})`;
            } else if (dist) {
                summaryRecipText.innerText = `All in ${dist}`;
            } else {
                summaryRecipText.innerText = 'Specific District';
            }
        } else if (val === 'Specific Group') {
            const grp = document.getElementById('groupSelect').value;
            summaryRecipText.innerText = grp ? grp : 'Specific Group';
        }
    }

    // 3. Channels
    const channels = [];
    if (document.getElementById('channelSms')?.checked) channels.push('SMS');
    if (document.getElementById('channelPush')?.checked) channels.push('In-App');
    if (document.getElementById('channelEmail')?.checked) channels.push('Email');
    const summaryChannelsText = document.getElementById('summaryChannels');
    if (summaryChannelsText) {
        summaryChannelsText.innerText = channels.length > 0 ? channels.join(', ') : 'None selected';
    }

    // 4. Schedule
    const schedRadio = document.querySelector('input[name="scheduleSend"]:checked');
    const summarySchedText = document.getElementById('summarySchedule');
    if (summarySchedText && schedRadio) {
        if (schedRadio.value === 'Send Immediately') {
            summarySchedText.innerText = 'Send Immediately';
        } else {
            const dateVal = document.getElementById('scheduleDateInput').value;
            const timeVal = document.getElementById('scheduleTimeInput').value;
            summarySchedText.innerText = dateVal && timeVal ? `${dateVal} ${timeVal}` : 'Scheduled';
        }
    }
}

function selectCategory(categoryName, element) {
    selectedCategory = categoryName;
    document.querySelectorAll('.category-card').forEach(card => {
        card.classList.remove('border-[#0f53d1]', 'bg-blue-50/20', 'active-category');
        card.classList.add('border-slate-200');
        const badge = card.querySelector('.check-badge');
        if (badge) badge.remove();
    });

    element.classList.remove('border-slate-200');
    element.classList.add('border-[#0f53d1]', 'bg-blue-50/20', 'active-category');
    
    const checkBadge = document.createElement('div');
    checkBadge.className = 'absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-[#0f53d1] text-white flex items-center justify-center text-[9px] shadow-xs check-badge';
    checkBadge.innerHTML = '<i class="fa-solid fa-check"></i>';
    element.appendChild(checkBadge);

    updatePreviewSummary();
}

const districtBarangaysMap = {
    'District 1': [1, 2, 3, 4, 77, 78, 79, 80, 81, 82, 83, 84, 85, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177],
    'District 2': [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131],
    'District 3': [178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188]
};

function onDistrictSelectChange() {
    const dist = document.getElementById('districtSelect').value;
    const brgySelect = document.getElementById('barangaySelect');
    brgySelect.innerHTML = '<option value="">All Barangays in District</option>';
    
    if (dist && districtBarangaysMap[dist]) {
        districtBarangaysMap[dist].forEach(num => {
            const opt = document.createElement('option');
            const bName = `Barangay ${num}`;
            opt.value = bName;
            opt.innerText = bName;
            brgySelect.appendChild(opt);
        });
    } else {
        Object.values(districtBarangaysMap).flat().forEach(num => {
            const opt = document.createElement('option');
            const bName = `Barangay ${num}`;
            opt.value = bName;
            opt.innerText = bName;
            brgySelect.appendChild(opt);
        });
    }
    updatePreviewSummary();
}

function toggleRecipientType(type) {
    const districtSelect = document.getElementById('districtSelect');
    const barangaySelect = document.getElementById('barangaySelect');
    const groupSelect = document.getElementById('groupSelect');

    if (type === 'Specific District') {
        districtSelect.disabled = false;
        barangaySelect.disabled = false;
        groupSelect.disabled = true;
        if (!barangaySelect.options.length || barangaySelect.options.length <= 1) {
            onDistrictSelectChange();
        }
    } else if (type === 'Specific Group') {
        districtSelect.disabled = true;
        barangaySelect.disabled = true;
        groupSelect.disabled = false;
    } else {
        districtSelect.disabled = true;
        barangaySelect.disabled = true;
        groupSelect.disabled = true;
    }

    updatePreviewSummary();
}

function toggleScheduleType(type) {
    const schedulePickers = document.getElementById('schedulePickers');
    if (type === 'Schedule for Later') {
        schedulePickers.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        schedulePickers.classList.add('opacity-50', 'pointer-events-none');
    }

    updatePreviewSummary();
}

function getAlertBodyHTML() {
    const editor = document.getElementById('alertBodyInput');
    return editor ? editor.innerHTML.trim() : '';
}

function getAlertBodyText() {
    const editor = document.getElementById('alertBodyInput');
    return editor ? (editor.innerText || editor.textContent).trim() : '';
}

function validateFormInputs() {
    const titleVal = (document.getElementById('alertTitleInput').value || '').trim();
    const bodyVal = getAlertBodyText();
    const btn = document.getElementById('btnReviewSend');

    if (!btn) return;

    if (titleVal.length > 0 && bodyVal.length > 0) {
        btn.disabled = false;
        btn.className = "px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] hover:bg-[#0d46b0] rounded-xl transition shadow-xs flex items-center gap-2 cursor-pointer";
    } else {
        btn.disabled = true;
        btn.className = "px-5 py-2.5 text-xs font-bold text-white bg-[#0f53d1] rounded-xl transition shadow-xs flex items-center gap-2 opacity-50 cursor-not-allowed";
    }
}

document.addEventListener('DOMContentLoaded', validateFormInputs);

function updateCharCount() {
    const bodyText = getAlertBodyText();
    document.getElementById('charCounter').innerText = bodyText.length;
    validateFormInputs();
}

function formatText(command, value = null) {
    const editor = document.getElementById('alertBodyInput');
    if (editor) editor.focus();
    document.execCommand(command, false, value);
    updateToolbarActiveState();
    updateCharCount();
}

function formatHeading(val) {
    const editor = document.getElementById('alertBodyInput');
    if (editor) editor.focus();
    if (val === 'Heading 1') {
        document.execCommand('formatBlock', false, '<h1>');
    } else if (val === 'Heading 2') {
        document.execCommand('formatBlock', false, '<h2>');
    } else {
        document.execCommand('formatBlock', false, '<p>');
    }
    updateToolbarActiveState();
    updateCharCount();
}

function formatLink() {
    const editor = document.getElementById('alertBodyInput');
    if (editor) editor.focus();
    const url = prompt('Enter link URL (e.g. https://example.gov.ph):', 'https://');
    if (url && url !== 'https://') {
        document.execCommand('createLink', false, url);
    }
    updateToolbarActiveState();
    updateCharCount();
}

function updateToolbarActiveState() {
    const btnBold = document.getElementById('btnBold');
    const btnItalic = document.getElementById('btnItalic');
    const btnUnderline = document.getElementById('btnUnderline');
    const btnBulletList = document.getElementById('btnBulletList');
    const btnNumberedList = document.getElementById('btnNumberedList');
    const btnAlignLeft = document.getElementById('btnAlignLeft');
    const btnAlignCenter = document.getElementById('btnAlignCenter');

    const toggleBtnState = (btn, command) => {
        if (!btn) return;
        try {
            const isActive = document.queryCommandState(command);
            if (isActive) {
                btn.classList.add('bg-blue-100', 'text-[#0f53d1]', 'border', 'border-[#0f53d1]/40', 'font-black');
                btn.classList.remove('text-slate-600', 'hover:bg-slate-200');
            } else {
                btn.classList.remove('bg-blue-100', 'text-[#0f53d1]', 'border', 'border-[#0f53d1]/40', 'font-black');
                btn.classList.add('text-slate-600', 'hover:bg-slate-200');
            }
        } catch (e) {}
    };

    toggleBtnState(btnBold, 'bold');
    toggleBtnState(btnItalic, 'italic');
    toggleBtnState(btnUnderline, 'underline');
    toggleBtnState(btnBulletList, 'insertUnorderedList');
    toggleBtnState(btnNumberedList, 'insertOrderedList');
    toggleBtnState(btnAlignLeft, 'justifyLeft');
    toggleBtnState(btnAlignCenter, 'justifyCenter');
}

let uploadedFileObject = null;
let uploadedImageDataUrl = null;

function triggerFileSelect() {
    document.getElementById('fileUploadInput').click();
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('fileDropzone');
    if (dropzone) dropzone.classList.add('border-[#0f53d1]', 'bg-blue-50/40');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('fileDropzone');
    if (dropzone) dropzone.classList.remove('border-[#0f53d1]', 'bg-blue-50/40');
}

function handleFileDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('fileDropzone');
    if (dropzone) dropzone.classList.remove('border-[#0f53d1]', 'bg-blue-50/40');

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        processUploadedFile(e.dataTransfer.files[0]);
    }
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        processUploadedFile(input.files[0]);
    }
}

function processUploadedFile(file) {
    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit. Please select a smaller file (Max 5MB).');
        return;
    }

    uploadedFileObject = file;
    document.getElementById('fileNameDisplay').innerText = file.name;
    document.getElementById('fileSizeDisplay').innerText = (file.size / (1024 * 1024)).toFixed(2) + ' MB';

    const defaultState = document.getElementById('uploadDefaultState');
    const successState = document.getElementById('uploadSuccessState');
    const imgPreview = document.getElementById('filePreviewImage');
    const typeIcon = document.getElementById('fileTypeIcon');

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            uploadedImageDataUrl = e.target.result;
            imgPreview.src = uploadedImageDataUrl;
            imgPreview.classList.remove('hidden');
            typeIcon.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        uploadedImageDataUrl = null;
        imgPreview.classList.add('hidden');
        typeIcon.classList.remove('hidden');
        typeIcon.className = 'fa-solid fa-file-pdf text-sm text-rose-500';
    }

    defaultState.classList.add('hidden');
    successState.classList.remove('hidden');
    updatePreviewSummary();
}

function removeAttachedFile(e) {
    if (e) e.stopPropagation();
    uploadedFileObject = null;
    uploadedImageDataUrl = null;
    document.getElementById('fileUploadInput').value = '';
    
    document.getElementById('uploadDefaultState').classList.remove('hidden');
    document.getElementById('uploadSuccessState').classList.add('hidden');
    updatePreviewSummary();
}

function reviewAndSendAlert() {
    const title = (document.getElementById('alertTitleInput').value || '').trim();
    const bodyHTML = getAlertBodyHTML();
    const bodyText = getAlertBodyText();

    if (!title || !bodyText) {
        alert('Please fill out both the Message Title and Message Body before proceeding to review.');
        return;
    }

    // Populate modal summary fields
    document.getElementById('modalSummaryTitle').innerText = title;
    document.getElementById('modalSummaryCategory').innerText = selectedCategory;
    
    const catConfig = categoryDetailsMap[selectedCategory] || categoryDetailsMap['General Announcement'];
    
    const modalCatIcon = document.getElementById('modalSummaryCategoryIcon');
    if (modalCatIcon) modalCatIcon.className = `fa-solid ${catConfig.icon} ${catConfig.colorClass}`;

    const previewModalCatBadge = document.getElementById('previewModalCategoryBadge');
    if (previewModalCatBadge) {
        previewModalCatBadge.innerText = selectedCategory;
        previewModalCatBadge.className = `text-xs font-bold ${catConfig.colorClass}`;
    }

    const previewModalCatIcon = document.getElementById('previewModalCategoryIcon');
    if (previewModalCatIcon) {
        previewModalCatIcon.className = `fa-solid ${catConfig.icon} ${catConfig.colorClass} text-xs`;
    }

    document.getElementById('modalSummaryRecipients').innerText = document.getElementById('summaryRecipients').innerText;

    // Delivery channels summary in modal
    const channelsContainer = document.getElementById('modalSummaryChannels');
    channelsContainer.innerHTML = '';
    if (document.getElementById('channelSms').checked) {
        channelsContainer.innerHTML += '<span class="flex items-center gap-1 text-emerald-600"><i class="fa-solid fa-comment-dots text-sm"></i> SMS</span>';
    }
    if (document.getElementById('channelPush').checked) {
        channelsContainer.innerHTML += '<span class="flex items-center gap-1 text-[#0f53d1]"><i class="fa-solid fa-bell text-sm"></i> In-App / Push</span>';
    }
    if (document.getElementById('channelEmail').checked) {
        channelsContainer.innerHTML += '<span class="flex items-center gap-1 text-purple-600"><i class="fa-solid fa-envelope text-sm"></i> Email</span>';
    }

    document.getElementById('modalSummarySchedule').innerText = document.getElementById('summarySchedule').innerText;

    // File Attachment display in modal
    const attachmentContainer = document.getElementById('modalAttachmentContainer');
    if (uploadedFileObject) {
        const isImg = uploadedFileObject.type.startsWith('image/');
        attachmentContainer.innerHTML = `
            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0f53d1] flex items-center justify-center shrink-0 text-sm border border-blue-100">
                        <i class="fa-regular ${isImg ? 'fa-image' : 'fa-file-pdf text-rose-500'}"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold text-slate-800 truncate">${uploadedFileObject.name}</p>
                        <p class="text-[9px] text-slate-400 font-semibold">${(uploadedFileObject.size / (1024 * 1024)).toFixed(2)} MB</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-[10px] border border-emerald-200">Attached</span>
            </div>
        `;
    } else {
        attachmentContainer.innerHTML = `<p class="text-xs font-bold text-slate-400 italic">None attached</p>`;
    }

    // Modal In-App Graphic Banner (Image attached vs None)
    const bannerContainer = document.getElementById('modalGraphicBannerContainer');
    if (uploadedImageDataUrl) {
        bannerContainer.className = 'rounded-xl overflow-hidden border border-slate-200 bg-slate-100 max-h-48 flex items-center justify-center';
        bannerContainer.innerHTML = `<img src="${uploadedImageDataUrl}" alt="Attached Graphic" class="w-full h-full object-cover max-h-48">`;
    } else {
        bannerContainer.className = 'hidden';
        bannerContainer.innerHTML = '';
    }

    // Date Created
    const now = new Date();
    document.getElementById('modalDateCreated').innerText = `${now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} • ${now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;

    // Preview tab content values
    document.getElementById('previewModalTitle').innerText = title;
    document.getElementById('previewModalBody').innerHTML = bodyHTML;

    document.getElementById('smsPreviewTitle').innerText = `[BARANGAY ALERT] ${title}`;
    document.getElementById('smsPreviewBody').innerText = bodyText;

    document.getElementById('emailSubjectLine').innerHTML = `<span class="text-slate-500 font-medium">Subject:</span> [OFFICIAL ADVISORY] ${title}`;
    document.getElementById('emailPreviewContent').innerHTML = bodyHTML;

    // Show Modal
    document.getElementById('reviewAlertModal').classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewAlertModal').classList.add('hidden');
}

function switchPreviewTab(tab) {
    const tabInApp = document.getElementById('tabInApp');
    const tabSms = document.getElementById('tabSms');
    const tabEmail = document.getElementById('tabEmail');

    const contentInApp = document.getElementById('contentInApp');
    const contentSms = document.getElementById('contentSms');
    const contentEmail = document.getElementById('contentEmail');

    // Reset styles
    [tabInApp, tabSms, tabEmail].forEach(t => {
        t.className = 'flex-1 py-1.5 text-center rounded-lg hover:text-slate-900 transition cursor-pointer text-slate-600';
    });

    [contentInApp, contentSms, contentEmail].forEach(c => c.classList.add('hidden'));

    if (tab === 'inapp') {
        tabInApp.className = 'flex-1 py-1.5 text-center rounded-lg bg-white text-[#0f53d1] shadow-xs font-bold transition cursor-pointer';
        contentInApp.classList.remove('hidden');
    } else if (tab === 'sms') {
        tabSms.className = 'flex-1 py-1.5 text-center rounded-lg bg-white text-emerald-600 shadow-xs font-bold transition cursor-pointer';
        contentSms.classList.remove('hidden');
    } else if (tab === 'email') {
        tabEmail.className = 'flex-1 py-1.5 text-center rounded-lg bg-white text-purple-600 shadow-xs font-bold transition cursor-pointer';
        contentEmail.classList.remove('hidden');
    }
}

function confirmSendAlertNow() {
    const title = document.getElementById('modalSummaryTitle').innerText;
    alert(`Success! Alert "${title}" has been transmitted to all selected recipients.`);
    closeReviewModal();
    window.location.href = '<?php echo $basePath; ?>pages/notifications-alerts/broadcast-history.php';
}
</script>

<?php include '../../includes/footer.php'; ?>
