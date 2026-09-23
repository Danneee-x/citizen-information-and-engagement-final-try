<?php
/**
 * Civentral Dashboard Realistic Seed Data
 * Seeds realistic citizen concerns and certificate requests if empty,
 * ensuring the database records accurately match the operational command center metrics.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Civentral Dashboard Data Seeder ===\n";

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// 1. Seed citizen_concerns if empty or less than 14
$concernsCount = (int)$pdo->query("SELECT COUNT(*) FROM `citizen_concerns`")->fetchColumn();
echo "Current citizen_concerns count: $concernsCount\n";

if ($concernsCount < 14) {
    echo "Seeding realistic Caloocan citizen concerns...\n";
    
    $sampleConcerns = [
        [
            'ticket_number' => 'CCN-2026-0081',
            'citizen_name' => 'Maria Kristina Santos',
            'citizen_phone' => '0917-555-1234',
            'citizen_email' => 'kristina.santos@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Waste Management & Sanitation',
            'sub_category' => 'Uncollected Garbage',
            'title' => 'Uncollected Household Garbage on 5th Avenue',
            'description' => 'Garbage trucks have not collected trash for 4 consecutive days along 5th Avenue corner Rizal Ave. Foul odor is developing.',
            'location' => '5th Ave cor. Rizal Ave Extension',
            'barangay' => 'Barangay 54',
            'district' => 'District 1',
            'status' => 'In Progress',
            'priority' => 'High',
            'assigned_department' => 'Environmental / Waste Management Department',
            'created_at' => '2026-09-18 08:30:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0082',
            'citizen_name' => 'Anonymous Resident',
            'citizen_phone' => null,
            'citizen_email' => null,
            'is_anonymous' => 1,
            'category' => 'Public Infrastructure',
            'sub_category' => 'Potholes / Road Damage',
            'title' => 'Deep Potholes along Samson Road near Monumento',
            'description' => 'Multiple large potholes causing heavy traffic slowdown and risk of motorcycle accidents near the Monumento circle.',
            'location' => 'Samson Road near Monumento Circle',
            'barangay' => 'Barangay 81',
            'district' => 'District 2',
            'status' => 'Under Review',
            'priority' => 'Urgent',
            'assigned_department' => 'City Engineering & Public Works Office',
            'created_at' => '2026-09-19 14:15:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0083',
            'citizen_name' => 'Rolando Dela Cruz',
            'citizen_phone' => '0928-333-8899',
            'citizen_email' => 'roland.delacruz@yahoo.com',
            'is_anonymous' => 0,
            'category' => 'Public Safety & Streetlights',
            'sub_category' => 'Busted Streetlamp',
            'title' => 'Non-functioning street lamps in Bagumbong',
            'description' => 'Four street posts on Block 12 Bagumbong have blown fuses, leaving the dark alley prone to theft.',
            'location' => 'Block 12, Phase 3, Bagumbong',
            'barangay' => 'Barangay 171 (Bagumbong)',
            'district' => 'District 1',
            'status' => 'New',
            'priority' => 'Medium',
            'assigned_department' => 'Public Safety Electrical Division',
            'created_at' => '2026-09-20 19:40:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0084',
            'citizen_name' => 'Grace Bautista',
            'citizen_phone' => '0945-888-2121',
            'citizen_email' => 'grace.b@outlook.com',
            'is_anonymous' => 0,
            'category' => 'Drainage & Flood Control',
            'sub_category' => 'Clogged Canal',
            'title' => 'Severe Canal Clogging along Zabarte Road',
            'description' => 'Plastic wastes blocking drainage outflow causing flash flooding during afternoon downpours.',
            'location' => 'Zabarte Road near North Caloocan District Hospital',
            'barangay' => 'Barangay 177',
            'district' => 'District 3',
            'status' => 'In Progress',
            'priority' => 'High',
            'assigned_department' => 'Caloocan Flood Control & Drainage Bureau',
            'created_at' => '2026-09-21 07:15:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0085',
            'citizen_name' => 'Juancho Velasquez',
            'citizen_phone' => '0919-444-5566',
            'citizen_email' => 'jvelasquez@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Peace & Order / Noise',
            'sub_category' => 'Late-Night Videoke',
            'title' => 'Loud videoke and public disturbance past midnight',
            'description' => 'Commercial establishment playing excessively loud sound equipment until 3 AM on weeknights.',
            'location' => '10th Avenue cor. P. Burgos St.',
            'barangay' => 'Barangay 64',
            'district' => 'District 2',
            'status' => 'New',
            'priority' => 'Low',
            'assigned_department' => 'Barangay Peacekeeping Action Team (Tanod)',
            'created_at' => '2026-09-21 23:50:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0086',
            'citizen_name' => 'Anonymous Resident',
            'citizen_phone' => null,
            'citizen_email' => null,
            'is_anonymous' => 1,
            'category' => 'Public Infrastructure',
            'sub_category' => 'Damaged Sidewalk',
            'title' => 'Broken Sidewalk slabs near Monumento LRT Station',
            'description' => 'Exposed rebar and broken concrete pose severe hazard for pedestrians and elderly residents.',
            'location' => 'Rizal Ave near LRT 1 Monumento Station',
            'barangay' => 'Barangay 88',
            'district' => 'District 2',
            'status' => 'Under Review',
            'priority' => 'Medium',
            'assigned_department' => 'City Engineering & Public Works Office',
            'created_at' => '2026-09-22 09:10:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0087',
            'citizen_name' => 'Bernardo Ocampo',
            'citizen_phone' => '0908-111-2233',
            'citizen_email' => 'b.ocampo@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Health & Sanitation',
            'sub_category' => 'Dengue Prevention',
            'title' => 'Request for Mosquito Fogging / Anti-Dengue Spraying',
            'description' => 'Multiple suspected dengue cases in our compound. Requesting immediate misting and larva abatement.',
            'location' => 'Phase 4, Bagong Silang',
            'barangay' => 'Barangay 176',
            'district' => 'District 1',
            'status' => 'In Progress',
            'priority' => 'Urgent',
            'assigned_department' => 'City Health Department - Vector Control',
            'created_at' => '2026-09-22 11:30:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0088',
            'citizen_name' => 'Theresa Mendoza',
            'citizen_phone' => '0915-777-6655',
            'citizen_email' => 'theresa.mendoza@yahoo.com',
            'is_anonymous' => 0,
            'category' => 'Traffic & Transport',
            'sub_category' => 'Illegal Parking',
            'title' => 'Tricycle terminal obstructing primary thoroughfare',
            'description' => 'Unlicensed colorum tricycles occupying two lanes during morning peak hours causing severe gridlock.',
            'location' => 'Camarin Road cor. Susano Road',
            'barangay' => 'Barangay 174',
            'district' => 'District 3',
            'status' => 'Routed',
            'priority' => 'High',
            'assigned_department' => 'Caloocan Public Safety & Police Bureau (CPTMD)',
            'created_at' => '2026-09-22 13:45:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0089',
            'citizen_name' => 'Eduardo Ramos',
            'citizen_phone' => '0922-999-4433',
            'citizen_email' => 'eramos_caloocan@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Waste Management & Sanitation',
            'sub_category' => 'Illegal Dumping',
            'title' => 'Open Dump site sprouting in vacant lot',
            'description' => 'Commercial waste trucks dumping construction debris and organic waste in an unfenced lot.',
            'location' => 'Deparo Road near Caloocan High School annex',
            'barangay' => 'Barangay 168',
            'district' => 'District 1',
            'status' => 'Under Review',
            'priority' => 'High',
            'assigned_department' => 'Environmental / Waste Management Department',
            'created_at' => '2026-09-22 16:20:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0090',
            'citizen_name' => 'Patricia Nicole Alcantara',
            'citizen_phone' => '0939-222-1100',
            'citizen_email' => 'patricia.alcantara@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Public Safety & Streetlights',
            'sub_category' => 'Fallen Tree Branch on Wire',
            'title' => 'Heavy acacia branch resting on primary power line',
            'description' => 'Branch snapped during wind gusts and is resting on Meralco wires and cable lines.',
            'location' => 'Congressional Road near Caloocan Sports Complex',
            'barangay' => 'Barangay 173',
            'district' => 'District 1',
            'status' => 'In Progress',
            'priority' => 'Urgent',
            'assigned_department' => 'Public Safety Electrical Division',
            'created_at' => '2026-09-22 18:00:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0091',
            'citizen_name' => 'Arnel Fernandez',
            'citizen_phone' => '0918-666-3322',
            'citizen_email' => 'a.fernandez@yahoo.com',
            'is_anonymous' => 0,
            'category' => 'Drainage & Flood Control',
            'sub_category' => 'Drainage Grate Stolen',
            'title' => 'Missing storm drain iron manhole cover',
            'description' => 'Open manhole on sidewalk posing extreme danger to children walking to school.',
            'location' => 'A. Mabini St. cor. 2nd Avenue',
            'barangay' => 'Barangay 12',
            'district' => 'District 2',
            'status' => 'New',
            'priority' => 'Urgent',
            'assigned_department' => 'Caloocan Flood Control & Drainage Bureau',
            'created_at' => '2026-09-23 06:15:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0092',
            'citizen_name' => 'Cynthia Gutierrez',
            'citizen_phone' => '0917-888-9900',
            'citizen_email' => 'cynthiag@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Community Welfare',
            'sub_category' => 'Stray Animal Control',
            'title' => 'Pack of aggressive stray dogs near market entrance',
            'description' => 'Unvaccinated stray dogs roaming the wet market entrance and harassing shoppers.',
            'location' => 'Maypajo Wet Market entrance, J.P. Rizal St.',
            'barangay' => 'Barangay 28',
            'district' => 'District 2',
            'status' => 'New',
            'priority' => 'Medium',
            'assigned_department' => 'City Veterinary & Animal Control Office',
            'created_at' => '2026-09-23 07:45:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0093',
            'citizen_name' => 'Danilo Tolentino',
            'citizen_phone' => '0927-444-1234',
            'citizen_email' => 'dtolentino@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Public Infrastructure',
            'sub_category' => 'Water Pipe Leak',
            'title' => 'Main waterline pipe leaking on Quirino Highway',
            'description' => 'Clean water spurting onto roadway for the past 24 hours, washing away asphalt subbase.',
            'location' => 'Quirino Highway near Bankers Village',
            'barangay' => 'Barangay 185',
            'district' => 'District 3',
            'status' => 'Routed',
            'priority' => 'High',
            'assigned_department' => 'City Engineering & Public Works Office',
            'created_at' => '2026-09-23 09:20:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0094',
            'citizen_name' => 'Lourdes Manansala',
            'citizen_phone' => '0933-555-7890',
            'citizen_email' => 'lourdes.m@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Health & Sanitation',
            'sub_category' => 'Public Market Hygiene',
            'title' => 'Clogged drainage canal inside Bagong Silang Talipapa',
            'description' => 'Waste water from meat section overflowing into vendor aisles.',
            'location' => 'Phase 1 Talipapa, Bagong Silang',
            'barangay' => 'Barangay 176',
            'district' => 'District 1',
            'status' => 'New',
            'priority' => 'High',
            'assigned_department' => 'Environmental / Waste Management Department',
            'created_at' => '2026-09-23 10:30:00'
        ],
        // 4 Resolved concerns for historical ratio
        [
            'ticket_number' => 'CCN-2026-0070',
            'citizen_name' => 'Fernando Castro',
            'citizen_phone' => '0916-222-3344',
            'citizen_email' => 'fcastro@gmail.com',
            'is_anonymous' => 0,
            'category' => 'Public Infrastructure',
            'sub_category' => 'Pothole Repair',
            'title' => 'Road asphalt repair on 10th Avenue',
            'description' => 'Pothole repaired and hot mix asphalt applied by district road maintenance team.',
            'location' => '10th Avenue cor. B. Serrano St.',
            'barangay' => 'Barangay 60',
            'district' => 'District 2',
            'status' => 'Resolved',
            'priority' => 'Medium',
            'assigned_department' => 'City Engineering & Public Works Office',
            'created_at' => '2026-08-14 10:00:00'
        ],
        [
            'ticket_number' => 'CCN-2026-0071',
            'citizen_name' => 'Elena Villanueva',
            'citizen_phone' => '0920-111-9988',
            'citizen_email' => 'elena.v@yahoo.com',
            'is_anonymous' => 0,
            'category' => 'Waste Management & Sanitation',
            'sub_category' => 'Special Collection',
            'title' => 'Bulky waste collection request cleared',
            'description' => 'Discarded tree trimmings hauled by CENRO dump truck.',
            'location' => 'Pangarap Village',
            'barangay' => 'Barangay 181',
            'district' => 'District 3',
            'status' => 'Resolved',
            'priority' => 'Medium',
            'assigned_department' => 'Environmental / Waste Management Department',
            'created_at' => '2026-08-22 14:30:00'
        ]
    ];

    $ins = $pdo->prepare("INSERT INTO `citizen_concerns` 
        (`ticket_number`, `citizen_name`, `citizen_phone`, `citizen_email`, `is_anonymous`, `category`, `sub_category`, `title`, `description`, `location`, `barangay`, `district`, `status`, `priority`, `assigned_department`, `created_at`)
        VALUES (:tn, :cn, :cp, :ce, :ia, :cat, :scat, :title, :desc, :loc, :brgy, :dist, :stat, :prio, :dept, :ca)");

    foreach ($sampleConcerns as $c) {
        try {
            $ins->execute([
                ':tn' => $c['ticket_number'],
                ':cn' => $c['citizen_name'],
                ':cp' => $c['citizen_phone'],
                ':ce' => $c['citizen_email'],
                ':ia' => $c['is_anonymous'],
                ':cat' => $c['category'],
                ':scat' => $c['sub_category'],
                ':title' => $c['title'],
                ':desc' => $c['description'],
                ':loc' => $c['location'],
                ':brgy' => $c['barangay'],
                ':dist' => $c['district'],
                ':stat' => $c['status'],
                ':prio' => $c['priority'],
                ':dept' => $c['assigned_department'],
                ':ca' => $c['created_at']
            ]);
        } catch (Throwable $e) {
            echo "Notice on {$c['ticket_number']}: " . $e->getMessage() . "\n";
        }
    }
    echo "[OK] citizen_concerns seeded.\n";
}

// 2. Seed certificate_requests if empty
$certPdo = getCertificateDbConnection();
$certCount = (int)$certPdo->query("SELECT COUNT(*) FROM `certificate_requests`")->fetchColumn();
echo "Current certificate_requests count: $certCount\n";

if ($certCount < 10) {
    echo "Seeding realistic certificate requests...\n";
    $sampleCerts = [
        [
            'reference_no' => 'REQ-2026-0401',
            'citizen_name' => 'Danny Espelita',
            'contact_number' => '0917-123-4567',
            'email' => 'danny.espelita@gmail.com',
            'street_address' => 'Blk 5 Lot 12 Bagumbong',
            'barangay' => 'Barangay 171 (Bagumbong)',
            'district' => 'District 1',
            'certificate_type' => 'Barangay Clearance',
            'purpose' => 'Local Employment',
            'fee_amount' => 50.00,
            'payment_status' => 'Paid',
            'status' => 'Released',
            'created_at' => '2026-09-10 09:00:00'
        ],
        [
            'reference_no' => 'REQ-2026-0402',
            'citizen_name' => 'Jefferson Lee',
            'contact_number' => '0928-555-7788',
            'email' => 'jeff.lee@gmail.com',
            'street_address' => 'Phase 2 Package 3',
            'barangay' => 'Barangay 176',
            'district' => 'District 1',
            'certificate_type' => 'Certificate of Indigency',
            'purpose' => 'Medical Assistance / DSWD',
            'fee_amount' => 0.00,
            'payment_status' => 'Waived',
            'status' => 'Approved',
            'created_at' => '2026-09-15 11:30:00'
        ],
        [
            'reference_no' => 'REQ-2026-0403',
            'citizen_name' => 'Renz Millares',
            'contact_number' => '0945-111-2233',
            'email' => 'renz.m@gmail.com',
            'street_address' => '12 Mabini Street',
            'barangay' => 'Barangay 1',
            'district' => 'District 1',
            'certificate_type' => 'Certificate of Residency',
            'purpose' => 'Bank Account Opening',
            'fee_amount' => 50.00,
            'payment_status' => 'Paid',
            'status' => 'Ready for Release',
            'created_at' => '2026-09-18 14:20:00'
        ],
        [
            'reference_no' => 'REQ-2026-0404',
            'citizen_name' => 'Tony Stark',
            'contact_number' => '0999-888-7766',
            'email' => 'tony.stark@avengers.org',
            'street_address' => 'Stark Tech Alley',
            'barangay' => 'Barangay 25',
            'district' => 'District 2',
            'certificate_type' => 'Barangay Business Permit Clearance',
            'purpose' => 'Commercial License Renewal',
            'fee_amount' => 200.00,
            'payment_status' => 'Paid',
            'status' => 'Under Review',
            'created_at' => '2026-09-21 16:00:00'
        ],
        [
            'reference_no' => 'REQ-2026-0405',
            'citizen_name' => 'Juan Enrile',
            'contact_number' => '0905-333-2211',
            'email' => 'juan.enrile@senate.gov.ph',
            'street_address' => 'Villa Dela Rosa',
            'barangay' => 'Barangay 188',
            'district' => 'District 3',
            'certificate_type' => 'Senior Citizen Barangay Certification',
            'purpose' => 'Centenarian LGU Pension Benefit',
            'fee_amount' => 0.00,
            'payment_status' => 'Waived',
            'status' => 'Pending',
            'created_at' => '2026-09-22 10:15:00'
        ]
    ];

    $insCert = $certPdo->prepare("INSERT INTO `certificate_requests`
        (`reference_no`, `citizen_name`, `contact_number`, `email`, `street_address`, `barangay`, `district`, `certificate_type`, `purpose`, `fee_amount`, `payment_status`, `status`, `created_at`)
        VALUES (:rn, :cn, :num, :email, :addr, :brgy, :dist, :type, :purp, :fee, :ps, :st, :ca)");

    foreach ($sampleCerts as $sc) {
        try {
            $insCert->execute([
                ':rn' => $sc['reference_no'],
                ':cn' => $sc['citizen_name'],
                ':num' => $sc['contact_number'],
                ':email' => $sc['email'],
                ':addr' => $sc['street_address'],
                ':brgy' => $sc['barangay'],
                ':dist' => $sc['district'],
                ':type' => $sc['certificate_type'],
                ':purp' => $sc['purpose'],
                ':fee' => $sc['fee_amount'],
                ':ps' => $sc['payment_status'],
                ':st' => $sc['status'],
                ':ca' => $sc['created_at']
            ]);
        } catch (Throwable $e) {
            echo "Notice on {$sc['reference_no']}: " . $e->getMessage() . "\n";
        }
    }
    echo "[OK] certificate_requests seeded.\n";
}

echo "=== Seeding Completed Successfully! ===\n";
