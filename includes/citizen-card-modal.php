<?php
/**
 * Civentral Official Citizen ID Card Modal Component
 * Authentic Philippine Municipal PVC Government Smart Card (QCitizen Style)
 * Features:
 * - Clean White / Slate PVC Plastic Form Factor (CR80 Standard: 85.6mm x 53.98mm)
 * - Header Band with Civentral Deep Navy (#0F4C81) & Crimson Red (#DC2626) with Gold Accent
 * - Authentic Civentral Seal / Republic Header
 * - 1x1 Formal Cardholder Photo (85x85px) with Signature Baseline Box
 * - Prominent Embossed Legal Name & Resident Control ID
 * - Complete 2-Column Demographics Grid (Sex, DOB, Civil Status, Issued, Valid Until, Address)
 * - Emergency Contact Hotline Annotation
 * - Local Vector Scannable QR Matrix & Control Barcode
 * - Multi-Layer Null-Safe Controller & Backdrop Close Support
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
                    <h3 class="text-sm font-black text-white tracking-wide">
                        Civentral Citizen Card
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium">Official Municipal Credential &bull; City of Caloocan</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button onclick="printCitizenCard()" class="px-3 py-1.5 text-xs font-bold text-white bg-[#0F4C81] hover:bg-sky-700 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-xs border border-sky-400/30">
                    <i class="fa-solid fa-print text-[11px]"></i>
                    <span class="hidden sm:inline">Print / Save PDF</span>
                </button>
                <button id="closeCitizenCardModalBtn" onclick="closeCitizenCardModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition flex items-center justify-center cursor-pointer border border-slate-700">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="p-5 sm:p-6 bg-slate-100/70 space-y-4">

            <!-- THE OFFICIAL WHITE PVC CITIZEN SMART CARD (CR80 PHYSICAL RATIO) -->
            <div id="printContainer" class="flex flex-col items-center justify-center">
                <div id="printableCitizenCard" class="printable-card-side w-full max-w-[530px] rounded-2xl shadow-xl border border-slate-300 relative overflow-hidden select-none bg-white text-slate-900 transition-transform" style="aspect-ratio: 85.6/53.98; min-height: 335px;">
                    
                    <!-- Subtle Right Diagonal Wave Gradient -->
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(125deg, #FFFFFF 0%, #FFFFFF 52%, #F8FAFC 75%, #EDF2F7 100%);"></div>

                    <!-- Municipal Building Watermark Background -->
                    <img 
                        src="../../assets/images/building-bg.jpg" 
                        onerror="this.src='../assets/images/building-bg.jpg'; this.onerror=null;" 
                        style="position: absolute; right: 0; bottom: 0; width: 65%; height: 85%; object-fit: contain; opacity: 0.45; mix-blend-mode: multiply; filter: contrast(1.3) grayscale(20%); pointer-events: none; z-index: 1;" 
                        alt="Civic Building Watermark" 
                    />

                    <!-- Guilloche Security Pattern SVG Watermark (Ultra Subtle) -->
                    <div class="absolute inset-0 pointer-events-none opacity-[0.035] overflow-hidden z-0">
                        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <pattern id="pvcGuilloche" width="36" height="36" patternUnits="userSpaceOnUse">
                                    <path d="M 0,18 Q 9,0 18,18 T 36,18" fill="none" stroke="#0F4C81" stroke-width="0.8" />
                                    <path d="M 0,18 Q 9,36 18,18 T 36,18" fill="none" stroke="#DC2626" stroke-width="0.6" />
                                    <circle cx="18" cy="18" r="12" fill="none" stroke="#0F4C81" stroke-width="0.5" />
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#pvcGuilloche)" />
                        </svg>
                    </div>

                    <!-- Wavy Header Ribbon Extended All The Way to the Right (100% Width) -->
                    <svg class="absolute top-0 left-0 w-full h-[84px] pointer-events-none z-0" viewBox="0 0 500 84" preserveAspectRatio="none">
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
                      <!-- Red Wave dipping down to 78px on left, 62px on right -->
                      <path d="M 0,0 L 500,0 L 500,62 Q 370,72 250,66 T 0,80 Z" fill="url(#headerWaveGrad)" />
                      <!-- Gold Accent Wave border directly below -->
                      <path d="M 0,80 Q 130,66 250,66 T 500,62 L 500,66 Q 370,76 250,70 T 0,84 Z" fill="url(#goldWaveStripe)" />
                    </svg>

                    <!-- CARD CONTENT CONTAINER -->
                    <div class="relative z-10 p-3.5 sm:p-4 flex flex-col justify-between h-full" style="height: 100%;">
                        
                        <!-- TOP HEADER BLOCK -->
                        <div class="relative mb-2">
                            <!-- Top Center Line: REPUBLIC OF THE PHILIPPINES -->
                            <div class="text-center w-full">
                                <span class="text-[7.5px] font-bold uppercase tracking-[2px] text-[#FEE2E2] drop-shadow-xs">
                                    Republic of the Philippines
                                </span>
                            </div>

                            <!-- Brand Row with Logo & Centered Brand Title -->
                            <div class="flex items-center mt-1 pr-10">
                                <!-- Left Emblem Over Dynamic Vector Ribbon with Gold Metallic Ring -->
                                <div class="w-10 h-10 rounded-full bg-white p-0.5 shadow-md flex items-center justify-center shrink-0 border-[1.5px] border-amber-400 ml-1">
                                    <img src="../../assets/images/logo.png" onerror="this.src='../assets/images/logo.png'; this.onerror=null;" class="w-full h-full object-contain" alt="Civentral Seal" />
                                </div>

                                <!-- Centered Brand Title & Tagline -->
                                <div class="flex-1 flex flex-col items-center justify-center text-center">
                                    <h3 class="text-[15px] font-black uppercase tracking-wider text-[#FFFFFF] drop-shadow-sm leading-tight">
                                        Civentral Citizen Card
                                    </h3>
                                    <p class="text-[7.5px] font-bold text-[#FDE047] uppercase tracking-[1px] leading-tight mt-0.5 drop-shadow-xs">
                                        Kasama Ka Sa Pag-Unlad &bull; City of Caloocan
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- THREE-COLUMN CARD BODY -->
                        <div class="grid grid-cols-12 gap-3 items-start flex-1">
                            
                            <!-- COLUMN 1: Left - Photo, Signature, Resident Type (3 cols) on clean pure white base (z-20 in front of wave) -->
                            <div class="col-span-3 flex flex-col items-center justify-between h-full relative z-20">
                                <div class="flex flex-col items-center">
                                    <!-- 1x1 Photo Frame: Square photo, clean 1px neutral slate border, rounded 4px, sits in front of wave -->
                                    <div class="w-[82px] h-[82px] rounded-[4px] border border-[#CBD5E1] bg-slate-50 shadow-sm overflow-hidden flex items-center justify-center p-0.5 relative z-20">
                                        <img 
                                            id="cardModalPhoto" 
                                            src="" 
                                            class="w-full h-full object-cover rounded-[3px]" 
                                            alt="Citizen Photo" 
                                            onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Citizen&background=0F4C81&color=fff&size=200';" 
                                        />
                                    </div>

                                    <!-- Signature Box directly below photo, sits in front of wave -->
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
                                    <span class="text-[7px] font-semibold text-[#64748B] uppercase tracking-wider mt-0.5">Cardholder Signature</span>

                                    <!-- Dynamic Resident Status Classification on clean white base -->
                                    <span id="cardModalClassification" class="text-[9.5px] font-black text-[#0F172A] uppercase tracking-wider mt-1 text-center transition-colors">
                                        RESIDENT
                                    </span>
                                </div>

                                <!-- Issuance Timestamp at bottom-left corner -->
                                <div class="w-full text-left mt-auto pt-1">
                                    <span id="cardModalTimestamp" class="text-[6.5px] font-mono text-slate-500 block leading-tight">
                                        2026/10/04 06:04:00 PM
                                    </span>
                                </div>
                            </div>

                            <!-- COLUMN 2: Center - Citizen Demographics (6 cols) -->
                            <div class="col-span-6 flex flex-col justify-between h-full space-y-1.5 pl-0.5 relative z-10">
                                <div>
                                    <!-- Full Name Block -->
                                    <div>
                                        <span class="text-[7px] font-bold uppercase tracking-wider text-[#334155] block">
                                            Last Name, First Name, M.I.
                                        </span>
                                        <h4 id="cardModalName" class="text-[13.5px] font-black text-[#0F172A] uppercase tracking-wide leading-tight truncate">
                                            BASCO, MAE
                                        </h4>
                                    </div>

                                    <!-- Demographics Grid (3 Columns x 2 Rows) -->
                                    <div class="grid grid-cols-3 gap-x-2 gap-y-1.5 pt-2 border-t border-slate-200 mt-1">
                                        <!-- Row 1 -->
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Sex</span>
                                            <span id="cardModalSex" class="text-[8.5px] font-black text-[#0F172A] block uppercase">M</span>
                                        </div>
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Date of Birth</span>
                                            <span id="cardModalDob" class="text-[8.5px] font-black text-[#0F172A] block font-mono">1998/05/15</span>
                                        </div>
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Civil Status</span>
                                            <span id="cardModalCivil" class="text-[8.5px] font-black text-[#0F172A] block uppercase">SINGLE</span>
                                        </div>

                                        <!-- Row 2 -->
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Blood Type</span>
                                            <span id="cardModalBlood" class="text-[8.5px] font-black text-[#0F172A] block">N/A</span>
                                        </div>
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Date Issued</span>
                                            <span id="cardModalIssued" class="text-[8.5px] font-black text-[#0F172A] block font-mono">2026/10/04</span>
                                        </div>
                                        <div>
                                            <span class="text-[7px] font-bold uppercase text-[#334155] block">Valid Until</span>
                                            <span id="cardModalExpiry" class="text-[8.5px] font-black text-[#0F172A] block font-mono">2031/10/04</span>
                                        </div>
                                    </div>

                                    <!-- Address Block (2 Lines uppercase) -->
                                    <div class="pt-2 border-t border-slate-200 mt-1.5">
                                        <span id="cardModalAddressStreet" class="text-[8.5px] font-black text-[#0F172A] uppercase tracking-tight block leading-tight truncate">
                                            BLOCK 5 LOT 6, BARANGAY 3, DISTRICT 1
                                        </span>
                                        <span id="cardModalAddressCity" class="text-[8.5px] font-black text-[#0F172A] uppercase tracking-tight block leading-tight">
                                            CALOOCAN CITY
                                        </span>
                                    </div>
                                </div>

                                <!-- Emergency Contact at very bottom center -->
                                <div class="pt-1 border-t border-slate-200 mt-auto">
                                    <span class="text-[7px] text-[#334155] block leading-tight">
                                        <strong class="font-bold text-[#334155]">Emergency Contact:</strong> <span id="cardModalEmergency" class="font-bold text-[#0F172A]">Juan Basco (0917-123-4567)</span>
                                    </span>
                                </div>
                            </div>

                            <!-- COLUMN 3: Right - QR & Control String (3 cols, z-20 in front of wave) -->
                            <div class="col-span-3 flex flex-col items-end justify-between h-full self-stretch pl-1 relative z-20">
                                <div class="flex flex-col items-center w-full relative z-20">
                                    <!-- High-density square QR code matrix with zero padding/margins, sits crisp in front of wave -->
                                    <div class="w-[82px] h-[82px] bg-white border border-slate-200 shadow-sm flex items-center justify-center overflow-hidden relative z-20">
                                        <div id="cardModalQrBox" class="w-full h-full flex items-center justify-center p-0 m-0">
                                            <img id="cardModalQr" src="" class="w-full h-full object-contain" alt="QR Matrix" />
                                        </div>
                                    </div>

                                    <!-- Monospace Numeric Sequence Directly Below QR -->
                                    <span id="cardModalBarcodeNum" class="text-[8px] font-mono font-bold text-slate-700 tracking-wider mt-1 block text-center">
                                        01002026000004
                                    </span>
                                </div>

                                <!-- Security Micro-Code at bottom-right corner -->
                                <div class="w-full text-right mt-auto pt-1">
                                    <span class="text-[8px] font-mono font-bold text-slate-700 block leading-tight">00</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Print Cut Guide (Print-only) -->
                <div class="print-cut-guide hidden my-2 text-slate-400 font-mono text-[9px] tracking-wider text-center select-none">
                    - - - - - - - - - - - - - - - - - - - - - CUT HERE / FOLD LINE - - - - - - - - - - - - - - - - - - - - -
                </div>

                <!-- THE OFFICIAL WHITE PVC CITIZEN SMART CARD - BACK SIDE (PRINT ONLY) -->
                <div id="printableCardBack" class="printable-card-side hidden w-full max-w-[530px] rounded-2xl shadow-xl border border-slate-300 relative overflow-hidden select-none bg-white text-slate-900" style="aspect-ratio: 85.6/53.98; min-height: 335px;">
                    
                    <!-- Subtle Right Diagonal Wave Gradient -->
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(125deg, #FFFFFF 0%, #FFFFFF 52%, #F8FAFC 75%, #EDF2F7 100%);"></div>

                    <!-- Municipal Building Watermark Background -->
                    <img 
                        src="../../assets/images/building-bg.jpg" 
                        onerror="this.src='../assets/images/building-bg.jpg'; this.onerror=null;" 
                        style="position: absolute; right: 0; bottom: 0; width: 65%; height: 85%; object-fit: contain; opacity: 0.25; mix-blend-mode: multiply; filter: contrast(1.2) grayscale(40%); pointer-events: none; z-index: 1;" 
                        alt="Civic Building Watermark" 
                    />

                    <!-- Card Back Content -->
                    <div class="relative z-10 p-4 sm:p-5 flex flex-col justify-between h-full" style="height: 100%;">
                        
                        <!-- Top Accent Line -->
                        <div class="w-full h-1 bg-gradient-to-r from-red-800 via-amber-400 to-red-800 mb-2 rounded-full"></div>

                        <!-- 3-Column Grid -->
                        <div class="grid grid-cols-12 gap-3 items-stretch flex-1">
                            
                            <!-- Left Column: General Terms and Conditions (5 cols) -->
                            <div class="col-span-5 flex flex-col justify-between pr-1 border-r border-slate-200">
                                <div>
                                    <h5 class="text-[8px] font-black uppercase tracking-wider text-slate-800 mb-1">
                                        GENERAL TERMS AND CONDITIONS
                                    </h5>
                                    <p class="text-[6.5px] leading-tight text-slate-600 text-justify">
                                        By signing or using this card, the cardholder agrees to be bound by the Civentral Citizen Card Terms and Conditions. Please present this card when availing of municipal services or authorized partner privileges in Caloocan City. Tampering invalidates this card. A card is deemed tampered when there are alterations or erasures apparent on the card itself. If found, please return to the City Civil Registry Office, Caloocan City Hall.
                                    </p>
                                </div>
                                <p class="text-[7.5px] font-bold text-slate-900 uppercase tracking-tight mt-auto pt-1">
                                    This card is Non-Transferable.
                                </p>
                            </div>

                            <!-- Middle Column: Issuing Authority & Hologram Watermark (4 cols) -->
                            <div class="col-span-4 flex flex-col items-center justify-between text-center px-1 border-r border-slate-200">
                                <!-- Subtle security emblem / hologram watermark -->
                                <div class="w-12 h-12 rounded-full border border-amber-400/50 bg-amber-50/40 p-1 flex items-center justify-center shadow-xs">
                                    <div class="w-full h-full rounded-full border border-dashed border-amber-500/60 flex items-center justify-center">
                                        <i class="fa-solid fa-certificate text-amber-500 text-base"></i>
                                    </div>
                                </div>

                                <div class="mt-auto w-full pt-1">
                                    <span class="text-[6px] uppercase tracking-wider text-slate-500 block leading-tight">Issuing Authority</span>
                                    <div class="w-3/4 border-b border-slate-400 mx-auto my-0.5"></div>
                                    <strong class="text-[7.5px] font-black uppercase text-slate-900 block leading-tight">(SGD) DALE GONZALO R. MALAPITAN</strong>
                                    <span class="text-[6.5px] font-bold uppercase text-slate-700 block leading-tight">City Mayor</span>
                                    <span class="text-[5.5px] font-semibold uppercase text-slate-500 block leading-tight mt-0.5">CITY GOVERNMENT OF CALOOCAN</span>
                                </div>
                            </div>

                            <!-- Right Column: Logo, Subtext, Barcode / Control String (3 cols) -->
                            <div class="col-span-3 flex flex-col items-center justify-between text-center pl-1">
                                <div class="flex flex-col items-center">
                                    <img src="../../assets/images/logo.png" onerror="this.src='../assets/images/logo.png'; this.onerror=null;" class="w-10 h-10 object-contain drop-shadow-xs" alt="Civentral Logo" />
                                    <span class="text-[6px] font-bold text-slate-700 uppercase tracking-tight mt-1 leading-tight">
                                        Valid Anywhere in the Philippines
                                    </span>
                                </div>

                                <div class="w-full mt-auto pt-1">
                                    <div class="bg-slate-100 border border-slate-300 rounded px-1 py-0.5">
                                        <span id="cardModalBackBarcode" class="text-[6.5px] font-mono font-bold tracking-widest text-slate-800 block">
                                            01002026000004
                                        </span>
                                    </div>
                                    <span id="cardModalBackControlNum" class="text-[5.5px] font-mono text-slate-500 block mt-0.5">
                                        CAL-2026-000004
                                    </span>
                                </div>
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
                Standard CR80 White PVC Card &bull; Caloocan Civil &amp; Barangay Registry
            </span>
            <div class="flex items-center gap-2">
                <button onclick="closeCitizenCardModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition cursor-pointer">
                    Close
                </button>
                <button onclick="printCitizenCard()" class="px-4 py-2 text-xs font-bold text-white bg-[#0F4C81] hover:bg-sky-800 rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Print Card</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Print Stylesheet: Fits both Front and Back sides onto EXACTLY ONE PAGE -->
<style>
@media print {
  /* Suppress browser margins, headers, and footers */
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

  /* Hide entire dashboard, navigation, backdrop, and modal buttons */
  body * {
    visibility: hidden !important;
  }

  /* Make only the print container visible */
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

  /* Force exact CR80 physical card dimensions (scaled 1.25x for crisp readability) */
  .printable-card-side {
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

  /* Ensure cutting line indicator sits between or around cards */
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
      hotlineLabel: 'BARANGAY HALL',
      hotlinePhone: '(02) 8366-3101',
      hotlineSub: 'Barangay Secretariat',
      validityYears: 1
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
      hotlineLabel: 'PDAO OFFICE',
      hotlinePhone: '(02) 8366-4000',
      hotlineSub: 'Caloocan PDAO Desk',
      validityYears: 3
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
      hotlineLabel: 'OSCA OFFICE',
      hotlinePhone: '(02) 8366-2200',
      hotlineSub: 'Caloocan OSCA Desk',
      validityYears: 0
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
      hotlineLabel: 'CSWDO WELFARE',
      hotlinePhone: '(02) 8366-5000',
      hotlineSub: 'Solo Parent Section',
      validityYears: 1
    };
  }
  if (isNonResident) {
    return {
      title: 'NON-RESIDENT',
      type: 'NON-RESIDENT',
      stops: ['#0F172A', '#334155', '#475569', '#64748B'],
      accentColor: '#475569',
      badgeBg: '#F1F5F9',
      badgeText: '#334155',
      hex: '#475569',
      lightHex: '#64748B',
      darkHex: '#0F172A',
    };
  }
  if (isPwd) {
    return {
      title: 'PWD',
      type: 'PWD',
      stops: ['#7C2D12', '#C2410C', '#EA580C', '#F97316'],
      accentColor: '#EA580C',
      badgeBg: '#FFF7ED',
      badgeText: '#C2410C',
      hex: '#EA580C',
      lightHex: '#F97316',
      darkHex: '#7C2D12',
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
      type: 'SENIOR CITIZEN',
      stops: ['#172554', '#1E3A8A', '#2563EB', '#1D4ED8'],
      accentColor: '#2563EB',
      badgeBg: '#EFF6FF',
      badgeText: '#1E40AF',
      hex: '#2563EB',
      lightHex: '#2563EB',
      darkHex: '#172554',
    };
  }

  if (age < 18) {
    return {
      title: 'MINOR / RESIDENT',
      type: 'MINOR / RESIDENT',
      stops: ['#881337', '#991B1B', '#DC2626', '#B91C1C'],
      accentColor: '#DC2626',
      badgeBg: '#FEF2F2',
      badgeText: '#991B1B',
      hex: '#DC2626',
      lightHex: '#DC2626',
      darkHex: '#881337',
    };
  }

  // General Adult Resident (18-59)
  return {
    title: 'RESIDENT',
    type: 'RESIDENT',
    stops: ['#881337', '#991B1B', '#DC2626', '#B91C1C'],
    accentColor: '#DC2626',
    badgeBg: '#FEF2F2',
    badgeText: '#991B1B',
    hex: '#DC2626',
    lightHex: '#DC2626',
    darkHex: '#881337',
  };
}

const getCitizenClassification = resolveCitizenCategory;

/**
 * Fallback QR Code Renderer (Uses encoded SVG or image)
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
 * Bulletproof, null-safe implementation matching the authentic municipal PVC card blueprint.
 */
function openCitizenCardModal(app) {
    if (!app) return;

    // 0. Compute Dynamic Citizen Classification & Colors
    const category = resolveCitizenCategory(
        app.birth_date,
        !!(app.is_pwd || app.pwd),
        !!(app.is_non_resident || app.non_resident)
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

    // Update Column 1 classification label
    const classifLabel = document.getElementById('cardModalClassification');
    if (classifLabel) {
        classifLabel.textContent = category.title;
        classifLabel.style.color = '#0F172A';
    }

    // Update Modal Header Status Tag/Badge
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
    const idNumber = app.citizen_id_number || app.id || 'CAL-2026-000004';
    const numericSeed = idNumber.replace(/\D/g, '') || '000004';
    setSafeElementText('cardModalBarcodeNum', '0100' + numericSeed.padStart(10, '0'));
    setSafeElementText('cardModalBackBarcode', '0100' + numericSeed.padStart(10, '0'));
    setSafeElementText('cardModalBackControlNum', idNumber);

    // 3. Demographic Information
    setSafeElementText('cardModalDob', app.birth_date ? formatDateDisplay(app.birth_date) : '1998/05/15');
    
    const sex = (app.sex || 'Male').toUpperCase();
    setSafeElementText('cardModalSex', sex.startsWith('F') ? 'F' : 'M');
    
    const civil = (app.civil_status || 'Single').toUpperCase();
    setSafeElementText('cardModalCivil', civil);
    setSafeElementText('cardModalBlood', 'N/A');

    const issuedDate = app.issued_date || app.reviewed_at || app.updated || app.date || '2026/10/04';
    setSafeElementText('cardModalIssued', formatDateDisplay(issuedDate));
    
    const expiryDate = app.valid_until || (function() {
        const d = new Date();
        d.setFullYear(d.getFullYear() + 5);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}/${mm}/${dd}`;
    })();
    setSafeElementText('cardModalExpiry', expiryDate);

    // 4. Address Details (2 lines uppercase)
    const streetParts = [];
    if (app.street_address) streetParts.push(app.street_address.toUpperCase());
    if (app.barangay) streetParts.push((app.barangay.startsWith('Barangay') ? app.barangay : `BARANGAY ${app.barangay}`).toUpperCase());
    if (app.district) streetParts.push(app.district.toUpperCase());
    setSafeElementText('cardModalAddressStreet', streetParts.join(', ') || 'BLOCK 5 LOT 6, BARANGAY 3, DISTRICT 1');
    setSafeElementText('cardModalAddressCity', 'CALOOCAN CITY');

    // 5. Emergency Contact
    setSafeElementText('cardModalEmergency', app.emergency_contact || '(02) 8366-3101');

    // 6. Timestamp at bottom-left corner
    const now = new Date();
    const tsY = now.getFullYear();
    const tsM = String(now.getMonth() + 1).padStart(2, '0');
    const tsD = String(now.getDate()).padStart(2, '0');
    const timeStr = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    setSafeElementText('cardModalTimestamp', `${tsY}/${tsM}/${tsD} ${timeStr}`);

    // 7. Assets: 1x1 Photo (Null-Safe)
    const photoImg = document.getElementById('cardModalPhoto');
    if (photoImg) {
        const photoSrc = resolveCardAssetUrl(app.photo_1x1_url || app.selfie_photo_url || app.avatar);
        photoImg.src = photoSrc || `https://ui-avatars.com/api/?name=${encodeURIComponent(formattedName)}&background=0F4C81&color=fff&size=200`;
    }

    // 8. Assets: Cardholder Digital Signature (Null-Safe)
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

    // 9. Assets: Scannable Vector QR Code Matrix (Local / Standalone)
    const qrPayload = `CIVENTRAL:ID:${idNumber}|TOKEN:${app.qr_code_token || ''}`;

    const qrContainer = document.getElementById('cardModalQrBox');
    if (qrContainer) {
        qrContainer.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            try {
                new QRCode(qrContainer, {
                    text: qrPayload,
                    width: 78,
                    height: 78,
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

    // 10. Display Modal
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
