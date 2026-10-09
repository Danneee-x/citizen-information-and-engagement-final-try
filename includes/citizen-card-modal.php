<?php
/**
 * Civentral Official Citizen ID Card Modal Component
 * Authentic Philippine Municipal PVC Government Smart Card (CR80 Standard)
 * Full Dual-Sided Implementation:
 * - Front Side: Formal 1x1 Photo, Signature, Control Number, Demographics Grid, Address, QR & Barcode
 * - Back Side: Emergency Contact Box, Caloocan 24/7 Hotline Directory, Legal Terms, Mayor Signature
 * - Interactive Flip Tabs (Front Side / Back Side)
 * - Single-Page Print Layout with Cutting/Folding Guide for PVC Card Printing
 */
?>

<!-- QR Code Standalone Library Loading with Fallbacks -->
<script src="../../assets/js/qrcode.min.js"></script>
<script>
if (typeof QRCode === 'undefined') {
    document.write('<script src="../assets/js/qrcode.min.js"><\/script>');
}
if (typeof QRCode === 'undefined') {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
}
</script>

<!-- Citizen Card Modal Container -->
<div id="citizenCardModal" class="hidden fixed inset-0 z-[99999] overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-5">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-auto" onclick="event.stopPropagation()">
        
        <!-- Modal Top Bar -->
        <div class="px-5 py-3.5 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#0F4C81] to-sky-500 p-0.5 shadow-sm flex items-center justify-center">
                    <img src="../../assets/images/logo.png" onerror="this.src='../assets/images/logo.png'; this.onerror=null;" class="w-7 h-7 object-contain drop-shadow" alt="Civentral Logo" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="cardModalTopTitle" class="text-sm font-black text-white tracking-wide">
                            Civentral Citizen Card
                        </h3>
                        <span id="cardModalCategoryBadge" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-sky-500/20 text-sky-300 border border-sky-400/30">
                            OFFICIAL RESIDENT
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium">Official Municipal Credential &bull; City of Caloocan</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" onclick="printCitizenCard()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-[#0F4C81] hover:bg-sky-700 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-xs border border-sky-400/30">
                    <i class="fa-solid fa-print text-[11px]"></i>
                    <span class="hidden sm:inline">Print / Save PDF</span>
                </button>
                <button type="button" id="closeCitizenCardModalBtn" onclick="closeCitizenCardModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition flex items-center justify-center cursor-pointer border border-slate-700" title="Close Modal">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="p-5 sm:p-6 bg-slate-100/70 space-y-4">

            <!-- CARD SIDE SELECTOR TABS (Interactive Flip Controls) -->
            <div class="flex items-center justify-center p-1 bg-slate-200/90 rounded-2xl max-w-sm sm:max-w-md mx-auto border border-slate-300 shadow-inner">
                <button type="button" id="tabCardFront" onclick="switchCardSide('front')" class="flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer bg-white text-[#0F4C81] shadow-xs">
                    <i class="fa-solid fa-id-card"></i>
                    <span>FRONT SIDE (IDENTITY)</span>
                </button>
                <button type="button" id="tabCardBack" onclick="switchCardSide('back')" class="flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>BACK SIDE (EMERGENCY)</span>
                </button>
            </div>

            <!-- TAP TO FLIP NOTICE PROMPT -->
            <div class="text-center">
                <button type="button" onclick="toggleCardSide()" class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-sky-50 text-sky-700 hover:bg-sky-100 text-[11px] font-bold border border-sky-200 transition cursor-pointer">
                    <i class="fa-solid fa-arrow-right-arrow-left text-[10px]"></i>
                    <span id="cardFlipPromptText">Viewing Front Side &bull; Click card or tab to view Back Side</span>
                </button>
            </div>

            <!-- THE OFFICIAL CR80 CITIZEN SMART CARD PRINT & DISPLAY CONTAINER -->
            <div id="printContainer" class="flex flex-col items-center justify-center">

                <!-- ========================================================= -->
                <!-- FRONT SIDE: OFFICIAL PVC CITIZEN SMART CARD (CR80)        -->
                <!-- ========================================================= -->
                <div id="printableCitizenCard" onclick="toggleCardSide()" class="printable-card-side w-full max-w-[530px] rounded-2xl shadow-xl border border-slate-300 relative overflow-hidden select-none bg-white text-slate-900 transition-all cursor-pointer hover:shadow-2xl" style="aspect-ratio: 85.6/53.98; min-height: 335px;">
                    
                    <!-- Subtle Right Diagonal Wave Gradient -->
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(125deg, #FFFFFF 0%, #FFFFFF 52%, #F8FAFC 75%, #EDF2F7 100%);"></div>

                    <!-- Municipal Building Watermark Background -->
                    <img 
                        src="../../assets/images/building-bg.jpg" 
                        onerror="this.src='../assets/images/building-bg.jpg'; this.onerror=null;" 
                        style="position: absolute; right: 0; bottom: 0; width: 65%; height: 85%; object-fit: contain; opacity: 0.35; mix-blend-mode: multiply; filter: contrast(1.3) grayscale(20%); pointer-events: none; z-index: 1;" 
                        alt="Civic Building Watermark" 
                    />

                    <!-- Wavy Header Ribbon (100% Width Vector Ribbon with Gold Under-stripe) -->
                    <svg class="absolute top-0 left-0 w-full h-[90px] pointer-events-none z-0" viewBox="0 0 500 90" preserveAspectRatio="none">
                      <defs>
                        <linearGradient id="headerWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                          <stop id="cardRibbonStop1" offset="0%" stop-color="#881337" />
                          <stop id="cardRibbonStop2" offset="30%" stop-color="#991B1B" />
                          <stop id="cardRibbonStop3" offset="75%" stop-color="#DC2626" />
                          <stop id="cardRibbonStop4" offset="100%" stop-color="#B91C1C" />
                        </linearGradient>
                        <linearGradient id="goldWaveStripe" x1="0%" y1="0%" x2="100%" y2="0%">
                          <stop offset="0%" stop-color="#D97706" />
                          <stop offset="50%" stop-color="#FDE047" />
                          <stop offset="100%" stop-color="#D97706" />
                        </linearGradient>
                      </defs>
                      <path d="M 0,0 L 500,0 L 500,66 Q 370,78 250,72 T 0,86 Z" fill="url(#headerWaveGrad)" />
                      <path d="M 0,86 Q 130,72 250,72 T 500,66 L 500,70 Q 370,82 250,76 T 0,90 Z" fill="url(#goldWaveStripe)" />
                    </svg>

                    <!-- CARD FRONT CONTENT CONTAINER -->
                    <div class="relative z-10 px-3.5 pt-2 pb-3.5 sm:px-4 sm:pt-2.5 sm:pb-4 flex flex-col justify-between h-full" style="height: 100%;">
                        
                        <!-- TOP HEADER BLOCK -->
                        <div class="relative mb-1 -mt-0.5">
                            <div class="text-center w-full">
                                <span class="text-[7.5px] font-extrabold uppercase tracking-[2px] text-[#FEE2E2] leading-none block drop-shadow-xs">
                                    Republic of the Philippines
                                </span>
                            </div>

                            <div class="flex items-center -mt-0.5 pr-8">
                                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white p-0.5 shadow-md flex items-center justify-center shrink-0 border-[1.5px] border-amber-400 ml-1 -mt-1">
                                    <img src="../../assets/images/logo.png" onerror="this.src='../assets/images/logo.png'; this.onerror=null;" class="w-full h-full object-contain" alt="Civentral Seal" />
                                </div>

                                <div class="flex-1 flex flex-col items-center justify-center text-center">
                                    <h3 id="cardModalHeaderTitle" class="text-[12.5px] sm:text-[13.5px] font-black uppercase tracking-wider text-[#FFFFFF] drop-shadow-sm leading-tight">
                                        Civentral Citizen Card
                                    </h3>
                                    <p id="cardModalHeaderSubtitle" class="text-[7.5px] sm:text-[8px] font-black text-[#FDE047] uppercase tracking-[0.8px] leading-tight mt-0.5 drop-shadow-xs">
                                        Kasama Ka Sa Pag-Unlad &bull; City of Caloocan
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- THREE-COLUMN CARD BODY -->
                        <div class="grid grid-cols-12 gap-2.5 items-start flex-1">
                            
                            <!-- COLUMN 1: Photo, Signature, Classification (3 cols) -->
                            <div class="col-span-3 flex flex-col items-center justify-between h-full relative z-20">
                                <div class="flex flex-col items-center">
                                    <!-- 1x1 Photo Frame -->
                                    <div class="w-[82px] h-[82px] rounded-[4px] border border-[#CBD5E1] bg-slate-50 shadow-sm overflow-hidden flex items-center justify-center p-0.5 relative z-20">
                                        <img 
                                            id="cardModalPhoto" 
                                            src="" 
                                            class="w-full h-full object-cover rounded-[3px]" 
                                            alt="Citizen Photo" 
                                            onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Citizen&background=0F4C81&color=fff&size=200';" 
                                        />
                                    </div>

                                    <!-- Signature Box -->
                                    <div class="w-[82px] h-7 mt-1 bg-white border-b border-dashed border-slate-400 shadow-xs flex items-center justify-center px-1 overflow-hidden relative z-20">
                                        <img 
                                            id="cardModalSignature" 
                                            src="" 
                                            class="w-full h-full object-contain filter contrast-125" 
                                            alt="Cardholder Signature" 
                                            onerror="this.style.display='none'; const ph = document.getElementById('cardModalSigPlaceholder'); if (ph) ph.style.display='block';" 
                                        />
                                        <span id="cardModalSigPlaceholder" class="text-[7.5px] text-slate-400 italic hidden">Digital Signature</span>
                                    </div>
                                    <span class="text-[6.5px] font-semibold text-[#64748B] uppercase tracking-wider mt-0.5">Cardholder Signature</span>

                                    <!-- Classification Pill Badge -->
                                    <div id="cardModalClassificationBadge" class="mt-1 px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200">
                                        <span id="cardModalClassification" class="text-[8.5px] font-black text-[#0F172A] uppercase tracking-wider block text-center">
                                            OFFICIAL RESIDENT
                                        </span>
                                    </div>
                                </div>

                                <!-- Timestamp -->
                                <div class="w-full text-left mt-auto pt-0.5">
                                    <span id="cardModalTimestamp" class="text-[6px] font-mono text-slate-500 block leading-tight">
                                        2026/10/10 06:00:00 AM
                                    </span>
                                </div>
                            </div>

                            <!-- COLUMN 2: Center - Demographics & Address (6 cols) -->
                            <div class="col-span-6 flex flex-col justify-between h-full space-y-1 pl-0.5 relative z-10">
                                <div>
                                    <!-- ID / Control Number Row -->
                                    <div>
                                        <span class="text-[6.5px] font-bold uppercase tracking-wider text-[#64748B] block">
                                            ID / Control Number
                                        </span>
                                        <span id="cardModalControlNum" class="text-[11px] font-mono font-black text-[#0F4C81] block leading-tight tracking-wider">
                                            CAL-2026-000035
                                        </span>
                                    </div>

                                    <!-- Full Name Block -->
                                    <div class="mt-1">
                                        <span class="text-[6.5px] font-bold uppercase tracking-wider text-[#64748B] block">
                                            Name (Last Name, First Name, M.I.)
                                        </span>
                                        <h4 id="cardModalName" class="text-[13px] font-black text-[#0F172A] uppercase tracking-wide leading-tight truncate">
                                            ESPELITA, DANNY JR.
                                        </h4>
                                    </div>

                                    <!-- Demographics Grid (2 Rows x 3 Columns) -->
                                    <div class="grid grid-cols-3 gap-x-2 gap-y-1 pt-1.5 border-t border-slate-200 mt-1">
                                        <div>
                                            <span class="text-[6.5px] font-bold uppercase text-[#64748B] block">Sex</span>
                                            <span id="cardModalSex" class="text-[8.5px] font-black text-[#0F172A] block uppercase">M</span>
                                        </div>
                                        <div>
                                            <span class="text-[6.5px] font-bold uppercase text-[#64748B] block">Date of Birth</span>
                                            <span id="cardModalDob" class="text-[8.5px] font-black text-[#0F172A] block font-mono">1998/05/15</span>
                                        </div>
                                        <div>
                                            <span class="text-[6.5px] font-bold uppercase text-[#64748B] block">Civil Status</span>
                                            <span id="cardModalCivil" class="text-[8.5px] font-black text-[#0F172A] block uppercase">SINGLE</span>
                                        </div>

                                        <!-- Row 2 (Dynamic per Category) -->
                                        <div>
                                            <span id="cardModalCol1Label" class="text-[6.5px] font-bold uppercase text-[#64748B] block">Blood Type</span>
                                            <span id="cardModalCol1Value" class="text-[8.5px] font-black text-[#0F172A] block">N/A</span>
                                        </div>
                                        <div>
                                            <span id="cardModalCol2Label" class="text-[6.5px] font-bold uppercase text-[#64748B] block">Date Issued</span>
                                            <span id="cardModalCol2Value" class="text-[8.5px] font-black text-[#0F172A] block font-mono">2026/10/10</span>
                                        </div>
                                        <div>
                                            <span id="cardModalCol3Label" class="text-[6.5px] font-bold uppercase text-[#64748B] block">Valid Until</span>
                                            <span id="cardModalCol3Value" class="text-[8.5px] font-black text-[#0F172A] block font-mono">2031/10/10</span>
                                        </div>
                                    </div>

                                    <!-- Registered Address Block (2 Lines uppercase) -->
                                    <div class="pt-1.5 border-t border-slate-200 mt-1">
                                        <span class="text-[6.5px] font-bold uppercase tracking-wider text-[#64748B] block">
                                            Registered Address
                                        </span>
                                        <span id="cardModalAddressStreet" class="text-[8px] font-black text-[#0F172A] uppercase tracking-tight block leading-tight truncate">
                                            121 SAMPAGUITA ST., BARANGAY 171
                                        </span>
                                        <span id="cardModalAddressCity" class="text-[8px] font-black text-[#0F172A] uppercase tracking-tight block leading-tight">
                                            DISTRICT 2, CALOOCAN CITY
                                        </span>
                                    </div>
                                </div>

                                <!-- Emergency Contact Inline Footer -->
                                <div class="pt-1 border-t border-slate-200 mt-auto">
                                    <span class="text-[6.5px] text-[#64748B] block leading-tight">
                                        <strong class="font-bold text-[#475569]">Emergency Contact:</strong> 
                                        <span id="cardModalEmergency" class="font-bold text-[#0F172A]">Juan Basco (0917-123-4567)</span>
                                    </span>
                                </div>
                            </div>

                            <!-- COLUMN 3: Right - QR, Barcode, Verified Stamp (3 cols) -->
                            <div class="col-span-3 flex flex-col items-center justify-between h-full pl-0.5 relative z-20">
                                <div class="flex flex-col items-center w-full">
                                    <div class="w-[78px] h-[78px] bg-white border border-slate-200 rounded-[3px] shadow-sm flex items-center justify-center overflow-hidden p-0.5 relative z-20">
                                        <div id="cardModalQrBox" class="w-full h-full flex items-center justify-center p-0 m-0">
                                            <img id="cardModalQr" src="" class="w-full h-full object-contain" alt="QR Matrix" />
                                        </div>
                                    </div>

                                    <span id="cardModalBarcodeNum" class="text-[7.5px] font-mono font-bold text-slate-800 tracking-wider mt-1 block text-center">
                                        01002026000035
                                    </span>

                                    <div class="mt-1 flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8px]"></i>
                                        <span class="text-[7.5px] font-black tracking-wider text-emerald-700 uppercase">VERIFIED</span>
                                    </div>
                                </div>

                                <div class="w-full text-right mt-auto pt-0.5">
                                    <span class="text-[7.5px] font-mono font-bold text-slate-600 block leading-tight">00</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Print Cut Guide (Print-only) -->
                <div class="print-cut-guide hidden my-2 text-slate-400 font-mono text-[9px] tracking-wider text-center select-none">
                    - - - - - - - - - - - - - - - - - - - - - CUT HERE / FOLD LINE - - - - - - - - - - - - - - - - - - - - -
                </div>

                <!-- ========================================================= -->
                <!-- BACK SIDE: EMERGENCY & CITY DIRECTORY (CR80)              -->
                <!-- ========================================================= -->
                <div id="printableCardBack" onclick="toggleCardSide()" class="printable-card-side hidden w-full max-w-[530px] rounded-2xl shadow-xl border border-slate-300 relative overflow-hidden select-none bg-white text-slate-900 transition-all cursor-pointer hover:shadow-2xl" style="aspect-ratio: 85.6/53.98; min-height: 335px;">
                    
                    <!-- Subtle Right Diagonal Wave Gradient -->
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(125deg, #FFFFFF 0%, #FFFFFF 52%, #F8FAFC 75%, #EDF2F7 100%);"></div>

                    <!-- Municipal Building Watermark Background -->
                    <img 
                        src="../../assets/images/building-bg.jpg" 
                        onerror="this.src='../assets/images/building-bg.jpg'; this.onerror=null;" 
                        style="position: absolute; right: 0; bottom: 0; width: 65%; height: 85%; object-fit: contain; opacity: 0.15; mix-blend-mode: multiply; filter: contrast(1.2) grayscale(40%); pointer-events: none; z-index: 1;" 
                        alt="Civic Building Watermark" 
                    />

                    <!-- BACK SIDE HEADER RIBBON (Dark Slate with Gold Bottom Border) -->
                    <div class="bg-slate-900 text-white px-4 py-2 text-center border-b-2 border-amber-500 relative z-10 shadow-xs">
                        <h4 class="text-[8.5px] font-black uppercase tracking-wider text-white">
                            CITY GOVERNMENT OF CALOOCAN &bull; EMERGENCY &amp; RESIDENT RECORD
                        </h4>
                        <p class="text-[6.5px] font-bold text-slate-400 uppercase tracking-wide mt-0.5">
                            Official Municipal Smart Credential &bull; Kasama Ka Sa Pag-Unlad
                        </p>
                    </div>

                    <!-- BACK CARD INNER BODY -->
                    <div class="relative z-10 p-3 sm:p-3.5 flex flex-col justify-between h-[calc(100%-42px)] space-y-1.5" style="height: calc(100% - 42px);">
                        
                        <!-- SECTION 1: IN CASE OF EMERGENCY CARD -->
                        <div class="bg-rose-50/90 border border-rose-200 rounded-xl p-2 space-y-1">
                            <div class="flex items-center justify-between border-b border-rose-200/60 pb-1">
                                <div class="flex items-center gap-1.5 text-rose-700 font-extrabold text-[8px] uppercase tracking-wider">
                                    <i class="fa-solid fa-phone text-rose-600 text-[8px]"></i>
                                    <span>IN CASE OF EMERGENCY / ACCIDENT NOTIFY:</span>
                                </div>
                                <span class="px-1.5 py-0.2 rounded text-[7px] font-black bg-rose-600 text-white uppercase tracking-wider">
                                    PRIORITY DISPATCH
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between text-xs pt-0.5">
                                <div>
                                    <h5 id="cardModalBackEmgName" class="text-[9.5px] font-black text-slate-900 uppercase leading-tight">
                                        JUAN BASCO
                                    </h5>
                                    <span id="cardModalBackEmgRelation" class="text-[7.5px] font-bold text-slate-600 block">
                                        Relation: Immediate Family / Relative
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span id="cardModalBackEmgPhone" class="text-[10px] font-mono font-black text-rose-700 block">
                                        (02) 8366-3101
                                    </span>
                                    <span class="text-[6.5px] text-slate-500 font-semibold">Primary Contact / Mobile</span>
                                </div>
                            </div>

                            <p class="text-[6.5px] text-slate-600 leading-tight italic pt-0.5 border-t border-rose-200/50">
                                In the event of accident, medical emergency, or hospitalization, please notify the designated contact above immediately or call 888-ALERTO.
                            </p>
                        </div>

                        <!-- SECTION 2: CALOOCAN 24/7 EMERGENCY & CIVIC DIRECTORY GRID -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[7px] font-black uppercase tracking-wider text-slate-700 flex items-center gap-1">
                                    <i class="fa-solid fa-tower-broadcast text-rose-500 text-[8px]"></i>
                                    <span>CALOOCAN 24/7 EMERGENCY &amp; CIVIC DIRECTORY</span>
                                </span>
                                <span class="text-[6.5px] font-bold text-slate-400">Toll-Free &amp; Landline</span>
                            </div>

                            <div class="grid grid-cols-5 gap-1.5 text-center">
                                <!-- CDRRMO -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1 space-y-0.5">
                                    <span class="text-[6px] font-bold uppercase text-slate-500 block truncate">🚨 CDRRMO</span>
                                    <strong class="text-[7.5px] font-mono font-black text-rose-600 block leading-tight">888-ALERTO</strong>
                                    <span class="text-[5.5px] text-slate-400 block">8882-5378 &bull; 24/7</span>
                                </div>

                                <!-- POLICE (PNP) -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1 space-y-0.5">
                                    <span class="text-[6px] font-bold uppercase text-slate-500 block truncate">👮 POLICE (PNP)</span>
                                    <strong class="text-[7.5px] font-mono font-black text-slate-800 block leading-tight">(02) 8287-2270</strong>
                                    <span class="text-[5.5px] text-slate-400 block">Caloocan HQ</span>
                                </div>

                                <!-- FIRE (BFP) -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1 space-y-0.5">
                                    <span class="text-[6px] font-bold uppercase text-slate-500 block truncate">🚒 FIRE (BFP)</span>
                                    <strong class="text-[7.5px] font-mono font-black text-slate-800 block leading-tight">(02) 8361-9878</strong>
                                    <span class="text-[5.5px] text-slate-400 block">Central Station</span>
                                </div>

                                <!-- HOSPITAL (CCMC) -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1 space-y-0.5">
                                    <span class="text-[6px] font-bold uppercase text-slate-500 block truncate">🏥 CCMC HOSP</span>
                                    <strong class="text-[7.5px] font-mono font-black text-slate-800 block leading-tight">(02) 8288-8888</strong>
                                    <span class="text-[5.5px] text-slate-400 block">City Medical</span>
                                </div>

                                <!-- BUREAU SPECIFIC -->
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-1 space-y-0.5">
                                    <span id="cardModalBackHotlineLabel" class="text-[6px] font-bold uppercase text-slate-500 block truncate">🏛️ REGISTRY</span>
                                    <strong id="cardModalBackHotlinePhone" class="text-[7.5px] font-mono font-black text-slate-800 block leading-tight">(02) 8366-3101</strong>
                                    <span id="cardModalBackHotlineSub" class="text-[5.5px] text-slate-400 block">City Hall Desk</span>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: LEGAL TERMS & CONDITIONS -->
                        <div class="bg-slate-50/70 border border-slate-200/80 rounded-lg p-1.5 text-[6px] leading-tight text-slate-600 space-y-0.5">
                            <p>&bull; Non-transferable. Property of the City Government of Caloocan.</p>
                            <p>&bull; Valid proof of residency across city public health, social services, and partner merchants.</p>
                            <p>&bull; If found, please return to any Barangay Hall or Caloocan City Hall. Tampering is punishable by law (RPC 172).</p>
                        </div>

                        <!-- SECTION 4: BACK FOOTER (SERIAL + MAYOR SIGNATURE) -->
                        <div class="pt-1 border-t border-slate-200 flex items-end justify-between">
                            <div>
                                <span class="text-[6.5px] font-mono font-bold text-slate-800 block">
                                    SERIAL: <span id="cardModalBackBarcode">01002026000035</span>
                                </span>
                                <span class="text-[5.5px] font-mono text-slate-500 block">
                                    TOKEN: <span id="cardModalBackControlNum">CAL-2026-000035</span>
                                </span>
                            </div>

                            <div class="text-right">
                                <div class="border-b border-slate-400 pb-0.5 mb-0.5 inline-block text-right">
                                    <strong class="text-[7.5px] font-black uppercase text-slate-900 block leading-tight">
                                        HON. DALE GONZALO &ldquo;ALONG&rdquo; MALAPITAN
                                    </strong>
                                </div>
                                <span class="text-[6px] font-bold uppercase text-slate-600 block leading-tight">
                                    City Mayor &bull; City of Caloocan
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- CIVIC DIRECTORY & EMERGENCY HOTLINES CARD -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-phone-volume text-rose-500"></i> Caloocan City Civic &amp; Emergency Directory
                    </span>
                    <span class="text-[10px] font-bold text-slate-400">24/7 Priority Dispatch</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 space-y-0.5">
                        <span class="text-[9.5px] text-slate-400 font-bold uppercase block">CDRRMO Rescue</span>
                        <p class="font-black text-rose-600 font-mono text-xs">(02) 888-ALERTO</p>
                        <p class="text-[8.5px] text-slate-500">8882-5378 &bull; 24/7</p>
                    </div>

                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 space-y-0.5">
                        <span class="text-[9.5px] text-slate-400 font-bold uppercase block">Police (PNP)</span>
                        <p class="font-black text-slate-800 font-mono text-xs">(02) 8287-2270</p>
                        <p class="text-[8.5px] text-slate-500">Caloocan Police HQ</p>
                    </div>

                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 space-y-0.5">
                        <span class="text-[9.5px] text-slate-400 font-bold uppercase block">Fire (BFP)</span>
                        <p class="font-black text-slate-800 font-mono text-xs">(02) 8361-9878</p>
                        <p class="text-[8.5px] text-slate-500">Central Fire Station</p>
                    </div>

                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 space-y-0.5">
                        <span class="text-[9.5px] text-slate-400 font-bold uppercase block">Civil Registry</span>
                        <p class="font-black text-slate-800 font-mono text-xs">(02) 8366-3101</p>
                        <p class="text-[8.5px] text-slate-500">City Hall Registry</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer Actions -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">
                Standard CR80 PVC Dual-Sided Smart Card &bull; Caloocan Registry
            </span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleCardSide()" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-rotate text-xs"></i>
                    <span id="footerFlipBtnText">Flip to Back Side</span>
                </button>
                <button type="button" onclick="closeCitizenCardModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                    Close
                </button>
                <button type="button" onclick="printCitizenCard()" class="px-4 py-2 text-xs font-bold text-white bg-[#0F4C81] hover:bg-sky-800 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Print Card (Both Sides)</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Print Stylesheet: Fits both Front and Back sides onto EXACTLY ONE PAGE -->
<style>
@media print {
  @page {
    size: portrait;
    margin: 8mm;
  }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
    background: #ffffff !important;
  }

  body * {
    visibility: hidden !important;
  }

  #printContainer,
  #printContainer * {
    visibility: visible !important;
  }

  #printContainer {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 8mm !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  /* Force both front and back sides to be visible when printing */
  #printableCitizenCard,
  #printableCardBack {
    display: block !important;
    width: 105mm !important;
    height: 66.2mm !important;
    aspect-ratio: 105 / 66.2 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 3.5mm !important;
    background: #ffffff !important;
    overflow: hidden !important;
    position: relative !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print-cut-guide {
    display: block !important;
    font-size: 8pt !important;
    color: #94a3b8 !important;
    text-align: center !important;
    letter-spacing: 1px !important;
  }
}
</style>

<script>
/**
 * Safe Element Text Setter Helper
 */
function setSafeElementText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text || '';
}

/**
 * Card Side Switcher
 */
let currentCardSide = 'front';

function switchCardSide(side) {
    currentCardSide = side;
    const frontCard = document.getElementById('printableCitizenCard');
    const backCard = document.getElementById('printableCardBack');
    const tabFront = document.getElementById('tabCardFront');
    const tabBack = document.getElementById('tabCardBack');
    const promptText = document.getElementById('cardFlipPromptText');
    const footerBtnText = document.getElementById('footerFlipBtnText');

    if (side === 'front') {
        if (frontCard) frontCard.classList.remove('hidden');
        if (backCard) backCard.classList.add('hidden');
        if (tabFront) {
            tabFront.className = 'flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer bg-white text-[#0F4C81] shadow-xs';
        }
        if (tabBack) {
            tabBack.className = 'flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900';
        }
        if (promptText) {
            promptText.textContent = 'Viewing Front Side • Click card or tab to view Back Side';
        }
        if (footerBtnText) {
            footerBtnText.textContent = 'Flip to Back Side';
        }
    } else {
        if (frontCard) frontCard.classList.add('hidden');
        if (backCard) backCard.classList.remove('hidden');
        if (tabFront) {
            tabFront.className = 'flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900';
        }
        if (tabBack) {
            tabBack.className = 'flex-1 py-2 px-3 rounded-xl font-black text-xs transition flex items-center justify-center gap-2 cursor-pointer bg-white text-[#0F4C81] shadow-xs';
        }
        if (promptText) {
            promptText.textContent = 'Viewing Back Side • Click card or tab to view Front Side';
        }
        if (footerBtnText) {
            footerBtnText.textContent = 'Flip to Front Side';
        }
    }
}

function toggleCardSide() {
    switchCardSide(currentCardSide === 'front' ? 'back' : 'front');
}

/**
 * Category Resolver Function
 * Maps the applicant's category using birth_date, is_pwd, and residency flags.
 */
function resolveCitizenCategory(birthDateStr, isPwd = false, isNonResident = false, idCategory = '') {
  const cat = (idCategory || '').toLowerCase();
  
  if (cat.includes('barangay')) {
    return {
      title: 'BARANGAY RESIDENT',
      cardTitle: 'BARANGAY RESIDENT IDENTIFICATION CARD',
      cardSubtitle: 'BARANGAY 178 • SANGGUNIANG BARANGAY • CITY OF CALOOCAN',
      modalTitle: 'Barangay Resident Card',
      type: 'BARANGAY RESIDENT',
      stops: ['#064E3B', '#047857', '#10B981', '#059669'],
      accentColor: '#059669',
      badgeBg: '#DCFCE7',
      badgeText: '#15803D',
      col1Label: 'PRECINCT NO.',
      col1Val: '0412-A',
      col2Label: 'DATE ISSUED',
      col3Label: 'VALID UNTIL',
      validityYears: 1,
      hotlineLabel: '🏛️ BARANGAY HALL',
      hotlinePhone: '(02) 8366-3101',
      hotlineSub: 'Barangay Secretariat'
    };
  }
  if (cat.includes('pwd') || isPwd) {
    return {
      title: 'PWD PRIVILEGE',
      cardTitle: 'PERSON WITH DISABILITY (PWD) ID',
      cardSubtitle: 'REPUBLIC ACT NO. 10754 • PERSONS WITH DISABILITY AFFAIRS OFFICE (PDAO)',
      modalTitle: 'Civentral PWD Card',
      type: 'PWD',
      stops: ['#172554', '#1E40AF', '#2563EB', '#1D4ED8'],
      accentColor: '#1E40AF',
      badgeBg: '#DBEAFE',
      badgeText: '#1E40AF',
      col1Label: 'DISABILITY',
      col1Val: 'ORTHOPEDIC',
      col2Label: 'BLOOD TYPE',
      col3Label: 'VALID UNTIL',
      validityYears: 3,
      hotlineLabel: '♿ PDAO OFFICE',
      hotlinePhone: '(02) 8366-4000',
      hotlineSub: 'Caloocan PDAO Desk'
    };
  }
  if (cat.includes('senior') || cat.includes('osca')) {
    return {
      title: 'SENIOR CITIZEN (60+)',
      cardTitle: 'SENIOR CITIZEN IDENTIFICATION CARD',
      cardSubtitle: 'REPUBLIC ACT NO. 9994 • OFFICE OF SENIOR CITIZENS AFFAIRS (OSCA)',
      modalTitle: 'Civentral Senior Citizen Card',
      type: 'SENIOR CITIZEN',
      stops: ['#450A0A', '#7F1D1D', '#DC2626', '#D97706'],
      accentColor: '#991B1B',
      badgeBg: '#FEF3C7',
      badgeText: '#92400E',
      col1Label: 'OSCA NO.',
      col1Val: 'CAL-OSCA-0812',
      col2Label: 'BLOOD TYPE',
      col3Label: 'VALID UNTIL',
      validityYears: 0,
      lifetime: true,
      hotlineLabel: '🎖️ OSCA OFFICE',
      hotlinePhone: '(02) 8366-2200',
      hotlineSub: 'Caloocan OSCA Desk'
    };
  }
  if (cat.includes('solo')) {
    return {
      title: 'SOLO PARENT',
      cardTitle: 'SOLO PARENT IDENTIFICATION CARD',
      cardSubtitle: 'REPUBLIC ACT NO. 11861 • CITY SOCIAL WELFARE AND DEVELOPMENT (CSWDO)',
      modalTitle: 'Solo Parent Identification Card',
      type: 'SOLO PARENT',
      stops: ['#4A044E', '#6B21A8', '#A855F7', '#EAB308'],
      accentColor: '#6B21A8',
      badgeBg: '#F3E8FF',
      badgeText: '#6B21A8',
      col1Label: 'DEPENDENTS',
      col1Val: '2 MINORS',
      col2Label: 'DATE ISSUED',
      col3Label: 'VALID UNTIL',
      validityYears: 1,
      hotlineLabel: '👨‍👧 CSWDO WELFARE',
      hotlinePhone: '(02) 8366-5000',
      hotlineSub: 'Solo Parent Section'
    };
  }
  if (isNonResident) {
    return {
      title: 'NON-RESIDENT',
      cardTitle: 'CIVENTRAL NON-RESIDENT CARD',
      cardSubtitle: 'MUNICIPAL VISITOR & BUSINESS CREDENTIAL • CITY OF CALOOCAN',
      modalTitle: 'Non-Resident Credential',
      type: 'NON-RESIDENT',
      stops: ['#0F172A', '#334155', '#475569', '#64748B'],
      accentColor: '#475569',
      badgeBg: '#F1F5F9',
      badgeText: '#334155',
      col1Label: 'BLOOD TYPE',
      col2Label: 'DATE ISSUED',
      col3Label: 'VALID UNTIL',
      validityYears: 1,
      hotlineLabel: '🏛️ REGISTRY',
      hotlinePhone: '(02) 8366-3101',
      hotlineSub: 'City Hall Desk'
    };
  }

  let age = 30; // fallback adult
  if (birthDateStr) {
    const bDate = new Date(birthDateStr);
    if (!isNaN(bDate.getTime())) {
      const diff = Date.now() - bDate.getTime();
      age = Math.abs(new Date(diff).getUTCFullYear() - 1970);
    }
  }

  if (age >= 60) {
    return {
      title: 'SENIOR CITIZEN',
      cardTitle: 'SENIOR CITIZEN IDENTIFICATION CARD',
      cardSubtitle: 'REPUBLIC ACT NO. 9994 • OFFICE OF SENIOR CITIZENS AFFAIRS (OSCA)',
      modalTitle: 'Civentral Senior Citizen Card',
      type: 'SENIOR CITIZEN',
      stops: ['#172554', '#1E3A8A', '#2563EB', '#1D4ED8'],
      accentColor: '#2563EB',
      badgeBg: '#EFF6FF',
      badgeText: '#1E40AF',
      col1Label: 'OSCA NO.',
      col1Val: 'CAL-OSCA-0812',
      col2Label: 'BLOOD TYPE',
      col3Label: 'VALID UNTIL',
      validityYears: 0,
      lifetime: true,
      hotlineLabel: '🎖️ OSCA OFFICE',
      hotlinePhone: '(02) 8366-2200',
      hotlineSub: 'Caloocan OSCA Desk'
    };
  }

  // General Adult Resident (18-59)
  return {
    title: 'OFFICIAL RESIDENT',
    cardTitle: 'CIVENTRAL CITIZEN CARD',
    cardSubtitle: 'KASAMA KA SA PAG-UNLAD • CITY OF CALOOCAN',
    modalTitle: 'Civentral Citizen Card',
    type: 'RESIDENT',
    stops: ['#881337', '#991B1B', '#DC2626', '#B91C1C'],
    accentColor: '#DC2626',
    badgeBg: '#FEF2F2',
    badgeText: '#991B1B',
    col1Label: 'BLOOD TYPE',
    col2Label: 'DATE ISSUED',
    col3Label: 'VALID UNTIL',
    validityYears: 5,
    hotlineLabel: '🏛️ REGISTRY',
    hotlinePhone: '(02) 8366-3101',
    hotlineSub: 'City Hall Desk'
  };
}

/**
 * Fallback QR Code Renderer (Uses encoded image)
 */
function renderFallbackQr(container, payload) {
    if (!container) return;
    container.innerHTML = '';
    const img = document.createElement('img');
    img.className = 'w-full h-full object-contain';
    img.alt = 'QR Matrix';
    img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' + encodeURIComponent(payload);
    container.appendChild(img);
}

/**
 * Global Citizen Card Modal Handler
 * Full Dual-Sided Implementation matching Mobile App & Authentic CR80 Blueprint.
 */
function openCitizenCardModal(app) {
    if (!app) return;

    // Default to front side on open
    switchCardSide('front');

    // 0. Compute Dynamic Citizen Classification & Colors
    const category = resolveCitizenCategory(
        app.birth_date || app.birthdate,
        !!(app.is_pwd || app.pwd),
        !!(app.is_non_resident || app.non_resident),
        app.id_category || app.category || app.id_title || ''
    );

    // Apply dynamic colors to vector header ribbon SVG gradients (stops 1-4)
    const stop1 = document.getElementById('cardRibbonStop1');
    const stop2 = document.getElementById('cardRibbonStop2');
    const stop3 = document.getElementById('cardRibbonStop3');
    const stop4 = document.getElementById('cardRibbonStop4');

    if (stop1) stop1.setAttribute('stop-color', category.stops[0]);
    if (stop2) stop2.setAttribute('stop-color', category.stops[1]);
    if (stop3) stop3.setAttribute('stop-color', category.stops[2]);
    if (stop4) stop4.setAttribute('stop-color', category.stops[3]);

    // Header Card Titles
    setSafeElementText('cardModalHeaderTitle', category.cardTitle || 'CIVENTRAL CITIZEN CARD');
    setSafeElementText('cardModalHeaderSubtitle', category.cardSubtitle || 'KASAMA KA SA PAG-UNLAD • CITY OF CALOOCAN');
    setSafeElementText('cardModalTopTitle', category.modalTitle || 'Civentral Citizen Card');

    // Classification labels and badges
    const classifLabel = document.getElementById('cardModalClassification');
    if (classifLabel) classifLabel.textContent = category.title;

    const classifBadge = document.getElementById('cardModalClassificationBadge');
    if (classifBadge) {
        classifBadge.style.backgroundColor = category.badgeBg;
        classifBadge.style.borderColor = category.accentColor + '40';
        if (classifLabel) classifLabel.style.color = category.badgeText;
    }

    const catBadge = document.getElementById('cardModalCategoryBadge');
    if (catBadge) {
        catBadge.textContent = category.title;
        catBadge.style.backgroundColor = category.badgeBg;
        catBadge.style.color = category.badgeText;
        catBadge.style.borderColor = category.accentColor + '40';
    }

    // 1. Format and display cardholder legal name
    let formattedName = app.applicant || app.name || '';
    if (app.last_name && app.first_name) {
        const middleInitial = app.middle_name ? ` ${app.middle_name.trim().charAt(0)}.` : '';
        const suffix = app.suffix ? ` ${app.suffix.trim()}` : '';
        formattedName = `${app.last_name.toUpperCase()}, ${app.first_name.toUpperCase()}${middleInitial}${suffix}`;
    }
    setSafeElementText('cardModalName', formattedName || 'CITIZEN CARDHOLDER');

    // 2. Citizen Control ID Number & Monospace Numeric Sequence
    const idNumber = app.citizen_id_number || app.reference_no || app.id || 'CAL-2026-000035';
    const numericSeed = idNumber.replace(/\D/g, '') || '000035';
    const barcodeSequence = '0100' + numericSeed.padStart(10, '0');

    setSafeElementText('cardModalControlNum', idNumber);
    setSafeElementText('cardModalBarcodeNum', barcodeSequence);
    setSafeElementText('cardModalBackBarcode', barcodeSequence);
    setSafeElementText('cardModalBackControlNum', idNumber);

    // 3. Demographic Information
    const rawDob = app.birth_date || app.birthdate;
    setSafeElementText('cardModalDob', rawDob ? formatDateDisplay(rawDob) : '1998/05/15');
    
    const sex = (app.gender || app.sex || 'Male').toUpperCase();
    setSafeElementText('cardModalSex', sex.startsWith('F') ? 'F' : 'M');
    
    const civil = (app.civil_status || 'Single').toUpperCase();
    setSafeElementText('cardModalCivil', civil);

    // Row 2 Dynamic Demographics Columns
    const issuedDate = app.issued_date || app.reviewed_at || app.created_at || '2026/10/10';
    const formattedIssued = formatDateDisplay(issuedDate);

    let expiryDate = app.valid_until;
    if (!expiryDate) {
        if (category.lifetime) {
            expiryDate = 'LIFETIME VALIDITY';
        } else {
            const d = new Date(issuedDate);
            const addYears = category.validityYears || 5;
            d.setFullYear(d.getFullYear() + addYears);
            expiryDate = formatDateDisplay(d.toISOString().slice(0, 10));
        }
    } else {
        expiryDate = formatDateDisplay(expiryDate);
    }

    setSafeElementText('cardModalCol1Label', category.col1Label || 'BLOOD TYPE');
    setSafeElementText('cardModalCol1Value', category.col1Val || (app.blood_type || 'N/A').toUpperCase());

    setSafeElementText('cardModalCol2Label', category.col2Label || 'DATE ISSUED');
    setSafeElementText('cardModalCol2Value', formattedIssued);

    setSafeElementText('cardModalCol3Label', category.col3Label || 'VALID UNTIL');
    setSafeElementText('cardModalCol3Value', expiryDate);

    // 4. Address Details (2 lines uppercase)
    const street = (app.street_address || '').toUpperCase().trim();
    const brgyRaw = (app.barangay || 'BARANGAY 171').toUpperCase().trim();
    const brgy = brgyRaw.startsWith('BARANGAY') ? brgyRaw : `BARANGAY ${brgyRaw}`;
    const district = (app.district || 'DISTRICT 2').toUpperCase().trim();

    setSafeElementText('cardModalAddressStreet', street ? `${street}, ${brgy}` : brgy);
    setSafeElementText('cardModalAddressCity', `${district}, CALOOCAN CITY`);

    // 5. Emergency Contact Details
    const emergencyName = (app.emergency_contact_name || app.emergency_contact || 'NEXT OF KIN / FAMILY MEMBER').toUpperCase().trim();
    const emergencyPhone = (app.emergency_contact_phone || app.contact_number || '(02) 8366-3101').trim();
    const emergencyRelation = (app.emergency_contact_relation || 'Immediate Family / Relative').trim();

    setSafeElementText('cardModalEmergency', `${emergencyName} (${emergencyPhone})`);
    setSafeElementText('cardModalBackEmgName', emergencyName);
    setSafeElementText('cardModalBackEmgRelation', `Relation: ${emergencyRelation}`);
    setSafeElementText('cardModalBackEmgPhone', emergencyPhone);

    // 6. Back Side Hotlines
    setSafeElementText('cardModalBackHotlineLabel', category.hotlineLabel || '🏛️ REGISTRY');
    setSafeElementText('cardModalBackHotlinePhone', category.hotlinePhone || '(02) 8366-3101');
    setSafeElementText('cardModalBackHotlineSub', category.hotlineSub || 'City Hall Desk');

    // 7. Timestamp at bottom-left corner
    const now = new Date();
    const tsY = now.getFullYear();
    const tsM = String(now.getMonth() + 1).padStart(2, '0');
    const tsD = String(now.getDate()).padStart(2, '0');
    const timeStr = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    setSafeElementText('cardModalTimestamp', `${tsY}/${tsM}/${tsD} ${timeStr}`);

    // 8. Assets: 1x1 Photo
    const photoImg = document.getElementById('cardModalPhoto');
    if (photoImg) {
        const photoSrc = resolveCardAssetUrl(app.photo_1x1_url || app.photo_2x2_url || app.photo_url || app.selfie_photo_url || app.avatar);
        photoImg.src = photoSrc || `https://ui-avatars.com/api/?name=${encodeURIComponent(formattedName)}&background=0F4C81&color=fff&size=200`;
    }

    // 9. Assets: Cardholder Digital Signature
    const sigImg = document.getElementById('cardModalSignature');
    const sigPlaceholder = document.getElementById('cardModalSigPlaceholder');
    const sigSrc = resolveCardAssetUrl(app.signature_photo_url || app.signature_url);
    if (sigImg) {
        if (sigSrc) {
            sigImg.src = sigSrc;
            sigImg.style.display = 'block';
            if (sigPlaceholder) sigPlaceholder.style.display = 'none';
        } else if (app.e_signature_name) {
            sigImg.src = '';
            sigImg.style.display = 'none';
            if (sigPlaceholder) {
                sigPlaceholder.innerText = app.e_signature_name;
                sigPlaceholder.className = 'text-[10px] font-bold text-blue-900 italic select-none font-serif';
                sigPlaceholder.style.display = 'block';
            }
        } else {
            sigImg.src = '';
            sigImg.style.display = 'none';
            if (sigPlaceholder) {
                sigPlaceholder.innerText = 'Digital Signature';
                sigPlaceholder.className = 'text-[7.5px] text-slate-400 italic';
                sigPlaceholder.style.display = 'block';
            }
        }
    }

    // 10. Assets: Scannable Vector QR Code Matrix
    const qrPayload = `CIVENTRAL:ID:${idNumber}|TOKEN:${app.qr_code_token || barcodeSequence}`;

    const qrContainer = document.getElementById('cardModalQrBox');
    if (qrContainer) {
        qrContainer.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            try {
                new QRCode(qrContainer, {
                    text: qrPayload,
                    width: 74,
                    height: 74,
                    colorDark: '#0F172A',
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.M
                });
            } catch (err) {
                console.warn('Local QRCode generator warning:', err);
                renderFallbackQr(qrContainer, qrPayload);
            }
        } else {
            renderFallbackQr(qrContainer, qrPayload);
        }
    }

    // 11. Display Modal
    const modal = document.getElementById('citizenCardModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeCitizenCardModal() {
    const modal = document.getElementById('citizenCardModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// Backdrop click closes modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('citizenCardModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCitizenCardModal();
            }
        });
    }
});

function printCitizenCard() {
    window.print();
}

function resolveCardAssetUrl(url) {
    if (!url || typeof url !== 'string') return null;
    url = url.trim();
    if (!url || url.startsWith('blob:')) return null;

    if (url.startsWith('http://') || url.startsWith('https://')) {
        if (url.includes('api-citizen.civentral.tech')) {
            url = url.replace('api-citizen.civentral.tech', window.location.host);
        }
        return url;
    }
    if (url.startsWith('data:image/')) return url;

    const cleanPath = url.replace(/^\/+/, '');
    const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    if (isLocal) {
        if (cleanPath.startsWith('assets/')) return '../../' + cleanPath;
        if (cleanPath.startsWith('uploads/')) return '../../assets/' + cleanPath;
        return '../../assets/' + cleanPath;
    }

    if (cleanPath.startsWith('uploads/')) {
        return '/assets/' + cleanPath;
    }
    if (cleanPath.startsWith('assets/')) {
        return '/' + cleanPath;
    }
    return '/assets/uploads/' + cleanPath;
}

function formatDateDisplay(dateStr) {
    if (!dateStr) return 'N/A';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}/${mm}/${dd}`;
    } catch (_) {
        return dateStr;
    }
}
</script>
