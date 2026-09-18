<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Department & Auto-Routing Rules Data Reference
$departments = [
    'dpwh' => [
        'name' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'short' => 'DPWH / City Engineering',
        'badge' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
        'icon' => 'fa-solid fa-road',
        'default_priority' => 'High',
        'default_sla' => '24 Hours'
    ],
    'cenro' => [
        'name' => 'Environmental / Waste Management Department (CENRO)',
        'short' => 'CENRO Waste Mgmt',
        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
        'icon' => 'fa-solid fa-recycle',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'flood' => [
        'name' => 'Caloocan Flood Control & Drainage Bureau',
        'short' => 'Flood Control Bureau',
        'badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-800',
        'icon' => 'fa-solid fa-water',
        'default_priority' => 'High',
        'default_sla' => '24 Hours'
    ],
    'electrical' => [
        'name' => 'Public Safety Electrical Division',
        'short' => 'Electrical Division',
        'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800',
        'icon' => 'fa-solid fa-bolt',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'cptmd' => [
        'name' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'short' => 'CPTMD Public Safety',
        'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800',
        'icon' => 'fa-solid fa-shield-halved',
        'default_priority' => 'Urgent',
        'default_sla' => '4 Hours'
    ],
    'cenro_env' => [
        'name' => 'City Environment & Natural Resources Office',
        'short' => 'City Environment Office',
        'badge' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800',
        'icon' => 'fa-solid fa-tree',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours'
    ],
    'assistance' => [
        'name' => 'Caloocan Public Assistance & Grievance Bureau',
        'short' => 'Public Assistance Bureau',
        'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
        'icon' => 'fa-solid fa-handshake-angle',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours'
    ]
];

// Initial Rich Dataset matching all mobile app requirements & Caloocan City locations
$initialConcerns = [
    [
        'id' => 'CAL-REP-2026-4821',
        'title' => 'Deep asphalt crater on Camarin Road causing motor vehicle accidents',
        'description' => 'A dangerous crater-sized pothole has opened up along Camarin Road near Susano Market pedestrian crosswalk. Over the last 48 hours, multiple tricycles and motorbikes have suffered flat tires and near-collisions trying to swerve into oncoming lanes. Immediate asphalt patching is urgently requested before nightfall.',
        'category' => 'Road & Infrastructure',
        'department_key' => 'dpwh',
        'department_name' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'priority' => 'High',
        'stage' => 'in_progress',
        'sla_text' => '⏱ 3h 40m remaining',
        'sla_status' => 'warning', // 'normal', 'warning', 'urgent', 'overdue', 'resolved'
        'sla_total_hours' => 24,
        'date_filed' => '2026-08-25 09:30 AM',
        'citizen_name' => 'Roberto D. Mendoza',
        'citizen_phone' => '+63 917 842 1923',
        'citizen_email' => 'roberto.mendoza@gmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 178 Camarin',
        'landmark' => 'Near Susano Market & Pedestrian Overpass',
        'gps' => '14.7562° N, 121.0438° E',
        'assigned_officer' => 'Engr. Arnel Castillo (Road Maintenance Unit 4)',
        'ai_confidence' => 98,
        'ai_reason' => 'Keywords [pothole, asphalt crater, swerve, road damage] strongly match City Engineering road rehabilitation jurisdiction. High urgency detected from accident hazard mention.',
        'ai_keywords' => ['pothole', 'asphalt crater', 'road damage', 'accident hazard', 'camarin road'],
        'has_duplicate' => true,
        'duplicate_text' => '2 duplicate reports detected within 180m on Camarin Road (CAL-REP-2026-4819, CAL-REP-2026-4824)',
        'photos' => [
            'https://images.unsplash.com/photo-1515162816999-a0c47dc192f7?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1584463699039-383b1451f28b?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-25 09:30 AM', 'actor' => 'Citizen App', 'action' => 'Concern filed into CIVentral system.'],
            ['time' => '2026-08-25 09:31 AM', 'actor' => 'Gemini AI Engine', 'action' => 'Analyzed keywords & classified as Road & Infrastructure (98% confidence).'],
            ['time' => '2026-08-25 09:31 AM', 'actor' => 'Auto-Dispatch Engine', 'action' => 'Automatically routed to City Engineering & Public Works Office (DPWH/CEPO).'],
            ['time' => '2026-08-25 11:15 AM', 'actor' => 'Admin Officer P. Ramos', 'action' => 'Reviewed and assigned to Engr. Arnel Castillo (Unit 4).'],
            ['time' => '2026-08-25 01:45 PM', 'actor' => 'Engr. Arnel Castillo', 'action' => 'Field crew deployed on site with cold-mix asphalt patching equipment.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-8912',
        'title' => 'Fallen lamppost with exposed live wires sparking near pedestrian crossing',
        'description' => 'A streetlamp post collapsed during the strong wind gust earlier today, leaving open high-voltage cables dangling 4 feet above the ground. Sparking occurs intermittently near wet sidewalk puddles. Severe electrocution hazard for schoolchildren.',
        'category' => 'Streetlights',
        'department_key' => 'electrical',
        'department_name' => 'Public Safety Electrical Division',
        'priority' => 'Urgent',
        'stage' => 'under_review',
        'sla_text' => '⏱ 1h 20m remaining',
        'sla_status' => 'urgent',
        'sla_total_hours' => 48,
        'date_filed' => '2026-08-26 08:15 AM',
        'citizen_name' => 'Maria Theresa Ramos',
        'citizen_phone' => '+63 928 554 9012',
        'citizen_email' => 'mt.ramos@yahoo.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 80 Grace Park',
        'landmark' => '8th Avenue corner C-3 Road near Elementary School',
        'gps' => '14.6514° N, 120.9892° E',
        'assigned_officer' => 'Chief Inspector Danilo Cruz (Electrical Emergency Team)',
        'ai_confidence' => 99,
        'ai_reason' => 'Keywords [live wire, sparking, lamppost, electrocution] identified critical safety hazard. Priority elevated to Urgent with 4-hour safety containment protocol.',
        'ai_keywords' => ['live wire', 'sparking', 'lamppost', 'electrocution risk', 'street light'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1544724569-5f546fd6f2b5?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 08:15 AM', 'actor' => 'Citizen App', 'action' => 'Concern filed into system.'],
            ['time' => '2026-08-26 08:16 AM', 'actor' => 'Gemini AI Engine', 'action' => 'High-severity safety triage: Routed to Public Safety Electrical Division.'],
            ['time' => '2026-08-26 08:20 AM', 'actor' => 'System Dispatch', 'action' => 'Automated SMS emergency dispatch sent to Electrical On-Call Crew.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-3104',
        'title' => 'Recurring illegal garbage dump and rotten market waste blocking alleyway',
        'description' => 'Uncollected rotting market garbage bags have accumulated for 4 consecutive days along the Bagumbong Market perimeter. Stray dogs and rodents have ripped bags open, spreading leachate and pungent foul smell into adjacent residential daycare.',
        'category' => 'Garbage & Waste',
        'department_key' => 'cenro',
        'department_name' => 'Environmental / Waste Management Department (CENRO)',
        'priority' => 'Medium',
        'stage' => 'routed',
        'sla_text' => '⏱ 31h remaining',
        'sla_status' => 'normal',
        'sla_total_hours' => 48,
        'date_filed' => '2026-08-26 07:00 AM',
        'citizen_name' => 'Anonymous Resident',
        'citizen_phone' => '+63 919 443 8871',
        'citizen_email' => 'anonymous.citizen@civentral.gov',
        'is_anonymous' => true,
        'barangay' => 'Brgy 171 Bagumbong',
        'landmark' => 'Phase 3 Market Backdoor Alley, near Daycare Center',
        'gps' => '14.7610° N, 121.0385° E',
        'assigned_officer' => 'Unassigned (In CENRO Dispatch Queue)',
        'ai_confidence' => 96,
        'ai_reason' => 'Keywords [garbage, waste dump, uncollected, market trash, leachate] matched CENRO solid waste collection route #12.',
        'ai_keywords' => ['garbage dump', 'uncollected waste', 'foul odor', 'market alley', 'basura'],
        'has_duplicate' => true,
        'duplicate_text' => '2 duplicate complaints detected within 120m at Bagumbong Market Alley (CAL-REP-2026-3098, CAL-REP-2026-3101)',
        'photos' => [
            'https://images.unsplash.com/photo-1605600659908-0ef719419d41?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 07:00 AM', 'actor' => 'Citizen App (Anon)', 'action' => 'Concern submitted anonymously.'],
            ['time' => '2026-08-26 07:01 AM', 'actor' => 'Gemini AI Engine', 'action' => 'Identified Waste Management category and duplicate cluster #08. Automatically routed to CENRO.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-5520',
        'title' => 'Major drainage canal obstruction causing knee-deep flash flooding on 10th Ave',
        'description' => 'The main box culvert canal along 10th Avenue is completely jammed with plastic crates, tree branches, and heavy sediment silt. Light afternoon rain resulted in knee-high floodwaters entering ground-floor commercial shops.',
        'category' => 'Flooding & Drainage',
        'department_key' => 'flood',
        'department_name' => 'Caloocan Flood Control & Drainage Bureau',
        'priority' => 'High',
        'stage' => 'in_progress',
        'sla_text' => '🔴 Overdue by +2h 15m',
        'sla_status' => 'overdue',
        'sla_total_hours' => 24,
        'date_filed' => '2026-08-25 08:00 AM',
        'citizen_name' => 'Bernardo "Jun" Gutierrez',
        'citizen_phone' => '+63 905 671 2290',
        'citizen_email' => 'jun.gutierrez@outlook.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 12',
        'landmark' => '10th Avenue corner 4th Street near Barangay Outpost',
        'gps' => '14.6480° N, 120.9850° E',
        'assigned_officer' => 'Engr. Danilo Santos (Drainage Dredging Team 2)',
        'ai_confidence' => 97,
        'ai_reason' => 'Keywords [flood, canal clogged, drain obstruction, culvert silt] matched Flood Control Bureau high-priority de-clogging division.',
        'ai_keywords' => ['drainage clogged', 'canal overflow', 'flash flooding', '10th avenue', 'box culvert'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1547683905-f686c993aae5?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-25 08:00 AM', 'actor' => 'Citizen Portal', 'action' => 'Concern filed by citizen.'],
            ['time' => '2026-08-25 08:02 AM', 'actor' => 'Gemini AI Engine', 'action' => 'Auto-routed to Caloocan Flood Control Bureau with 24h SLA.'],
            ['time' => '2026-08-25 09:30 AM', 'actor' => 'Admin Officer P. Ramos', 'action' => 'Assigned to Drainage Dredging Team 2.'],
            ['time' => '2026-08-26 08:05 AM', 'actor' => 'System Watchdog', 'action' => 'SLA breach alert triggered (+2h overdue). Escalation SMS sent to Bureau Chief.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-7241',
        'title' => 'Late night videoke brawl and drunken disturbance past quiet hours ordinance',
        'description' => 'Repeated extreme loud music and drunken altercation occurring outside residential compound past 1:00 AM. Multiple senior citizens and working parents unable to rest. Barricading the street with plastic tables.',
        'category' => 'Public Safety',
        'department_key' => 'cptmd',
        'department_name' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'priority' => 'Urgent',
        'stage' => 'under_review',
        'sla_text' => '⏱ 50m remaining',
        'sla_status' => 'urgent',
        'sla_total_hours' => 4,
        'date_filed' => '2026-08-26 09:10 AM',
        'citizen_name' => 'Lourdes San Jose',
        'citizen_phone' => '+63 916 332 8901',
        'citizen_email' => 'lourdes.sanjose@gmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 176 Bagong Silang',
        'landmark' => 'Block 14 Lot 8, Phase 2, near Chapel',
        'gps' => '14.7745° N, 121.0512° E',
        'assigned_officer' => 'Officer Jerome Valdez (CPTMD Sector 3 Patrol)',
        'ai_confidence' => 95,
        'ai_reason' => 'Keywords [videoke, brawl, disturbance, safety, curfew, ordinance violation] assigned to CPTMD Peacekeeping response with 4-hour SLA.',
        'ai_keywords' => ['public disturbance', 'videoke noise', 'brawl', 'ordinance violation', 'curfew'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 09:10 AM', 'actor' => 'Citizen App', 'action' => 'Concern filed.'],
            ['time' => '2026-08-26 09:11 AM', 'actor' => 'Gemini AI Engine', 'action' => 'Classified as Public Safety Ordinance Violation. Auto-routed to CPTMD.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-9033',
        'title' => 'Open burning of hazardous plastic scrap and toxic black smoke emission',
        'description' => 'An unauthorized scrap compound is burning rubber tires and wiring insulation behind the residential rowhouses, emitting dense black toxic smoke that is triggering asthma among local children.',
        'category' => 'Environment',
        'department_key' => 'cenro_env',
        'department_name' => 'City Environment & Natural Resources Office',
        'priority' => 'Medium',
        'stage' => 'submitted',
        'sla_text' => '⏱ 47h remaining',
        'sla_status' => 'normal',
        'sla_total_hours' => 48,
        'date_filed' => '2026-08-26 10:40 AM',
        'citizen_name' => 'Eduardo Cruz',
        'citizen_phone' => '+63 939 128 4402',
        'citizen_email' => 'ecruz_eng@gmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 178 Camarin',
        'landmark' => 'Vacant lot at the end of Deparo Road',
        'gps' => '14.7390° N, 121.0250° E',
        'assigned_officer' => 'Unassigned (Waiting AI Triage Confirmation)',
        'ai_confidence' => 94,
        'ai_reason' => 'Keywords [open burning, toxic smoke, plastic scrap, air pollution] matched Clean Air Act enforcement under City Environment & Natural Resources Office.',
        'ai_keywords' => ['open burning', 'toxic smoke', 'air pollution', 'plastic burning', 'hazard'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 10:40 AM', 'actor' => 'Citizen App', 'action' => 'Concern filed into CIVentral intake queue.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-2180',
        'title' => 'Inquiry for Senior Citizen quarterly medical subsidy and clinic schedule',
        'description' => 'Resident requesting information regarding the release date of Barangay healthcare supplies, vitamin maintenance kits, and schedule of doctor consultations for bedridden seniors.',
        'category' => 'Government Service / General Inquiry',
        'department_key' => 'assistance',
        'department_name' => 'Caloocan Public Assistance & Grievance Bureau',
        'priority' => 'Low',
        'stage' => 'ai_analyzed',
        'sla_text' => '⏱ 68h remaining',
        'sla_status' => 'normal',
        'sla_total_hours' => 72,
        'date_filed' => '2026-08-26 06:15 AM',
        'citizen_name' => 'Flordeliza Santos',
        'citizen_phone' => '+63 920 892 1145',
        'citizen_email' => 'flordeliza.santos@gmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 80 Grace Park',
        'landmark' => 'Barangay Health Center, 5th Avenue',
        'gps' => '14.6530° N, 120.9910° E',
        'assigned_officer' => 'Helpdesk Officer Carla Dizon',
        'ai_confidence' => 92,
        'ai_reason' => 'Keywords [senior citizen, medical subsidy, clinic schedule, inquiry] classified as Public Assistance General Inquiry.',
        'ai_keywords' => ['senior citizen', 'health center', 'medical subsidy', 'inquiry'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 06:15 AM', 'actor' => 'Citizen App', 'action' => 'Inquiry submitted.'],
            ['time' => '2026-08-26 06:16 AM', 'actor' => 'Gemini AI Engine', 'action' => 'Classified as General Inquiry (Low priority, 72h SLA). Ready for routing.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-1144',
        'title' => 'High pressure water main pipe fracture flooding sidewalk near LRT terminal',
        'description' => 'A Maynilad distribution pipe ruptured beneath the pedestrian sidewalk, sending high-pressure water bubbling up and flooding commercial entryways.',
        'category' => 'Road & Infrastructure',
        'department_key' => 'dpwh',
        'department_name' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'priority' => 'High',
        'stage' => 'resolved',
        'sla_text' => '✓ Resolved in 16h (SLA Met)',
        'sla_status' => 'resolved',
        'sla_total_hours' => 24,
        'date_filed' => '2026-08-24 02:00 PM',
        'citizen_name' => 'Joaquin "Ken" Reyes',
        'citizen_phone' => '+63 918 776 5432',
        'citizen_email' => 'ken.reyes@techmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 12',
        'landmark' => '5th Avenue Station North Concourse',
        'gps' => '14.6525° N, 120.9835° E',
        'assigned_officer' => 'Engr. Mark Santos (DPWH Water Works Liaison)',
        'ai_confidence' => 97,
        'ai_reason' => 'Keywords [water pipe burst, flooding sidewalk, maynilad] dispatched to City Engineering Infrastructure Liaison team.',
        'ai_keywords' => ['water main break', 'pipe burst', 'sidewalk flooding', 'maynilad'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => 'Emergency shut-off isolation completed with Maynilad crew. Pipe collar replacement installed and sidewalk trench resurfaced with quick-drying concrete. Safe pedestrian access restored.',
        'resolution_photos' => [
            'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80'
        ],
        'activity_log' => [
            ['time' => '2026-08-24 02:00 PM', 'actor' => 'Citizen App', 'action' => 'Concern filed.'],
            ['time' => '2026-08-24 02:01 PM', 'actor' => 'Gemini AI Engine', 'action' => 'Auto-routed to City Engineering & Public Works.'],
            ['time' => '2026-08-24 03:30 PM', 'actor' => 'Engr. Mark Santos', 'action' => 'On-site excavation and water isolation started.'],
            ['time' => '2026-08-25 06:00 AM', 'actor' => 'Engr. Mark Santos', 'action' => 'Repair verified complete. Concrete poured and cured. Ticket marked RESOLVED.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-6632',
        'title' => 'Overgrown Acacia tree branches dangerously entangled in 220V power lines',
        'description' => 'Large dead branches from an old tree were rubbing against primary power distribution lines during windy nights, causing loud electrical arcs.',
        'category' => 'Environment',
        'department_key' => 'cenro_env',
        'department_name' => 'City Environment & Natural Resources Office',
        'priority' => 'High',
        'stage' => 'closed',
        'sla_text' => '✓ Closed (CSAT 5/5 Stars)',
        'sla_status' => 'closed',
        'sla_total_hours' => 48,
        'date_filed' => '2026-08-23 11:00 AM',
        'citizen_name' => 'Corazon Villanueva',
        'citizen_phone' => '+63 945 221 9087',
        'citizen_email' => 'cora.villanueva@yahoo.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 171 Bagumbong',
        'landmark' => 'Alley 3, Saranay Homes Phase 1',
        'gps' => '14.7595° N, 121.0410° E',
        'assigned_officer' => 'CENRO Urban Forestry Crew Team 1',
        'ai_confidence' => 96,
        'ai_reason' => 'Keywords [tree branches, power line hazard, trimming, tree cutting] routed to CENRO Forestry & Meralco Joint Team.',
        'ai_keywords' => ['tree branches', 'power line hazard', 'tree trimming', 'meralco hazard'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => 'Coordinated branch pruning completed with utility bucket truck. Hazardous branches cleared and hauled away. Citizen confirmed completion with 5-star rating.',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-23 11:00 AM', 'actor' => 'Citizen App', 'action' => 'Concern filed.'],
            ['time' => '2026-08-24 09:00 AM', 'actor' => 'CENRO Team 1', 'action' => 'Tree pruning operations completed.'],
            ['time' => '2026-08-24 02:00 PM', 'actor' => 'Citizen', 'action' => 'Citizen verified work and submitted CSAT (5/5 Stars). Ticket closed.']
        ]
    ],
    [
        'id' => 'CAL-REP-2026-9901',
        'title' => 'Unlicensed sidewalk auto repair shop dumping waste oil into storm gutters',
        'description' => 'An unauthorized roadside mechanic is occupying the entire sidewalk along Monumento circle, forcing pedestrians into busy traffic and pouring black waste motor oil straight into storm drains.',
        'category' => 'Public Safety',
        'department_key' => 'cptmd',
        'department_name' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'priority' => 'Urgent',
        'stage' => 'routed',
        'sla_text' => '🔴 Overdue by +45m',
        'sla_status' => 'overdue',
        'sla_total_hours' => 4,
        'date_filed' => '2026-08-26 06:30 AM',
        'citizen_name' => 'Vicente Morales',
        'citizen_phone' => '+63 917 112 3499',
        'citizen_email' => 'vicente.m@gmail.com',
        'is_anonymous' => false,
        'barangay' => 'Brgy 80 Grace Park',
        'landmark' => 'Samson Road cor. Monumento Circle near Pedestrian Footbridge',
        'gps' => '14.6570° N, 120.9840° E',
        'assigned_officer' => 'CPTMD Traffic & Sidewalk Clearing Division',
        'ai_confidence' => 95,
        'ai_reason' => 'Keywords [sidewalk obstruction, illegal shop, waste oil, public safety, traffic hazard] assigned to CPTMD rapid clearing team.',
        'ai_keywords' => ['sidewalk obstruction', 'illegal auto shop', 'waste oil dump', 'safety hazard'],
        'has_duplicate' => false,
        'duplicate_text' => '',
        'photos' => [
            'https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=600&q=80'
        ],
        'resolution_notes' => '',
        'resolution_photos' => [],
        'activity_log' => [
            ['time' => '2026-08-26 06:30 AM', 'actor' => 'Citizen Portal', 'action' => 'Concern filed.'],
            ['time' => '2026-08-26 06:31 AM', 'actor' => 'Gemini AI Engine', 'action' => 'High urgency auto-routed to CPTMD Sidewalk Clearing Division.']
        ]
    ]
];

// Initial Routing Rules Dataset
$routingRules = [
    [
        'category' => 'Road & Infrastructure',
        'keywords' => ['pothole', 'road', 'bridge', 'crack', 'asphalt', 'crater', 'pavement'],
        'department_key' => 'dpwh',
        'department_name' => 'City Engineering & Public Works Office (DPWH/CEPO)',
        'default_priority' => 'High',
        'default_sla' => '24 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Garbage & Waste',
        'keywords' => ['garbage', 'waste', 'trash', 'dump', 'basura', 'odor', 'leachate', 'uncollected'],
        'department_key' => 'cenro',
        'department_name' => 'Environmental / Waste Management Department (CENRO)',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Flooding & Drainage',
        'keywords' => ['flood', 'drain', 'canal', 'waterlog', 'culvert', 'clogged', 'gutter'],
        'department_key' => 'flood',
        'department_name' => 'Caloocan Flood Control & Drainage Bureau',
        'default_priority' => 'High',
        'default_sla' => '24 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Streetlights',
        'keywords' => ['light', 'dark', 'lamp', 'post', 'wire', 'sparking', 'electric', 'bulb'],
        'department_key' => 'electrical',
        'department_name' => 'Public Safety Electrical Division',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Public Safety',
        'keywords' => ['safety', 'police', 'hazard', 'theft', 'crime', 'brawl', 'curfew', 'videoke', 'disturbance'],
        'department_key' => 'cptmd',
        'department_name' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
        'default_priority' => 'Urgent',
        'default_sla' => '4 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Environment',
        'keywords' => ['tree', 'smoke', 'pollution', 'air', 'burning', 'toxic', 'branch'],
        'department_key' => 'cenro_env',
        'department_name' => 'City Environment & Natural Resources Office',
        'default_priority' => 'Medium',
        'default_sla' => '48 Hours',
        'is_active' => true
    ],
    [
        'category' => 'Government Service / General Inquiry',
        'keywords' => ['inquiry', 'assistance', 'subsidy', 'clinic', 'program', 'schedule', 'certificate', 'help'],
        'department_key' => 'assistance',
        'department_name' => 'Caloocan Public Assistance & Grievance Bureau',
        'default_priority' => 'Low',
        'default_sla' => '72 Hours',
        'is_active' => true
    ]
];

// Load Live Concerns from MySQL Database (Replaces Mock Data)
require_once __DIR__ . '/../../config/database.php';
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM `citizen_concerns` ORDER BY `concern_id` DESC");
    $dbRows = $stmt->fetchAll();
    if (!empty($dbRows)) {
        $liveConcerns = [];
        foreach ($dbRows as $row) {
            $cat = $row['category'];
            $deptKey = 'assistance';
            if (stripos($cat, 'Road') !== false || stripos($cat, 'Infra') !== false) $deptKey = 'dpwh';
            else if (stripos($cat, 'Garbage') !== false || stripos($cat, 'Waste') !== false || stripos($cat, 'Sanitation') !== false) $deptKey = 'cenro';
            else if (stripos($cat, 'Flood') !== false || stripos($cat, 'Drain') !== false) $deptKey = 'flood';
            else if (stripos($cat, 'Light') !== false) $deptKey = 'electrical';
            else if (stripos($cat, 'Safety') !== false || stripos($cat, 'Police') !== false) $deptKey = 'cptmd';
            else if (stripos($cat, 'Environment') !== false) $deptKey = 'cenro_env';

            $stage = 'new';
            $st = $row['status'];
            if ($st === 'Under Review') $stage = 'under_review';
            else if ($st === 'Routed') $stage = 'routed';
            else if ($st === 'In Progress') $stage = 'in_progress';
            else if ($st === 'Resolved') $stage = 'resolved';
            else if ($st === 'Closed') $stage = 'closed';

            $liveConcerns[] = [
                'id' => $row['ticket_number'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category' => $row['category'],
                'department_key' => $deptKey,
                'department_name' => $row['assigned_department'] ?: ($departments[$deptKey]['name'] ?? 'City Engineering & Public Works Office'),
                'priority' => $row['priority'],
                'stage' => $stage,
                'sla_text' => '⏱ 24h SLA',
                'sla_status' => $row['priority'] === 'Urgent' ? 'urgent' : 'normal',
                'sla_total_hours' => 24,
                'date_filed' => date('Y-m-d h:i A', strtotime($row['created_at'])),
                'citizen_name' => $row['is_anonymous'] ? 'Anonymous Resident' : $row['citizen_name'],
                'citizen_phone' => $row['citizen_phone'] ?: '',
                'citizen_email' => $row['citizen_email'] ?: '',
                'is_anonymous' => (bool)$row['is_anonymous'],
                'barangay' => $row['barangay'] ?: 'Caloocan City',
                'landmark' => $row['location'],
                'gps' => $row['gps_coordinates'] ?: '14.6514° N, 120.9892° E',
                'assigned_officer' => !empty($row['assigned_department']) ? "Officer ({$row['assigned_department']})" : 'Pending Assignment',
                'ai_confidence' => 96,
                'ai_reason' => 'Processed from citizen submission in Caloocan CIVentral network.',
                'ai_keywords' => array_values(array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9 ]/', '', $row['title'])))),
                'has_duplicate' => false,
                'duplicate_text' => '',
                'photos' => !empty($row['photo_evidence_url']) ? [$row['photo_evidence_url']] : [],
                'resolution_notes' => $row['resolution_notes'] ?: '',
                'resolution_photos' => [],
                'activity_log' => [
                    ['time' => date('Y-m-d h:i A', strtotime($row['created_at'])), 'actor' => 'Citizen Mobile App', 'action' => 'Concern filed into CIVentral system.']
                ]
            ];
        }
        $initialConcerns = $liveConcerns;
    }
} catch (Exception $e) {
    // Keep fallback
}
?>

<!-- Custom Dashboard & Control Center Styles -->
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
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #475569;
    }
    @keyframes pulse-subtle {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.85; transform: scale(1.02); }
    }
    .animate-pulse-subtle {
        animation: pulse-subtle 2.5s infinite ease-in-out;
    }
    .glass-card {
        backdrop-filter: blur(12px);
    }
</style>

<main class="flex-1 p-4 md:p-6 lg:p-8 w-full overflow-y-auto bg-slate-50/60 dark:bg-slate-950 min-h-[calc(100vh-4rem)] space-y-6 transition-colors duration-200">

    <!-- Top Action & Title Header Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-slate-800 pb-5">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-xl shadow-lg shadow-blue-500/20 ring-4 ring-blue-50 dark:ring-slate-800 shrink-0">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400 mb-0.5">
                    <span>Feedback & Grievance</span>
                    <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
                    <span class="text-blue-600 dark:text-blue-400">Automated Dispatch Engine</span>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 flex-wrap">
                    <span>Automated Concern Routing & Dispatch Center</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                        <span>Gemini AI Active</span>
                    </span>
                </h1>
            </div>
        </div>

        <!-- Quick Top Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <button onclick="openRulesConfigModal()" class="px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-400 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs hover:shadow-sm transition-all flex items-center gap-2 cursor-pointer group">
                <i class="fa-solid fa-sliders text-blue-600 dark:text-blue-400 group-hover:rotate-45 transition-transform duration-300"></i>
                <span>Routing Rules Config</span>
            </button>

            <button onclick="openAddWalkInModal()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-500/20 hover:shadow-blue-500/30 transition-all flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus-circle text-sm"></i>
                <span>Add Walk-In / Manual Report</span>
            </button>

            <button onclick="exportConcernsToCSV()" class="px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer" title="Export Filtered Concerns to CSV">
                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                <span class="hidden sm:inline">Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Real-time Dynamic KPI Analytics Row (5 Modern Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- KPI 1: Active Dispatches -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-4 flex items-center justify-between hover:border-blue-300 dark:hover:border-blue-700 transition">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Active Pipeline</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 id="kpiTotalActive" class="text-2xl font-black text-slate-900 dark:text-white">8</h3>
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded-md">Live</span>
                </div>
                <span class="text-[10px] text-slate-500 font-medium">In Caloocan System</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg border border-blue-100 dark:border-blue-800 shrink-0">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
        </div>

        <!-- KPI 2: AI Auto-Routed Today -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-4 flex items-center justify-between hover:border-indigo-300 dark:hover:border-indigo-700 transition">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">AI Auto-Routed Today</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 id="kpiAiRouted" class="text-2xl font-black text-indigo-600 dark:text-indigo-400">96.4%</h3>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-1.5 py-0.5 rounded-md">+3 today</span>
                </div>
                <span class="text-[10px] text-slate-500 font-medium">Gemini Match Accuracy</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg border border-indigo-100 dark:border-indigo-800 shrink-0">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
        </div>

        <!-- KPI 3: Urgent & High Priority Queue -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-4 flex items-center justify-between hover:border-rose-300 dark:hover:border-rose-700 transition">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Urgent & High Queue</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 id="kpiUrgentHigh" class="text-2xl font-black text-rose-600 dark:text-rose-400">5</h3>
                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-900/30 px-1.5 py-0.5 rounded-md animate-pulse">Fast-Track</span>
                </div>
                <span class="text-[10px] text-slate-500 font-medium">Immediate Action Required</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg border border-rose-100 dark:border-rose-800 shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- KPI 4: Overdue & SLA Warnings -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-4 flex items-center justify-between hover:border-amber-300 dark:hover:border-amber-700 transition">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Near / Overdue SLA</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 id="kpiSlaBreaches" class="text-2xl font-black text-amber-600 dark:text-amber-400">2</h3>
                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-1.5 py-0.5 rounded-md">Alert</span>
                </div>
                <span class="text-[10px] text-slate-500 font-medium">1 Overdue • 1 Warning</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg border border-amber-100 dark:border-amber-800 shrink-0">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <!-- KPI 5: Resolution Rate (This Month) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-4 flex items-center justify-between hover:border-emerald-300 dark:hover:border-emerald-700 transition">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Resolution Rate</span>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 id="kpiResolutionRate" class="text-2xl font-black text-emerald-600 dark:text-emerald-400">89.4%</h3>
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 px-1.5 py-0.5 rounded-md">Aug 2026</span>
                </div>
                <span class="text-[10px] text-slate-500 font-medium">Avg Resolution: 18.2 hrs</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg border border-emerald-100 dark:border-emerald-800 shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

    </div>

    <!-- Search, Filter & View Controls Hub -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-xs space-y-3.5">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            
            <!-- Universal Search Bar -->
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="universalSearchInput" oninput="applyFilters()" placeholder="Search by Reference ID (e.g. CAL-REP-2026-4821), Citizen Name, Keyword, or Landmark..." class="w-full bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 placeholder-slate-400 text-xs rounded-xl pl-9 pr-8 py-2.5 outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:bg-white dark:focus:bg-slate-800 transition">
                <button id="clearSearchBtn" onclick="clearUniversalSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs cursor-pointer">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <!-- View Switcher & Counter -->
            <div class="flex items-center gap-3 justify-between sm:justify-end shrink-0">
                <span id="filteredResultsCounter" class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Showing <span class="text-blue-600 dark:text-blue-400 font-extrabold" id="visibleRowsCount">10</span> concerns
                </span>

                <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                    <button id="tableViewBtn" onclick="setViewMode('table')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs cursor-pointer">
                        <i class="fa-solid fa-table-list"></i>
                        <span>Queue Table</span>
                    </button>
                    <button id="boardViewBtn" onclick="setViewMode('board')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white cursor-pointer">
                        <i class="fa-solid fa-columns"></i>
                        <span>Lifecycle Board</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Filter Selectors Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            
            <!-- Filter by Department -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Target Department</label>
                <select id="filterDepartment" onchange="applyFilters()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="all">All Departments (7 Offices)</option>
                    <option value="dpwh">City Engineering & Public Works (DPWH)</option>
                    <option value="cenro">CENRO Waste Management</option>
                    <option value="flood">Caloocan Flood Control Bureau</option>
                    <option value="electrical">Public Safety Electrical Division</option>
                    <option value="cptmd">CPTMD Public Safety & Police</option>
                    <option value="cenro_env">City Environment & Natural Resources</option>
                    <option value="assistance">Public Assistance & Grievance</option>
                </select>
            </div>

            <!-- Filter by Priority -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Priority Level</label>
                <select id="filterPriority" onchange="applyFilters()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="all">All Priorities</option>
                    <option value="Urgent">Urgent (4h SLA)</option>
                    <option value="High">High (24h SLA)</option>
                    <option value="Medium">Medium (48h SLA)</option>
                    <option value="Low">Low (72h SLA)</option>
                </select>
            </div>

            <!-- Filter by Lifecycle Stage -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">7-Stage Lifecycle</label>
                <select id="filterStage" onchange="applyFilters()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="all">All Lifecycle Stages</option>
                    <option value="submitted">1. Submitted (Pending Triage)</option>
                    <option value="ai_analyzed">2. AI Analyzed</option>
                    <option value="routed">3. Automatically Routed</option>
                    <option value="under_review">4. Under Review (Assigned)</option>
                    <option value="in_progress">5. In Progress (Field Active)</option>
                    <option value="resolved">6. Resolved</option>
                    <option value="closed">7. Closed / Verified</option>
                </select>
            </div>

            <!-- Filter by Barangay -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Barangay Jurisdiction</label>
                <select id="filterBarangay" onchange="applyFilters()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="all">All Barangays (Caloocan)</option>
                    <option value="Brgy 178 Camarin">Brgy 178 Camarin</option>
                    <option value="Brgy 171 Bagumbong">Brgy 171 Bagumbong</option>
                    <option value="Brgy 80 Grace Park">Brgy 80 Grace Park</option>
                    <option value="Brgy 12">Brgy 12 (10th Avenue)</option>
                    <option value="Brgy 176 Bagong Silang">Brgy 176 Bagong Silang</option>
                </select>
            </div>

        </div>

    </div>

    <!-- MAIN VIEW CONTAINER 1: QUEUE TABLE VIEW -->
    <div id="queueTableViewContainer" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-extrabold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Ref ID & Date</th>
                        <th class="py-3.5 px-4">Citizen & Barangay</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Concern & AI Triage Match</th>
                        <th class="py-3.5 px-4">Target Department</th>
                        <th class="py-3.5 px-4 text-center">Priority</th>
                        <th class="py-3.5 px-4">SLA Countdown</th>
                        <th class="py-3.5 px-4 text-center">Lifecycle Stage</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="concernsTableBody" class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    <!-- Injected dynamically by JavaScript for reactive real-time updates -->
                </tbody>
            </table>
        </div>

        <!-- Empty State Container -->
        <div id="tableEmptyState" class="hidden py-16 px-4 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 mx-auto flex items-center justify-center text-2xl">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-white">No matching grievances found</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Try adjusting your keyword search, department, priority, or lifecycle stage filters.</p>
            <button onclick="resetAllFilters()" class="px-4 py-2 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 font-bold text-xs rounded-xl hover:bg-blue-100 transition cursor-pointer">
                Reset All Filters
            </button>
        </div>
    </div>

    <!-- MAIN VIEW CONTAINER 2: 7-STAGE LIFECYCLE KANBAN BOARD VIEW (Hidden by default) -->
    <div id="lifecycleBoardViewContainer" class="hidden space-y-4">
        
        <div class="flex items-center justify-between text-xs px-1">
            <span class="text-slate-500 font-semibold">7-Stage Grievance Dispatch Lifecycle Board:</span>
            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold">Real-time status synchronization</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3.5 items-start">
            
            <!-- Column 1: Submitted -->
            <div class="bg-slate-100/70 dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                    <span class="text-[11px] font-black uppercase text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span> 1. Submitted
                    </span>
                    <span id="boardCount-submitted" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">0</span>
                </div>
                <div id="boardColumn-submitted" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 2: AI Analyzed -->
            <div class="bg-indigo-50/40 dark:bg-indigo-950/20 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-indigo-100 dark:border-indigo-900/40 pb-2">
                    <span class="text-[11px] font-black uppercase text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span> 2. AI Analyzed
                    </span>
                    <span id="boardCount-ai_analyzed" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300">0</span>
                </div>
                <div id="boardColumn-ai_analyzed" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 3: Auto-Routed -->
            <div class="bg-blue-50/40 dark:bg-blue-950/20 rounded-2xl border border-blue-100 dark:border-blue-900/40 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-blue-100 dark:border-blue-900/40 pb-2">
                    <span class="text-[11px] font-black uppercase text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> 3. Routed
                    </span>
                    <span id="boardCount-routed" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">0</span>
                </div>
                <div id="boardColumn-routed" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 4: Under Review -->
            <div class="bg-amber-50/40 dark:bg-amber-950/20 rounded-2xl border border-amber-100 dark:border-amber-900/40 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-amber-100 dark:border-amber-900/40 pb-2">
                    <span class="text-[11px] font-black uppercase text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> 4. Review
                    </span>
                    <span id="boardCount-under_review" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300">0</span>
                </div>
                <div id="boardColumn-under_review" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 5: In Progress -->
            <div class="bg-cyan-50/40 dark:bg-cyan-950/20 rounded-2xl border border-cyan-100 dark:border-cyan-900/40 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-cyan-100 dark:border-cyan-900/40 pb-2">
                    <span class="text-[11px] font-black uppercase text-cyan-700 dark:text-cyan-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-cyan-500"></span> 5. In Progress
                    </span>
                    <span id="boardCount-in_progress" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-cyan-100 dark:bg-cyan-900/50 text-cyan-800 dark:text-cyan-300">0</span>
                </div>
                <div id="boardColumn-in_progress" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 6: Resolved -->
            <div class="bg-emerald-50/40 dark:bg-emerald-950/20 rounded-2xl border border-emerald-100 dark:border-emerald-900/40 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-emerald-100 dark:border-emerald-900/40 pb-2">
                    <span class="text-[11px] font-black uppercase text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 6. Resolved
                    </span>
                    <span id="boardCount-resolved" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300">0</span>
                </div>
                <div id="boardColumn-resolved" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

            <!-- Column 7: Closed -->
            <div class="bg-slate-100/50 dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800 p-3 space-y-3 flex flex-col min-h-[480px]">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                    <span class="text-[11px] font-black uppercase text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-500"></span> 7. Closed
                    </span>
                    <span id="boardCount-closed" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400">0</span>
                </div>
                <div id="boardColumn-closed" class="space-y-2.5 flex-1 overflow-y-auto custom-scrollbar max-h-[640px]"></div>
            </div>

        </div>

    </div>

</main>

<!-- ========================================================================= -->
<!-- 1. COMPREHENSIVE CONCERN DETAIL & DISPATCH CONTROL HUB MODAL (CORE MODAL) -->
<!-- ========================================================================= -->
<div id="detailModal" class="hidden fixed inset-0 z-[110] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-5xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-auto">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40 flex items-center justify-between gap-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shadow-sm shrink-0">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span id="modalRefId" class="text-sm font-black text-blue-700 dark:text-blue-400 tracking-tight font-mono">CAL-REP-2026-4821</span>
                        <span id="modalPriorityBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider">High Priority</span>
                        <span id="modalStageBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider">In Progress</span>
                    </div>
                    <span id="modalDateFiled" class="text-[11px] text-slate-400 font-medium mt-0.5 block">Filed on Aug 25, 2026 • 09:30 AM</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="printConcernSummary()" class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer" title="Print Concern Record">
                    <i class="fa-solid fa-print text-sm"></i>
                </button>
                <button onclick="closeDetailModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Modal Scrollable Content Body -->
        <div class="p-6 overflow-y-auto custom-scrollbar space-y-6 flex-1 text-xs">
            
            <!-- Visual 7-Stage Horizontal Timeline Tracker -->
            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Concern Lifecycle Progress Tracker</span>
                    <span id="modalTimelineStageSummary" class="text-[11px] font-bold text-blue-600 dark:text-blue-400">Stage 5 of 7: In Progress</span>
                </div>

                <!-- 7 Stages Visual Step Bar -->
                <div class="grid grid-cols-7 gap-1.5 pt-1" id="modalStageTrackerBar">
                    <!-- Injected by JS -->
                </div>
            </div>

            <!-- Duplicate / Proximity Alert Banner (If Applicable) -->
            <div id="modalDuplicateAlert" class="hidden p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-amber-200 dark:bg-amber-800 text-amber-700 dark:text-amber-200 flex items-center justify-center text-xs shrink-0 mt-0.5">
                        <i class="fa-solid fa-clone"></i>
                    </div>
                    <div>
                        <h5 class="font-black text-xs">Proximity Duplicate Detected by Gemini Geo-Cluster</h5>
                        <p id="modalDuplicateText" class="text-[11px] text-amber-700 dark:text-amber-300 mt-0.5 font-medium">2 duplicate reports detected within 180m radius.</p>
                    </div>
                </div>
                <button onclick="mergeDuplicateReports()" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] rounded-lg shadow-xs transition shrink-0 cursor-pointer flex items-center gap-1">
                    <i class="fa-solid fa-code-merge text-[10px]"></i> Merge Duplicate Cluster
                </button>
            </div>

            <!-- Two-Column Grid: Left (Citizen & Concern Details) | Right (AI Triage & Dispatch Assignment) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left Column (7 Cols) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Concern Full Details Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Citizen Concern Narrative</span>
                            <span id="modalCategoryBadge" class="px-2.5 py-0.5 rounded-md font-bold text-[10px] border">Road & Infrastructure</span>
                        </div>

                        <h3 id="modalTitle" class="text-sm md:text-base font-black text-slate-900 dark:text-white leading-snug">
                            Deep asphalt crater on Camarin Road causing motor vehicle accidents
                        </h3>

                        <p id="modalDescription" class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                            A dangerous crater-sized pothole has opened up along Camarin Road...
                        </p>

                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                            <i class="fa-solid fa-location-dot text-rose-500"></i>
                            <span id="modalLocationText" class="font-bold text-slate-700 dark:text-slate-200">Near Susano Market & Pedestrian Overpass, Brgy 178 Camarin</span>
                        </div>

                        <!-- Evidence Photo Gallery -->
                        <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Photo Evidence (<span id="modalPhotosCount">2</span>)</span>
                            <div id="modalPhotoGallery" class="flex items-center gap-2.5 flex-wrap">
                                <!-- Photo thumbnails injected here -->
                            </div>
                        </div>
                    </div>

                    <!-- Citizen Profile Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <h4 class="text-xs font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-id-card text-blue-600"></i>
                                <span>Complainant Citizen Profile</span>
                            </h4>
                            <span id="modalAnonBadge" class="text-[10px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">Verified Citizen</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Full Name</span>
                                <span id="modalCitizenName" class="font-bold text-slate-800 dark:text-slate-200">Roberto D. Mendoza</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Contact Number</span>
                                <span id="modalCitizenPhone" class="font-bold text-slate-800 dark:text-slate-200 font-mono">+63 917 842 1923</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Email Address</span>
                                <span id="modalCitizenEmail" class="font-bold text-slate-800 dark:text-slate-200 truncate block">roberto.mendoza@gmail.com</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">GPS Coordinates Pin</span>
                                <div class="flex items-center gap-1.5">
                                    <span id="modalGpsPin" class="font-mono font-bold text-blue-600 dark:text-blue-400 text-[11px]">14.7562° N, 121.0438° E</span>
                                    <button onclick="copyGpsPin()" class="text-slate-400 hover:text-blue-600 transition" title="Copy Pin"><i class="fa-solid fa-copy text-[10px]"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Log / Audit Trail -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 shadow-xs">
                        <h4 class="text-xs font-black text-slate-900 dark:text-white flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                                <span>Internal Audit Trail & Activity Log</span>
                            </span>
                            <span class="text-[10px] font-bold text-slate-400">Auto-Logged</span>
                        </h4>

                        <div id="modalActivityLogList" class="space-y-2.5 max-h-[160px] overflow-y-auto custom-scrollbar pr-1">
                            <!-- Injected by JS -->
                        </div>
                    </div>

                </div>

                <!-- Right Column (5 Cols) -->
                <div class="lg:col-span-5 space-y-5">
                    
                    <!-- AI Multi-Modal Triage Card -->
                    <div class="bg-gradient-to-br from-indigo-50/80 to-blue-50/80 dark:from-slate-800/90 dark:to-indigo-950/40 rounded-2xl border border-indigo-100 dark:border-indigo-900/60 p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-indigo-900 dark:text-indigo-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-brain text-indigo-600 dark:text-indigo-400"></i>
                                <span>Gemini AI Triage Engine</span>
                            </span>
                            <span id="modalAiConfidenceBadge" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white shadow-xs">
                                98% Match
                            </span>
                        </div>

                        <!-- Confidence Bar -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold text-indigo-700 dark:text-indigo-300">
                                <span>Classification Confidence</span>
                                <span id="modalConfidenceScore">98%</span>
                            </div>
                            <div class="w-full h-2 bg-indigo-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div id="modalConfidenceBar" class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500" style="width: 98%"></div>
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase text-indigo-800 dark:text-indigo-300 block mb-1">Detected Keywords:</span>
                            <div id="modalAiKeywordsList" class="flex flex-wrap gap-1">
                                <!-- Injected by JS -->
                            </div>
                        </div>

                        <div class="bg-white/80 dark:bg-slate-900/80 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900 text-[11px] text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                            <span class="font-black text-indigo-700 dark:text-indigo-400 block mb-0.5">Triage Rationale:</span>
                            <span id="modalAiReasonText">Keywords strongly match City Engineering road rehabilitation jurisdiction.</span>
                        </div>
                    </div>

                    <!-- Department Assignment & SLA Panel -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3.5 shadow-xs">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h4 class="text-xs font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-building-user text-blue-600"></i>
                                <span>Department & SLA Controls</span>
                            </h4>
                            <span id="modalSlaStatusPill" class="px-2 py-0.5 rounded-md text-[10px] font-bold">⏱ 3h 40m remaining</span>
                        </div>

                        <!-- Target Department (with Re-Route Override) -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned Department</label>
                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold">Auto-Routed</span>
                            </div>
                            <select id="modalDepartmentSelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                                <option value="dpwh">City Engineering & Public Works (DPWH)</option>
                                <option value="cenro">CENRO Waste Management</option>
                                <option value="flood">Caloocan Flood Control Bureau</option>
                                <option value="electrical">Public Safety Electrical Division</option>
                                <option value="cptmd">CPTMD Public Safety & Police</option>
                                <option value="cenro_env">City Environment & Natural Resources</option>
                                <option value="assistance">Public Assistance & Grievance</option>
                            </select>
                        </div>

                        <!-- Action Officer / Field Unit Assigned -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Assigned Action Officer / Field Unit</label>
                            <input type="text" id="modalAssignedOfficerInput" placeholder="e.g. Engr. Arnel Castillo (Unit 4)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 outline-none font-medium text-xs focus:border-blue-500">
                        </div>

                        <!-- Priority Selector -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Priority Level & SLA Matrix</label>
                            <select id="modalPrioritySelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                                <option value="Urgent">Urgent — 4 Hours SLA</option>
                                <option value="High">High — 24 Hours SLA</option>
                                <option value="Medium">Medium — 48 Hours SLA</option>
                                <option value="Low">Low — 72 Hours SLA</option>
                            </select>
                        </div>

                        <button onclick="saveAssignmentChanges()" class="w-full py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk text-xs text-blue-600"></i>
                            <span>Save Department & Officer Assignment</span>
                        </button>
                    </div>

                    <!-- Workflow Stage Advance Action Box -->
                    <div class="bg-blue-50/60 dark:bg-blue-950/30 rounded-2xl border border-blue-200 dark:border-blue-900/60 p-4 space-y-3">
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-800 dark:text-blue-300 block">Advance Workflow Action</span>
                        <div id="modalStageActionContainer" class="space-y-2">
                            <!-- Injected dynamically by JS based on current stage -->
                        </div>
                    </div>

                </div>

            </div>

            <!-- Resolution Notes & Evidence Section (Shown when In Progress, Resolved, or Closed) -->
            <div id="modalResolutionSection" class="hidden bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2">
                    <h4 class="text-xs font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-emerald-600"></i>
                        <span>Resolution Summary & Proof of Work</span>
                    </h4>
                    <span class="text-[10px] text-emerald-600 font-bold">Field Verification</span>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Action Taken & Final Resolution Notes:</label>
                    <textarea id="modalResolutionNotes" rows="2" placeholder="Describe the physical repair, sanitation haul, or peacekeeping action performed by field team..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-3 text-xs outline-none focus:border-emerald-500 font-medium"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input type="checkbox" id="modalNotifyCitizenCheckbox" checked class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer">
                        <span>Send SMS & In-App resolution notification to Citizen</span>
                    </label>

                    <button onclick="simulateResolutionPhotoUpload()" class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-emerald-400 text-slate-700 dark:text-slate-200 font-bold text-[11px] rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-camera text-emerald-600"></i>
                        <span>Upload Proof Photo</span>
                    </button>
                </div>

                <div id="modalResolutionPhotosGallery" class="flex items-center gap-2 pt-1 flex-wrap"></div>
            </div>

        </div>

        <!-- Modal Footer Actions -->
        <div class="px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/40 flex items-center justify-between gap-3 shrink-0">
            <span class="text-[11px] text-slate-500 font-medium hidden sm:inline">CIVentral Caloocan Dispatch Control Engine v2.4</span>
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button onclick="closeDetailModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer">
                    Close Window
                </button>
                <button id="modalPrimaryActionButton" onclick="advanceActiveTicketStage()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <span>Advance Stage</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. ROUTING RULES CONFIGURATOR MODAL                                       -->
<!-- ========================================================================= -->
<div id="rulesConfigModal" class="hidden fixed inset-0 z-[120] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-auto">
        
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400 flex items-center justify-center text-base border border-blue-100 dark:border-blue-800 shrink-0">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Automated Concern Routing Rules Engine</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Manage AI keyword trigger mappings, assigned bureaus, default urgency, and SLA targets.</p>
                </div>
            </div>
            <button onclick="closeRulesConfigModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar space-y-5 text-xs flex-1">
            
            <!-- AI Classification Tester Sandbox -->
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-indigo-950/40 rounded-2xl border border-blue-200/80 dark:border-blue-800 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-blue-900 dark:text-blue-200 flex items-center gap-1.5">
                        <i class="fa-solid fa-vial text-blue-600"></i>
                        <span>Live AI Routing Simulation Sandbox</span>
                    </span>
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">Gemini Keyword Parser</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="aiTestInput" placeholder="Type a test complaint (e.g. 'Clogged canal flood on 10th Ave' or 'Broken streetlight')..." class="flex-1 bg-white dark:bg-slate-900 border border-blue-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs outline-none text-slate-800 dark:text-slate-200 focus:border-blue-500 font-medium">
                    <button onclick="testAiRoutingRule()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
                        Test Route
                    </button>
                </div>
                <div id="aiTestResult" class="hidden text-[11px] font-bold text-slate-700 dark:text-slate-200 bg-white/90 dark:bg-slate-900/90 p-2.5 rounded-xl border border-blue-100 dark:border-slate-700 flex items-center justify-between">
                    <!-- Injected by JS -->
                </div>
            </div>

            <!-- Rules Table -->
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                            <th class="py-3 px-3.5">Category</th>
                            <th class="py-3 px-3.5">Trigger Keywords</th>
                            <th class="py-3 px-3.5">Target Assigned Bureau</th>
                            <th class="py-3 px-3.5">Default Priority</th>
                            <th class="py-3 px-3.5">SLA Target</th>
                            <th class="py-3 px-3.5 text-center">AI Auto-Dispatch</th>
                        </tr>
                    </thead>
                    <tbody id="rulesTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        <!-- Injected by JS -->
                    </tbody>
                </table>
            </div>

        </div>

        <div class="px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-end gap-2 shrink-0">
            <button onclick="closeRulesConfigModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                Close Configurator
            </button>
            <button onclick="saveRulesConfig()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition cursor-pointer">
                Save Rule Policies
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. ADD WALK-IN / MANUAL CONCERN REPORT MODAL                              -->
<!-- ========================================================================= -->
<div id="addWalkInModal" class="hidden fixed inset-0 z-[120] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-auto">
        
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-base shadow-sm shrink-0">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Add Walk-In / Manual Citizen Grievance</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Record a walk-in citizen complaint with automated AI routing assist.</p>
                </div>
            </div>
            <button onclick="closeAddWalkInModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="addWalkInForm" onsubmit="submitNewWalkInReport(event)" class="p-6 overflow-y-auto custom-scrollbar space-y-4 text-xs flex-1">
            
            <!-- Citizen Info Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Citizen Full Name *</label>
                    <input type="text" id="walkInCitizenName" required placeholder="e.g. Juan De La Cruz" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200">
                </div>
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Mobile Contact Number *</label>
                    <input type="text" id="walkInCitizenPhone" required placeholder="+63 9XX XXX XXXX" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200">
                </div>
            </div>

            <!-- Barangay & Landmark -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Barangay *</label>
                    <select id="walkInBarangay" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200 cursor-pointer">
                        <option value="Brgy 178 Camarin">Brgy 178 Camarin</option>
                        <option value="Brgy 171 Bagumbong">Brgy 171 Bagumbong</option>
                        <option value="Brgy 80 Grace Park">Brgy 80 Grace Park</option>
                        <option value="Brgy 12">Brgy 12 (10th Avenue)</option>
                        <option value="Brgy 176 Bagong Silang">Brgy 176 Bagong Silang</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Specific Landmark / Street *</label>
                    <input type="text" id="walkInLandmark" required placeholder="e.g. Near Susano Market Overpass" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200">
                </div>
            </div>

            <!-- Concern Title -->
            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Concern Subject Title *</label>
                <input type="text" id="walkInTitle" required placeholder="e.g. Broken road surface causing accidents" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200">
            </div>

            <!-- Description with Live AI Analyzer -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="font-bold text-slate-700 dark:text-slate-300 block">Detailed Description *</label>
                    <span id="walkInAiDetectorHint" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                        <i class="fa-solid fa-sparkles"></i> AI Triage Active
                    </span>
                </div>
                <textarea id="walkInDescription" oninput="triggerWalkInAiAssistant()" required rows="3" placeholder="Enter citizen complaint details. AI will automatically suggest the matching department & priority based on keywords..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200"></textarea>
            </div>

            <!-- AI Suggested Category & Department Banner -->
            <div id="walkInAiSuggestionCard" class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-indigo-600"></i>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-indigo-700 dark:text-indigo-300 block">AI Recommended Bureau:</span>
                        <span id="walkInAiBureauText" class="font-extrabold text-xs text-indigo-900 dark:text-indigo-100">City Engineering & Public Works Office (DPWH)</span>
                    </div>
                </div>
                <span id="walkInAiConfidenceScore" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-600 text-white">98% Match</span>
            </div>

            <!-- Category & Priority Controls -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Assigned Department</label>
                    <select id="walkInDepartmentSelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200 cursor-pointer">
                        <option value="dpwh">City Engineering & Public Works (DPWH)</option>
                        <option value="cenro">CENRO Waste Management</option>
                        <option value="flood">Caloocan Flood Control Bureau</option>
                        <option value="electrical">Public Safety Electrical Division</option>
                        <option value="cptmd">CPTMD Public Safety & Police</option>
                        <option value="cenro_env">City Environment & Natural Resources</option>
                        <option value="assistance">Public Assistance & Grievance</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Priority Level</label>
                    <select id="walkInPrioritySelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs outline-none focus:border-blue-500 font-medium text-slate-800 dark:text-slate-200 cursor-pointer">
                        <option value="High">High (24h SLA)</option>
                        <option value="Urgent">Urgent (4h SLA)</option>
                        <option value="Medium">Medium (48h SLA)</option>
                        <option value="Low">Low (72h SLA)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeAddWalkInModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Submit & Auto-Dispatch Ticket</span>
                </button>
            </div>

        </form>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. QUICK RE-ROUTE MODAL                                                   -->
<!-- ========================================================================= -->
<div id="quickReRouteModal" class="hidden fixed inset-0 z-[120] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-arrow-right-arrow-left text-blue-600"></i>
                <span>Manual Re-Route Override</span>
            </h3>
            <button onclick="closeQuickReRouteModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-950/40 p-2.5 rounded-xl border border-blue-100 dark:border-blue-900/40">
                <span class="text-slate-500 font-semibold">Target Concern:</span>
                <span id="quickReRouteTicketId" class="font-mono font-bold text-blue-600 dark:text-blue-400">CAL-REP-2026-4821</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">New Target Department</label>
                <select id="quickReRouteDeptSelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="dpwh">City Engineering & Public Works (DPWH)</option>
                    <option value="cenro">CENRO Waste Management</option>
                    <option value="flood">Caloocan Flood Control Bureau</option>
                    <option value="electrical">Public Safety Electrical Division</option>
                    <option value="cptmd">CPTMD Public Safety & Police</option>
                    <option value="cenro_env">City Environment & Natural Resources</option>
                    <option value="assistance">Public Assistance & Grievance</option>
                </select>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Audit Reason for Manual Re-Route *</label>
                <textarea id="quickReRouteReasonInput" rows="2" placeholder="Explain why AI routing was overridden (e.g. Utility pipe jurisdiction belongs to Maynilad/CEPO)..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs focus:border-blue-500"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button onclick="closeQuickReRouteModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmQuickReRoute()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-check text-xs"></i>
                <span>Confirm & Dispatch</span>
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. QUICK ASSIGN OFFICER MODAL                                             -->
<!-- ========================================================================= -->
<div id="quickAssignModal" class="hidden fixed inset-0 z-[120] bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-user-check text-blue-600"></i>
                <span>Assign Action Officer / Field Unit</span>
            </h3>
            <button onclick="closeQuickAssignModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 flex items-center justify-center cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-950/40 p-2.5 rounded-xl border border-blue-100 dark:border-blue-900/40">
                <span class="text-slate-500 font-semibold">Ref Ticket:</span>
                <span id="quickAssignTicketId" class="font-mono font-bold text-blue-600 dark:text-blue-400">CAL-REP-2026-4821</span>
            </div>

            <div>
                <label class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Select Action Officer / Unit</label>
                <select id="quickAssignOfficerSelect" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl p-2.5 outline-none font-medium text-xs cursor-pointer focus:border-blue-500">
                    <option value="Engr. Arnel Castillo (Road Maintenance Unit 4)">Engr. Arnel Castillo (Road Maintenance Unit 4)</option>
                    <option value="Chief Inspector Danilo Cruz (Electrical Emergency Team)">Chief Inspector Danilo Cruz (Electrical Emergency Team)</option>
                    <option value="Engr. Danilo Santos (Drainage Dredging Team 2)">Engr. Danilo Santos (Drainage Dredging Team 2)</option>
                    <option value="Officer Jerome Valdez (CPTMD Sector 3 Patrol)">Officer Jerome Valdez (CPTMD Sector 3 Patrol)</option>
                    <option value="CENRO Waste Operations Team Alpha">CENRO Waste Operations Team Alpha</option>
                    <option value="Helpdesk Officer Carla Dizon (Public Assistance)">Helpdesk Officer Carla Dizon (Public Assistance)</option>
                </select>
            </div>

            <div class="p-3 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-xl text-[11px] text-slate-600 dark:text-slate-300 font-medium">
                <span class="font-bold text-blue-700 dark:text-blue-400 block mb-0.5">Automated Mobile Dispatch Alert</span>
                <span>Assigning an action officer will trigger an instantaneous SMS notification and dispatch work order to their connected mobile unit.</span>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button onclick="closeQuickAssignModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
            <button onclick="confirmQuickAssign()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-xs cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Assign & Dispatch SMS</span>
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. IMAGE LIGHTBOX MODAL                                                   -->
<!-- ========================================================================= -->
<div id="imageLightboxModal" class="hidden fixed inset-0 z-[130] bg-black/90 backdrop-blur-md flex items-center justify-center p-4" onclick="closeImageLightbox()">
    <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" onclick="event.stopPropagation()">
        <img id="lightboxImage" src="" alt="Evidence Full" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl border border-white/10">
        <button onclick="closeImageLightbox()" class="absolute -top-10 right-0 text-white/80 hover:text-white text-lg font-bold cursor-pointer">
            <i class="fa-solid fa-xmark"></i> Close Preview
        </button>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 7. TOAST NOTIFICATION POPUP CONTAINER                                     -->
<!-- ========================================================================= -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-[140] space-y-2 pointer-events-none"></div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT STATE ENGINE & INTERACTIVE CONTROLLERS                        -->
<!-- ========================================================================= -->
<script>
// Master Reactive State (Loaded from PHP Data + In-Memory Store)
let concernsDataset = <?php echo json_encode($initialConcerns, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let routingRulesDataset = <?php echo json_encode($routingRules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let departmentsMap = <?php echo json_encode($departments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

// Active Modal State Pointers
let activeConcernId = null;
let currentViewMode = 'table'; // 'table' or 'board'

// Lifecycle Stages Definition
const STAGES_ORDER = [
    { key: 'submitted', label: 'Submitted', num: 1, color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' },
    { key: 'ai_analyzed', label: 'AI Analyzed', num: 2, color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' },
    { key: 'routed', label: 'Auto-Routed', num: 3, color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' },
    { key: 'under_review', label: 'Under Review', num: 4, color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' },
    { key: 'in_progress', label: 'In Progress', num: 5, color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300' },
    { key: 'resolved', label: 'Resolved', num: 6, color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' },
    { key: 'closed', label: 'Closed', num: 7, color: 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200' }
];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    renderAllViews();
    updateKPIs();
    renderRoutingRulesTable();
});

// Render Main Views
function renderAllViews() {
    renderQueueTable();
    renderLifecycleBoard();
    updateKPIs();
}

// -------------------------------------------------------------
// FILTERING ENGINE
// -------------------------------------------------------------
function getFilteredConcerns() {
    const searchVal = (document.getElementById('universalSearchInput')?.value || '').toLowerCase().trim();
    const deptVal = document.getElementById('filterDepartment')?.value || 'all';
    const priorityVal = document.getElementById('filterPriority')?.value || 'all';
    const stageVal = document.getElementById('filterStage')?.value || 'all';
    const brgyVal = document.getElementById('filterBarangay')?.value || 'all';

    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) {
        if (searchVal.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }

    return concernsDataset.filter(item => {
        // Universal search filter
        if (searchVal) {
            const matchId = item.id.toLowerCase().includes(searchVal);
            const matchTitle = item.title.toLowerCase().includes(searchVal);
            const matchDesc = item.description.toLowerCase().includes(searchVal);
            const matchCitizen = item.citizen_name.toLowerCase().includes(searchVal);
            const matchLandmark = item.landmark.toLowerCase().includes(searchVal);
            const matchBrgy = item.barangay.toLowerCase().includes(searchVal);
            const matchDept = item.department_name.toLowerCase().includes(searchVal);
            const matchKeywords = (item.ai_keywords || []).some(k => k.toLowerCase().includes(searchVal));

            if (!matchId && !matchTitle && !matchDesc && !matchCitizen && !matchLandmark && !matchBrgy && !matchDept && !matchKeywords) {
                return false;
            }
        }

        // Department filter
        if (deptVal !== 'all' && item.department_key !== deptVal) return false;

        // Priority filter
        if (priorityVal !== 'all' && item.priority !== priorityVal) return false;

        // Stage filter
        if (stageVal !== 'all' && item.stage !== stageVal) return false;

        // Barangay filter
        if (brgyVal !== 'all' && item.barangay !== brgyVal) return false;

        return true;
    });
}

function applyFilters() {
    renderAllViews();
}

function clearUniversalSearch() {
    const input = document.getElementById('universalSearchInput');
    if (input) input.value = '';
    applyFilters();
}

function resetAllFilters() {
    document.getElementById('universalSearchInput').value = '';
    document.getElementById('filterDepartment').value = 'all';
    document.getElementById('filterPriority').value = 'all';
    document.getElementById('filterStage').value = 'all';
    document.getElementById('filterBarangay').value = 'all';
    applyFilters();
}

// -------------------------------------------------------------
// TABLE VIEW RENDERER
// -------------------------------------------------------------
function renderQueueTable() {
    const tbody = document.getElementById('concernsTableBody');
    const emptyState = document.getElementById('tableEmptyState');
    const visibleCounter = document.getElementById('visibleRowsCount');
    if (!tbody) return;

    const filtered = getFilteredConcerns();
    if (visibleCounter) visibleCounter.innerText = filtered.length;

    if (filtered.length === 0) {
        tbody.innerHTML = '';
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    let html = '';
    filtered.forEach(item => {
        const priorityBadge = getPriorityBadgeHtml(item.priority);
        const stageBadge = getStageBadgeHtml(item.stage);
        const slaBadge = getSlaBadgeHtml(item.sla_text, item.sla_status);
        const deptInfo = departmentsMap[item.department_key] || { badge: 'bg-slate-100 text-slate-700', short: item.department_name, icon: 'fa-solid fa-building' };

        html += `
        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition group cursor-pointer" onclick="openConcernDetailModal('${item.id}')">
            
            <!-- Ref ID & Date -->
            <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="flex flex-col">
                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline group-hover:text-blue-700 flex items-center gap-1">
                        <span>${item.id}</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-0 group-hover:opacity-100 transition"></i>
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5">${item.date_filed}</span>
                </div>
            </td>

            <!-- Citizen & Barangay -->
            <td class="py-3.5 px-4">
                <div class="flex flex-col max-w-[170px]">
                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate ${item.is_anonymous ? 'italic text-slate-500' : ''}">
                        ${item.is_anonymous ? '<i class="fa-solid fa-user-secret mr-1 text-slate-400"></i> Anonymous' : escapeHtml(item.citizen_name)}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium truncate flex items-center gap-1 mt-0.5">
                        <i class="fa-solid fa-location-dot text-rose-500 text-[9px]"></i>
                        ${escapeHtml(item.barangay)}
                    </span>
                </div>
            </td>

            <!-- Concern & AI Match -->
            <td class="py-3.5 px-4">
                <div class="space-y-1 max-w-[340px]">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            ${escapeHtml(item.category)}
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                            <i class="fa-solid fa-brain text-[8px]"></i>
                            <span>${item.ai_confidence}% Match</span>
                        </span>
                        ${item.has_duplicate ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800"><i class="fa-solid fa-clone text-[8px]"></i> Duplicate Alert</span>` : ''}
                    </div>
                    <p class="font-bold text-slate-900 dark:text-white leading-snug line-clamp-1 text-xs">
                        ${escapeHtml(item.title)}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 font-medium">
                        ${escapeHtml(item.landmark)}
                    </p>
                </div>
            </td>

            <!-- Target Department -->
            <td class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border ${deptInfo.badge} flex items-center gap-1.5">
                        <i class="${deptInfo.icon} text-[10px]"></i>
                        <span>${deptInfo.short}</span>
                    </span>
                </div>
            </td>

            <!-- Priority -->
            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                ${priorityBadge}
            </td>

            <!-- SLA Countdown -->
            <td class="py-3.5 px-4 whitespace-nowrap">
                ${slaBadge}
            </td>

            <!-- Stage -->
            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                ${stageBadge}
            </td>

            <!-- Actions -->
            <td class="py-3.5 px-4 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                <div class="flex items-center justify-end gap-1.5">
                    <button onclick="openConcernDetailModal('${item.id}')" class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:hover:bg-blue-900/60 dark:text-blue-400 rounded-lg text-xs font-bold transition cursor-pointer" title="View / Manage Control Hub">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                    <button onclick="openQuickReRouteModal('${item.id}')" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition cursor-pointer" title="Quick Re-Route Bureau">
                        <i class="fa-solid fa-arrow-right-arrow-left text-xs"></i>
                    </button>
                    <button onclick="openQuickAssignModal('${item.id}')" class="p-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/60 dark:text-indigo-400 rounded-lg text-xs font-bold transition cursor-pointer" title="Quick Assign Action Officer">
                        <i class="fa-solid fa-user-pen text-xs"></i>
                    </button>
                </div>
            </td>

        </tr>
        `;
    });

    tbody.innerHTML = html;
}

// -------------------------------------------------------------
// KANBAN LIFECYCLE BOARD RENDERER
// -------------------------------------------------------------
function renderLifecycleBoard() {
    const filtered = getFilteredConcerns();

    STAGES_ORDER.forEach(stage => {
        const colContainer = document.getElementById(`boardColumn-${stage.key}`);
        const countBadge = document.getElementById(`boardCount-${stage.key}`);
        if (!colContainer) return;

        const stageItems = filtered.filter(item => item.stage === stage.key);
        if (countBadge) countBadge.innerText = stageItems.length;

        if (stageItems.length === 0) {
            colContainer.innerHTML = `
                <div class="py-10 text-center text-slate-400 dark:text-slate-600">
                    <i class="fa-solid fa-inbox text-lg opacity-40 mb-1"></i>
                    <p class="text-[10px] font-bold">No tickets in this stage</p>
                </div>
            `;
            return;
        }

        let html = '';
        stageItems.forEach(item => {
            const priorityBadge = getPriorityBadgeHtml(item.priority);
            const deptInfo = departmentsMap[item.department_key] || { short: item.department_name, badge: 'bg-slate-100 text-slate-700' };

            html += `
            <div class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs p-3 space-y-2.5 hover:border-blue-300 dark:hover:border-blue-600 transition cursor-pointer" onclick="openConcernDetailModal('${item.id}')">
                
                <div class="flex items-center justify-between text-[10px]">
                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400">${item.id}</span>
                    ${priorityBadge}
                </div>

                <div>
                    <h5 class="text-xs font-black text-slate-900 dark:text-white leading-snug line-clamp-2">${escapeHtml(item.title)}</h5>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-1 flex items-center gap-1 truncate">
                        <i class="fa-solid fa-location-dot text-rose-500 text-[9px]"></i>
                        ${escapeHtml(item.barangay)}
                    </p>
                </div>

                <div class="p-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-[10px] flex items-center justify-between">
                    <span class="text-slate-400 truncate max-w-[120px] font-medium">${deptInfo.short}</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">${item.sla_text}</span>
                </div>

                <div class="flex items-center justify-between gap-1 pt-1 border-t border-slate-100 dark:border-slate-700" onclick="event.stopPropagation()">
                    <button onclick="openConcernDetailModal('${item.id}')" class="flex-1 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-[10px] rounded-lg transition text-center">
                        Details
                    </button>
                    <button onclick="advanceStageDirectly('${item.id}')" class="flex-1 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] rounded-lg transition shadow-2xs text-center flex items-center justify-center gap-1">
                        <span>Advance</span> <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    </button>
                </div>

            </div>
            `;
        });

        colContainer.innerHTML = html;
    });
}

// -------------------------------------------------------------
// VIEW MODE TOGGLER
// -------------------------------------------------------------
function setViewMode(mode) {
    currentViewMode = mode;
    const tableView = document.getElementById('queueTableViewContainer');
    const boardView = document.getElementById('lifecycleBoardViewContainer');
    const tableBtn = document.getElementById('tableViewBtn');
    const boardBtn = document.getElementById('boardViewBtn');

    if (mode === 'table') {
        tableView?.classList.remove('hidden');
        boardView?.classList.add('hidden');

        tableBtn?.classList.add('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-xs');
        tableBtn?.classList.remove('text-slate-500');

        boardBtn?.classList.remove('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-xs');
        boardBtn?.classList.add('text-slate-500');
    } else {
        tableView?.classList.add('hidden');
        boardView?.classList.remove('hidden');

        boardBtn?.classList.add('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-xs');
        boardBtn?.classList.remove('text-slate-500');

        tableBtn?.classList.remove('bg-white', 'dark:bg-slate-700', 'text-blue-600', 'dark:text-blue-400', 'shadow-xs');
        tableBtn?.classList.add('text-slate-500');
    }
}

// -------------------------------------------------------------
// DETAIL & DISPATCH MODAL CONTROLLERS (THE CORE CONTROL HUB)
// -------------------------------------------------------------
function openConcernDetailModal(id) {
    const item = concernsDataset.find(c => c.id === id);
    if (!item) return;

    activeConcernId = id;

    // Header values
    document.getElementById('modalRefId').innerText = item.id;
    document.getElementById('modalDateFiled').innerText = `Filed on ${item.date_filed}`;

    const priorityBadge = document.getElementById('modalPriorityBadge');
    priorityBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ' + getPriorityBadgeClasses(item.priority);
    priorityBadge.innerText = `${item.priority} Priority`;

    const stageBadge = document.getElementById('modalStageBadge');
    stageBadge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ' + getStageBadgeClasses(item.stage);
    stageBadge.innerText = getStageLabel(item.stage);

    // 7-Stage Horizontal Step Bar
    renderModalStepBar(item.stage);

    // Duplicate alert
    const dupAlert = document.getElementById('modalDuplicateAlert');
    const dupText = document.getElementById('modalDuplicateText');
    if (item.has_duplicate) {
        dupAlert?.classList.remove('hidden');
        if (dupText) dupText.innerText = item.duplicate_text || 'Duplicate reports detected nearby.';
    } else {
        dupAlert?.classList.add('hidden');
    }

    // Concern Details
    document.getElementById('modalCategoryBadge').innerText = item.category;
    document.getElementById('modalTitle').innerText = item.title;
    document.getElementById('modalDescription').innerText = item.description;
    document.getElementById('modalLocationText').innerText = `${item.landmark}, ${item.barangay}`;

    // Photo Gallery
    const photosCount = document.getElementById('modalPhotosCount');
    const photosGallery = document.getElementById('modalPhotoGallery');
    const photos = item.photos || [];
    if (photosCount) photosCount.innerText = photos.length;
    
    if (photosGallery) {
        if (photos.length === 0) {
            photosGallery.innerHTML = '<span class="text-slate-400 text-[11px] italic">No evidence photos attached by citizen.</span>';
        } else {
            photosGallery.innerHTML = photos.map(imgUrl => `
                <img src="${imgUrl}" alt="Evidence" onclick="openImageLightbox('${imgUrl}')" class="w-16 h-16 object-cover rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-400 transition cursor-pointer shadow-xs hover:scale-105">
            `).join('');
        }
    }

    // Citizen Profile
    document.getElementById('modalCitizenName').innerText = item.is_anonymous ? 'Anonymous Citizen' : item.citizen_name;
    document.getElementById('modalCitizenPhone').innerText = item.citizen_phone || 'N/A';
    document.getElementById('modalCitizenEmail').innerText = item.citizen_email || 'N/A';
    document.getElementById('modalGpsPin').innerText = item.gps || '14.7565° N, 121.0437° E';
    document.getElementById('modalAnonBadge').innerText = item.is_anonymous ? 'Anonymous Report' : 'Verified Citizen';

    // Activity Log
    renderModalActivityLog(item.activity_log || []);

    // AI Multi-Modal Triage Breakdown
    document.getElementById('modalAiConfidenceBadge').innerText = `${item.ai_confidence}% Match`;
    document.getElementById('modalConfidenceScore').innerText = `${item.ai_confidence}%`;
    document.getElementById('modalConfidenceBar').style.width = `${item.ai_confidence}%`;
    document.getElementById('modalAiReasonText').innerText = item.ai_reason || 'Keyword and sentiment parameters analyzed by Gemini multi-modal engine.';

    const keywordsContainer = document.getElementById('modalAiKeywordsList');
    if (keywordsContainer) {
        const keywords = item.ai_keywords || ['concern', 'community'];
        keywordsContainer.innerHTML = keywords.map(kw => `
            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 rounded-md text-[10px] font-bold">
                #${escapeHtml(kw)}
            </span>
        `).join('');
    }

    // Department & SLA Controls
    document.getElementById('modalDepartmentSelect').value = item.department_key;
    document.getElementById('modalAssignedOfficerInput').value = item.assigned_officer || '';
    document.getElementById('modalPrioritySelect').value = item.priority;

    const slaStatusPill = document.getElementById('modalSlaStatusPill');
    if (slaStatusPill) {
        slaStatusPill.className = 'px-2 py-0.5 rounded-md text-[10px] font-bold ' + getSlaBadgeClasses(item.sla_status);
        slaStatusPill.innerText = item.sla_text;
    }

    // Resolution & Workflow Advance Actions
    renderModalStageAdvanceControls(item);

    // Open Modal
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    activeConcernId = null;
}

function renderModalStepBar(currentStage) {
    const bar = document.getElementById('modalStageTrackerBar');
    const summary = document.getElementById('modalTimelineStageSummary');
    if (!bar) return;

    const currentStageIndex = STAGES_ORDER.findIndex(s => s.key === currentStage);
    if (summary) summary.innerText = `Stage ${currentStageIndex + 1} of 7: ${STAGES_ORDER[currentStageIndex].label}`;

    let html = '';
    STAGES_ORDER.forEach((stage, idx) => {
        const isCompleted = idx < currentStageIndex;
        const isCurrent = idx === currentStageIndex;

        let circleStyle = 'bg-slate-200 dark:bg-slate-700 text-slate-500';
        let barStyle = 'text-slate-400 font-medium';

        if (isCompleted) {
            circleStyle = 'bg-emerald-600 text-white shadow-xs';
            barStyle = 'text-emerald-700 dark:text-emerald-400 font-bold';
        } else if (isCurrent) {
            circleStyle = 'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-900/50 shadow-md';
            barStyle = 'text-blue-700 dark:text-blue-300 font-black';
        }

        html += `
        <div class="flex flex-col items-center text-center space-y-1">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold ${circleStyle}">
                ${isCompleted ? '<i class="fa-solid fa-check text-[9px]"></i>' : stage.num}
            </div>
            <span class="text-[9px] leading-tight ${barStyle} hidden sm:block">${stage.label}</span>
        </div>
        `;
    });

    bar.innerHTML = html;
}

function renderModalActivityLog(logs) {
    const list = document.getElementById('modalActivityLogList');
    if (!list) return;

    if (logs.length === 0) {
        list.innerHTML = '<span class="text-slate-400 text-[11px] italic">No logs recorded yet.</span>';
        return;
    }

    list.innerHTML = logs.map(log => `
        <div class="flex items-start gap-2.5 text-[11px] bg-slate-50/70 dark:bg-slate-800/40 p-2 rounded-xl border border-slate-100 dark:border-slate-800">
            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <span class="font-extrabold text-slate-800 dark:text-slate-200">${escapeHtml(log.actor)}</span>
                    <span class="text-[10px] text-slate-400 font-mono">${escapeHtml(log.time)}</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 mt-0.5">${escapeHtml(log.action)}</p>
            </div>
        </div>
    `).join('');
}

function renderModalStageAdvanceControls(item) {
    const actionContainer = document.getElementById('modalStageActionContainer');
    const resolutionSection = document.getElementById('modalResolutionSection');
    const primaryBtn = document.getElementById('modalPrimaryActionButton');

    if (!actionContainer) return;

    // Show resolution section if in progress, resolved, or closed
    if (['in_progress', 'resolved', 'closed'].includes(item.stage)) {
        resolutionSection?.classList.remove('hidden');
        document.getElementById('modalResolutionNotes').value = item.resolution_notes || '';

        const resPhotosContainer = document.getElementById('modalResolutionPhotosGallery');
        if (resPhotosContainer) {
            const resPhotos = item.resolution_photos || [];
            if (resPhotos.length === 0) {
                resPhotosContainer.innerHTML = '<span class="text-slate-400 text-[10px] italic">No resolution proof photos attached.</span>';
            } else {
                resPhotosContainer.innerHTML = resPhotos.map(url => `
                    <img src="${url}" alt="Resolution Proof" onclick="openImageLightbox('${url}')" class="w-14 h-14 object-cover rounded-lg border border-emerald-300 dark:border-emerald-700 cursor-pointer shadow-xs">
                `).join('');
            }
        }
    } else {
        resolutionSection?.classList.add('hidden');
    }

    let actionButtonsHtml = '';
    let primaryBtnText = 'Advance Stage';

    switch (item.stage) {
        case 'submitted':
            actionButtonsHtml = `
                <button onclick="advanceStage('ai_analyzed')" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                    <span>Trigger AI Triage & Verification</span>
                </button>
            `;
            primaryBtnText = 'Trigger AI Triage';
            break;
        case 'ai_analyzed':
            actionButtonsHtml = `
                <button onclick="advanceStage('routed')" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-route text-xs"></i>
                    <span>Confirm Auto-Route to Department</span>
                </button>
            `;
            primaryBtnText = 'Confirm Route';
            break;
        case 'routed':
            actionButtonsHtml = `
                <button onclick="advanceStage('under_review')" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-user-check text-xs"></i>
                    <span>Assign Officer & Mark Under Review</span>
                </button>
            `;
            primaryBtnText = 'Mark Under Review';
            break;
        case 'under_review':
            actionButtonsHtml = `
                <button onclick="advanceStage('in_progress')" class="w-full py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-person-digging text-xs"></i>
                    <span>Deploy Field Unit (Mark In Progress)</span>
                </button>
            `;
            primaryBtnText = 'Deploy Field Unit';
            break;
        case 'in_progress':
            actionButtonsHtml = `
                <button onclick="advanceStage('resolved')" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-check-double text-xs"></i>
                    <span>Submit Work & Mark RESOLVED</span>
                </button>
            `;
            primaryBtnText = 'Mark Resolved';
            break;
        case 'resolved':
            actionButtonsHtml = `
                <button onclick="advanceStage('closed')" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-box-archive text-xs"></i>
                    <span>Citizen Acknowledged & Close Ticket</span>
                </button>
            `;
            primaryBtnText = 'Close Ticket';
            break;
        case 'closed':
            actionButtonsHtml = `
                <div class="p-2.5 bg-emerald-100/60 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-200 font-bold text-center text-xs flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Ticket is Fully Resolved & Archived</span>
                </div>
            `;
            primaryBtnText = 'Ticket Closed';
            break;
    }

    actionContainer.innerHTML = actionButtonsHtml;
    if (primaryBtn) {
        primaryBtn.querySelector('span').innerText = primaryBtnText;
        if (item.stage === 'closed') {
            primaryBtn.disabled = true;
            primaryBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            primaryBtn.disabled = false;
            primaryBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

// Save Department & Officer updates
function saveAssignmentChanges() {
    if (!activeConcernId) return;
    const item = concernsDataset.find(c => c.id === activeConcernId);
    if (!item) return;

    const newDept = document.getElementById('modalDepartmentSelect').value;
    const newOfficer = document.getElementById('modalAssignedOfficerInput').value.trim();
    const newPriority = document.getElementById('modalPrioritySelect').value;

    const isDeptChanged = item.department_key !== newDept;
    item.department_key = newDept;
    item.department_name = departmentsMap[newDept]?.name || newDept;
    item.assigned_officer = newOfficer || 'Unassigned';
    item.priority = newPriority;

    const now = getCurrentFormattedTime();
    item.activity_log.unshift({
        time: now,
        actor: 'Admin Officer',
        action: `Updated dispatch parameters: Department set to "${item.department_name}", Assigned Officer: "${item.assigned_officer}", Priority: ${newPriority}.`
    });

    renderAllViews();
    openConcernDetailModal(activeConcernId);
    showToast(`Dispatch parameters updated for ticket ${item.id}`, 'success');
}

// Advance Lifecycle Stage
function advanceStage(targetStage) {
    if (!activeConcernId) return;
    const item = concernsDataset.find(c => c.id === activeConcernId);
    if (!item) return;

    const prevStageLabel = getStageLabel(item.stage);
    const newStageLabel = getStageLabel(targetStage);

    item.stage = targetStage;
    const now = getCurrentFormattedTime();

    if (targetStage === 'resolved') {
        const notes = document.getElementById('modalResolutionNotes')?.value.trim();
        item.resolution_notes = notes || 'On-site resolution verified by action team. Work orders completed according to city engineering standards.';
        item.sla_status = 'resolved';
        item.sla_text = '✓ Resolved (SLA Met)';
    } else if (targetStage === 'closed') {
        item.sla_status = 'closed';
        item.sla_text = '✓ Closed (CSAT 5/5 Stars)';
    }

    item.activity_log.unshift({
        time: now,
        actor: 'Admin Dispatcher',
        action: `Advanced lifecycle stage from "${prevStageLabel}" to "${newStageLabel}".`
    });

    renderAllViews();
    openConcernDetailModal(activeConcernId);
    showToast(`Ticket ${item.id} advanced to "${newStageLabel}"!`, 'success');
}

function advanceActiveTicketStage() {
    if (!activeConcernId) return;
    const item = concernsDataset.find(c => c.id === activeConcernId);
    if (!item) return;

    const currentIndex = STAGES_ORDER.findIndex(s => s.key === item.stage);
    if (currentIndex < STAGES_ORDER.length - 1) {
        advanceStage(STAGES_ORDER[currentIndex + 1].key);
    }
}

function advanceStageDirectly(id) {
    const item = concernsDataset.find(c => c.id === id);
    if (!item) return;

    const currentIndex = STAGES_ORDER.findIndex(s => s.key === item.stage);
    if (currentIndex < STAGES_ORDER.length - 1) {
        activeConcernId = id;
        advanceStage(STAGES_ORDER[currentIndex + 1].key);
    }
}

function mergeDuplicateReports() {
    if (!activeConcernId) return;
    const item = concernsDataset.find(c => c.id === activeConcernId);
    if (!item) return;

    item.has_duplicate = false;
    const now = getCurrentFormattedTime();
    item.activity_log.unshift({
        time: now,
        actor: 'Admin Officer',
        action: 'Merged 2 duplicate neighborhood reports into this primary parent ticket.'
    });

    renderAllViews();
    openConcernDetailModal(activeConcernId);
    showToast(`Duplicate cluster merged into primary ticket ${item.id}!`, 'info');
}

function simulateResolutionPhotoUpload() {
    if (!activeConcernId) return;
    const item = concernsDataset.find(c => c.id === activeConcernId);
    if (!item) return;

    item.resolution_photos = item.resolution_photos || [];
    item.resolution_photos.push('https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80');

    openConcernDetailModal(activeConcernId);
    showToast('Resolution proof photo attached to record!', 'success');
}

// -------------------------------------------------------------
// QUICK RE-ROUTE MODAL CONTROLLER
// -------------------------------------------------------------
let activeQuickReRouteId = null;

function openQuickReRouteModal(id) {
    activeQuickReRouteId = id;
    const item = concernsDataset.find(c => c.id === id);
    if (!item) return;

    document.getElementById('quickReRouteTicketId').innerText = id;
    document.getElementById('quickReRouteDeptSelect').value = item.department_key;
    document.getElementById('quickReRouteReasonInput').value = '';
    document.getElementById('quickReRouteModal').classList.remove('hidden');
}

function closeQuickReRouteModal() {
    document.getElementById('quickReRouteModal').classList.add('hidden');
    activeQuickReRouteId = null;
}

function confirmQuickReRoute() {
    if (!activeQuickReRouteId) return;
    const item = concernsDataset.find(c => c.id === activeQuickReRouteId);
    if (!item) return;

    const newDept = document.getElementById('quickReRouteDeptSelect').value;
    const reason = document.getElementById('quickReRouteReasonInput').value.trim() || 'Manual administrative jurisdiction reassignment.';

    item.department_key = newDept;
    item.department_name = departmentsMap[newDept]?.name || newDept;

    const now = getCurrentFormattedTime();
    item.activity_log.unshift({
        time: now,
        actor: 'Admin Officer',
        action: `Manual re-route override dispatched to "${item.department_name}". Reason: ${reason}`
    });

    closeQuickReRouteModal();
    renderAllViews();
    showToast(`Ticket ${item.id} re-routed to ${departmentsMap[newDept]?.short}!`, 'success');
}

// -------------------------------------------------------------
// QUICK ASSIGN OFFICER MODAL CONTROLLER
// -------------------------------------------------------------
let activeQuickAssignId = null;

function openQuickAssignModal(id) {
    activeQuickAssignId = id;
    const item = concernsDataset.find(c => c.id === id);
    if (!item) return;

    document.getElementById('quickAssignTicketId').innerText = id;
    document.getElementById('quickAssignModal').classList.remove('hidden');
}

function closeQuickAssignModal() {
    document.getElementById('quickAssignModal').classList.add('hidden');
    activeQuickAssignId = null;
}

function confirmQuickAssign() {
    if (!activeQuickAssignId) return;
    const item = concernsDataset.find(c => c.id === activeQuickAssignId);
    if (!item) return;

    const officer = document.getElementById('quickAssignOfficerSelect').value;
    item.assigned_officer = officer;

    if (item.stage === 'routed' || item.stage === 'submitted') {
        item.stage = 'under_review';
    }

    const now = getCurrentFormattedTime();
    item.activity_log.unshift({
        time: now,
        actor: 'Admin Officer',
        action: `Assigned ticket to action officer: "${officer}". Mobile dispatch alert triggered.`
    });

    closeQuickAssignModal();
    renderAllViews();
    showToast(`Assigned to ${officer} & SMS alert dispatched!`, 'success');
}

// -------------------------------------------------------------
// ROUTING RULES CONFIGURATOR MODAL CONTROLLER
// -------------------------------------------------------------
function openRulesConfigModal() {
    renderRoutingRulesTable();
    document.getElementById('rulesConfigModal').classList.remove('hidden');
}

function closeRulesConfigModal() {
    document.getElementById('rulesConfigModal').classList.add('hidden');
}

function renderRoutingRulesTable() {
    const tbody = document.getElementById('rulesTableBody');
    if (!tbody) return;

    let html = '';
    routingRulesDataset.forEach((rule, idx) => {
        html += `
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
            <td class="py-3 px-3.5 font-bold text-slate-800 dark:text-slate-200">
                ${escapeHtml(rule.category)}
            </td>
            <td class="py-3 px-3.5">
                <div class="flex flex-wrap gap-1 max-w-[220px]">
                    ${rule.keywords.map(kw => `<span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded text-[9px] font-mono">#${kw}</span>`).join('')}
                </div>
            </td>
            <td class="py-3 px-3.5 font-semibold text-slate-700 dark:text-slate-300">
                ${escapeHtml(rule.department_name)}
            </td>
            <td class="py-3 px-3.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold ${getPriorityBadgeClasses(rule.default_priority)}">
                    ${rule.default_priority}
                </span>
            </td>
            <td class="py-3 px-3.5 font-mono text-[11px] text-slate-600 dark:text-slate-400 font-bold">
                ${rule.default_sla}
            </td>
            <td class="py-3 px-3.5 text-center">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" onchange="toggleRuleActive(${idx})" ${rule.is_active ? 'checked' : ''} class="sr-only peer">
                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                </label>
            </td>
        </tr>
        `;
    });

    tbody.innerHTML = html;
}

function toggleRuleActive(index) {
    if (routingRulesDataset[index]) {
        routingRulesDataset[index].is_active = !routingRulesDataset[index].is_active;
        showToast(`Rule policy for ${routingRulesDataset[index].category} updated.`, 'info');
    }
}

function testAiRoutingRule() {
    const input = document.getElementById('aiTestInput')?.value.trim().toLowerCase();
    const resultBox = document.getElementById('aiTestResult');
    if (!input || !resultBox) return;

    let matchedRule = null;
    let matchedScore = 85;

    for (const rule of routingRulesDataset) {
        if (!rule.is_active) continue;
        const matchCount = rule.keywords.filter(kw => input.includes(kw.toLowerCase())).length;
        if (matchCount > 0) {
            matchedRule = rule;
            matchedScore = Math.min(99, 88 + (matchCount * 4));
            break;
        }
    }

    if (!matchedRule) {
        matchedRule = routingRulesDataset[routingRulesDataset.length - 1]; // Default to General Assistance
        matchedScore = 78;
    }

    resultBox.classList.remove('hidden');
    resultBox.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="text-emerald-600 font-extrabold"><i class="fa-solid fa-circle-check"></i> Matched:</span>
            <span>${matchedRule.department_name} (${matchedRule.category})</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-blue-600 text-white font-bold text-[10px]">${matchedScore}% AI Confidence</span>
    `;
}

function saveRulesConfig() {
    closeRulesConfigModal();
    showToast('Routing rule policies successfully synchronized!', 'success');
}

// -------------------------------------------------------------
// ADD WALK-IN MODAL CONTROLLER
// -------------------------------------------------------------
function openAddWalkInModal() {
    document.getElementById('addWalkInForm').reset();
    document.getElementById('addWalkInModal').classList.remove('hidden');
}

function closeAddWalkInModal() {
    document.getElementById('addWalkInModal').classList.add('hidden');
}

function triggerWalkInAiAssistant() {
    const text = document.getElementById('walkInDescription')?.value.toLowerCase().trim() || '';
    const bureauText = document.getElementById('walkInAiBureauText');
    const deptSelect = document.getElementById('walkInDepartmentSelect');
    const prioritySelect = document.getElementById('walkInPrioritySelect');
    const confBadge = document.getElementById('walkInAiConfidenceScore');

    if (!text || text.length < 5) return;

    let matched = null;
    for (const rule of routingRulesDataset) {
        if (rule.keywords.some(kw => text.includes(kw))) {
            matched = rule;
            break;
        }
    }

    if (matched) {
        if (bureauText) bureauText.innerText = matched.department_name;
        if (deptSelect) deptSelect.value = matched.department_key;
        if (prioritySelect) prioritySelect.value = matched.default_priority;
        if (confBadge) confBadge.innerText = '97% Match';
    }
}

function submitNewWalkInReport(event) {
    event.preventDefault();

    const name = document.getElementById('walkInCitizenName').value.trim();
    const phone = document.getElementById('walkInCitizenPhone').value.trim();
    const brgy = document.getElementById('walkInBarangay').value;
    const landmark = document.getElementById('walkInLandmark').value.trim();
    const title = document.getElementById('walkInTitle').value.trim();
    const desc = document.getElementById('walkInDescription').value.trim();
    const deptKey = document.getElementById('walkInDepartmentSelect').value;
    const priority = document.getElementById('walkInPrioritySelect').value;

    const randomIdNum = Math.floor(1000 + Math.random() * 9000);
    const newId = `CAL-REP-2026-${randomIdNum}`;
    const now = getCurrentFormattedTime();

    let category = 'Road & Infrastructure';
    const rule = routingRulesDataset.find(r => r.department_key === deptKey);
    if (rule) category = rule.category;

    const newRecord = {
        id: newId,
        title: title,
        description: desc,
        category: category,
        department_key: deptKey,
        department_name: departmentsMap[deptKey]?.name || 'City Engineering Office',
        priority: priority,
        stage: 'routed',
        sla_text: priority === 'Urgent' ? '⏱ 4h remaining' : (priority === 'High' ? '⏱ 24h remaining' : '⏱ 48h remaining'),
        sla_status: priority === 'Urgent' ? 'urgent' : 'normal',
        sla_total_hours: priority === 'Urgent' ? 4 : (priority === 'High' ? 24 : 48),
        date_filed: now,
        citizen_name: name,
        citizen_phone: phone,
        citizen_email: `${name.toLowerCase().replace(/\s+/g, '.')}@civentral.gov`,
        is_anonymous: false,
        barangay: brgy,
        landmark: landmark,
        gps: '14.7565° N, 121.0437° E',
        assigned_officer: 'Unassigned (Waiting Desk Officer)',
        ai_confidence: 96,
        ai_reason: `Walk-in concern processed by triage engine and auto-routed to ${departmentsMap[deptKey]?.short}.`,
        ai_keywords: [category.toLowerCase(), brgy.toLowerCase()],
        has_duplicate: false,
        duplicate_text: '',
        photos: [],
        resolution_notes: '',
        resolution_photos: [],
        activity_log: [
            { time: now, actor: 'Walk-In Intake Officer', action: `Walk-in citizen grievance registered for ${name}. Automatically routed to ${departmentsMap[deptKey]?.short}.` }
        ]
    };

    concernsDataset.unshift(newRecord);
    closeAddWalkInModal();
    renderAllViews();
    showToast(`Walk-in grievance registered as ${newId} and dispatched!`, 'success');
}

// -------------------------------------------------------------
// CSV EXPORT ENGINE
// -------------------------------------------------------------
function exportConcernsToCSV() {
    const filtered = getFilteredConcerns();
    if (filtered.length === 0) {
        showToast('No concerns match current filters to export.', 'warning');
        return;
    }

    const headers = [
        'Reference ID',
        'Citizen Name',
        'Citizen Mobile',
        'Barangay',
        'Specific Landmark',
        'Concern Category',
        'Title',
        'Target Department',
        'Assigned Officer',
        'Priority Level',
        'Lifecycle Stage',
        'SLA Status',
        'Date Filed'
    ];

    const rows = filtered.map(c => [
        `"${c.id}"`,
        `"${c.is_anonymous ? 'Anonymous' : c.citizen_name.replace(/"/g, '""')}"`,
        `"${c.citizen_phone || ''}"`,
        `"${c.barangay}"`,
        `"${c.landmark.replace(/"/g, '""')}"`,
        `"${c.category}"`,
        `"${c.title.replace(/"/g, '""')}"`,
        `"${c.department_name.replace(/"/g, '""')}"`,
        `"${(c.assigned_officer || 'Unassigned').replace(/"/g, '""')}"`,
        `"${c.priority}"`,
        `"${getStageLabel(c.stage)}"`,
        `"${c.sla_text}"`,
        `"${c.date_filed}"`
    ]);

    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `CIVentral_Concern_Dispatch_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast(`Exported ${filtered.length} concern records to CSV.`, 'success');
}

// -------------------------------------------------------------
// IMAGE LIGHTBOX
// -------------------------------------------------------------
function openImageLightbox(src) {
    const img = document.getElementById('lightboxImage');
    if (img) img.src = src;
    document.getElementById('imageLightboxModal').classList.remove('hidden');
}

function closeImageLightbox() {
    document.getElementById('imageLightboxModal').classList.add('hidden');
}

// -------------------------------------------------------------
// UTILITY FUNCTIONS & BADGE GENERATORS
// -------------------------------------------------------------
function getPriorityBadgeHtml(priority) {
    const classes = getPriorityBadgeClasses(priority);
    return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${classes}">${priority}</span>`;
}

function getPriorityBadgeClasses(priority) {
    switch (priority) {
        case 'Urgent':
            return 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800 animate-pulse';
        case 'High':
            return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800';
        case 'Medium':
            return 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800';
        case 'Low':
        default:
            return 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
}

function getStageLabel(stageKey) {
    const stage = STAGES_ORDER.find(s => s.key === stageKey);
    return stage ? stage.label : stageKey;
}

function getStageBadgeHtml(stageKey) {
    const classes = getStageBadgeClasses(stageKey);
    const label = getStageLabel(stageKey);
    return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${classes}">${label}</span>`;
}

function getStageBadgeClasses(stageKey) {
    switch (stageKey) {
        case 'submitted':
            return 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
        case 'ai_analyzed':
            return 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-800';
        case 'routed':
            return 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800';
        case 'under_review':
            return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800';
        case 'in_progress':
            return 'bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-900/40 dark:text-cyan-300 dark:border-cyan-800';
        case 'resolved':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'closed':
            return 'bg-slate-200 text-slate-800 border border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}

function getSlaBadgeHtml(text, status) {
    const classes = getSlaBadgeClasses(status);
    return `<span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${classes}">${text}</span>`;
}

function getSlaBadgeClasses(status) {
    switch (status) {
        case 'overdue':
            return 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800 font-extrabold';
        case 'urgent':
            return 'bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300';
        case 'warning':
            return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300';
        case 'resolved':
        case 'closed':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300';
        case 'normal':
        default:
            return 'bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
    }
}

function updateKPIs() {
    const activeTickets = concernsDataset.filter(c => c.stage !== 'closed');
    const urgentHighTickets = concernsDataset.filter(c => ['Urgent', 'High'].includes(c.priority) && c.stage !== 'closed');
    const slaBreaches = concernsDataset.filter(c => c.sla_status === 'overdue' || c.sla_status === 'urgent');

    const totalActiveElem = document.getElementById('kpiTotalActive');
    const urgentHighElem = document.getElementById('kpiUrgentHigh');
    const slaBreachesElem = document.getElementById('kpiSlaBreaches');

    if (totalActiveElem) totalActiveElem.innerText = activeTickets.length;
    if (urgentHighElem) urgentHighElem.innerText = urgentHighTickets.length;
    if (slaBreachesElem) slaBreachesElem.innerText = slaBreaches.length;
}

function copyGpsPin() {
    const pin = document.getElementById('modalGpsPin')?.innerText;
    if (pin && navigator.clipboard) {
        navigator.clipboard.writeText(pin).then(() => {
            showToast('GPS coordinates copied to clipboard!', 'info');
        });
    }
}

function printConcernSummary() {
    window.print();
}

function getCurrentFormattedTime() {
    const now = new Date();
    return now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0') + ' ' +
        String(now.getHours() % 12 || 12).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ' ' +
        (now.getHours() >= 12 ? 'PM' : 'AM');
}

function escapeHtml(string) {
    if (!string) return '';
    return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// -------------------------------------------------------------
// TOAST NOTIFICATIONS
// -------------------------------------------------------------
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    let icon = 'fa-solid fa-circle-info text-blue-500';
    let border = 'border-blue-200 dark:border-blue-800';

    if (type === 'success') {
        icon = 'fa-solid fa-circle-check text-emerald-500';
        border = 'border-emerald-200 dark:border-emerald-800';
    } else if (type === 'warning') {
        icon = 'fa-solid fa-triangle-exclamation text-amber-500';
        border = 'border-amber-200 dark:border-amber-800';
    } else if (type === 'error') {
        icon = 'fa-solid fa-circle-xmark text-rose-500';
        border = 'border-rose-200 dark:border-rose-800';
    }

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-3 bg-white dark:bg-slate-900 border ${border} shadow-xl rounded-2xl px-4 py-3 text-xs font-bold text-slate-800 dark:text-white transition-all duration-300 transform translate-y-2 opacity-0 max-w-sm`;
    toast.innerHTML = `
        <i class="${icon} text-base shrink-0"></i>
        <span class="flex-1">${escapeHtml(message)}</span>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
</script>

<?php include '../../includes/footer.php'; ?>
