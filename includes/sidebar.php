    <?php
      $currentPage = basename($_SERVER['PHP_SELF']);

      $usermanagementPages = [
        'user-directory.php',
        'create-account.php',
        'account-status.php'
      ];

      $rolesmanagementPages = [
        'roles-management.php',
        'permissions.php',
        'module-management.php',
        'resource-management.php',
        'resourcemanagement.php',
        'action-management.php',
        'actionmanagement.php',
        'access-control.php'
      ];

      $departmentmanagementPages = [
        'departments.php'
      ];
      $citizenPages = [
        'citizen-directory.php',
        'citizen-account.php'
      ];
      $scholarshipPages = [
        'scholarship-types.php'
      ];
      $auditPages = [
        'user-activities.php',
        'login-history.php',
        'data-changes.php'
      ];

      $citizenRegistryPages = ['registered-citizens.php', 'pending-approvals.php', 'id-verification-logs.php', 'duplicate-flags.php'];
      $feedbackGrievancePages = ['incoming-concerns.php', 'ai-analysis-results.php', 'concern-routing.php', 'resolved-concerns.php'];
      $certificateIssuancePages = ['certificate-requests.php', 'certificate-pending-approvals.php', 'issued-certificates.php'];
      $publicConsultationPages = ['manage-surveys.php', 'live-results.php', 'participation-analytics.php', 'published-consultations.php'];
      $notificationsAlertsPages = ['compose-alert.php', 'broadcast-history.php', 'delivery-reports.php', 'alert-templates.php'];

      $isSuperAdmin = !empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']);
      $userGrantedRes = $headerUser['granted_resources'] ?? [];

      // Dynamic RBAC Permission Checker
      $hasResourceAccess = function($keywords) use ($isSuperAdmin, $userGrantedRes) {
          if ($isSuperAdmin) return true;
          if (empty($userGrantedRes)) return false;
          if (is_string($keywords)) $keywords = [$keywords];
          foreach ($userGrantedRes as $resName) {
              $resLower = strtolower($resName);
              foreach ($keywords as $kw) {
                  if (strpos($resLower, strtolower($kw)) !== false) return true;
              }
          }
          return false;
      };
    ?>
    
    <aside id="sidebar" class="bg-brand-light text-slate-600 w-72 min-h-[calc(100vh-5rem)] flex flex-col justify-between transition-all duration-300 border-r border-brand-border/60 sticky top-20 h-[calc(100vh-5rem)] z-30 shrink-0 shadow-sm">
      
      <div class="flex flex-col h-full overflow-hidden">
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
          
          <div class="sidebar-divider px-1 pb-3 mb-2 border-b">
            <button onclick="toggleSidebar()" class="sidebar-collapse-btn w-full py-2 rounded-xl border flex items-center justify-center focus:outline-none transition cursor-pointer shadow-xs" title="Collapse Menu Panel">
              <i id="toggleArrow" class="fa-solid fa-chevron-left text-xs"></i>
            </button>
          </div>

          <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mb-2">Citizen Information & Engagement</span>

          <a href="<?php echo $basePath ?? '../'; ?>pages/dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs tracking-wide transition cursor-pointer <?php echo $currentPage == 'dashboard.php' ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
            <i class="fa-solid fa-table-columns text-sm <?php echo $currentPage == 'dashboard.php' ? 'text-brand-medium' : 'text-slate-400'; ?>"></i>
            <span class="sidebar-text truncate">Dashboard Overview</span>
          </a>

          <?php 
          $canAccessCitizenRegistry = $isSuperAdmin || $hasResourceAccess(['citizen registry', 'citizen']);
          if ($canAccessCitizenRegistry): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('citizenRegistryDropdown', 'citizenRegistryChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $citizenRegistryPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-users-rectangle text-sm <?php echo in_array($currentPage, $citizenRegistryPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Citizen Registry</span>
              </div>
                <div class="dropdown-right">
                    <i id="citizenRegistryChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $citizenRegistryPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="citizenRegistryDropdown" class="<?php echo in_array($currentPage, $citizenRegistryPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen-registry/registered-citizens.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'registered-citizens.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-id-card-clip text-[10px] <?php echo $currentPage == 'registered-citizens.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Registered Citizens</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen-registry/pending-approvals.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'pending-approvals.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clock text-[10px] <?php echo $currentPage == 'pending-approvals.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Pending Approvals</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen-registry/id-verification-logs.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'id-verification-logs.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-address-card text-[10px] <?php echo $currentPage == 'id-verification-logs.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>ID Verification Logs</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen-registry/duplicate-flags.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'duplicate-flags.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-flag text-[10px] <?php echo $currentPage == 'duplicate-flags.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Duplicate Flags</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessFeedback = $isSuperAdmin || $hasResourceAccess(['feedback', 'grievance', 'concerns', 'citizen']);
          if ($canAccessFeedback): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('feedbackDropdown', 'feedbackChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $feedbackGrievancePages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-comments text-sm <?php echo in_array($currentPage, $feedbackGrievancePages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Feedback & Grievance</span>
              </div>
                <div class="dropdown-right">
                    <i id="feedbackChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $feedbackGrievancePages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="feedbackDropdown" class="<?php echo in_array($currentPage, $feedbackGrievancePages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/feedback-grievance/incoming-concerns.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'incoming-concerns.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-inbox text-[10px] <?php echo $currentPage == 'incoming-concerns.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Incoming Concerns</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/feedback-grievance/ai-analysis-results.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'ai-analysis-results.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-robot text-[10px] <?php echo $currentPage == 'ai-analysis-results.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>AI Analysis Results</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/feedback-grievance/concern-routing.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'concern-routing.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-route text-[10px] <?php echo $currentPage == 'concern-routing.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Concern Routing</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/feedback-grievance/resolved-concerns.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'resolved-concerns.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-check-circle text-[10px] <?php echo $currentPage == 'resolved-concerns.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Resolved Concerns</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessCertificates = $isSuperAdmin || $hasResourceAccess(['certificate', 'id issuance', 'payment', 'citizen']);
          if ($canAccessCertificates): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('certificateDropdown', 'certificateChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $certificateIssuancePages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-certificate text-sm <?php echo in_array($currentPage, $certificateIssuancePages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Barangay Certificate & ID Issuance</span>
              </div>
                <div class="dropdown-right">
                    <i id="certificateChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $certificateIssuancePages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="certificateDropdown" class="<?php echo in_array($currentPage, $certificateIssuancePages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/certificate-issuance/certificate-requests.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'certificate-requests.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-signature text-[10px] <?php echo $currentPage == 'certificate-requests.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Certificate Requests</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/certificate-issuance/certificate-pending-approvals.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'certificate-pending-approvals.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clock-rotate-left text-[10px] <?php echo $currentPage == 'certificate-pending-approvals.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Pending Approvals</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/certificate-issuance/issued-certificates.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'issued-certificates.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-stamp text-[10px] <?php echo $currentPage == 'issued-certificates.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Issued Certificates</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessSurveys = $isSuperAdmin || $hasResourceAccess(['survey', 'consultation', 'analytics', 'citizen']);
          if ($canAccessSurveys): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('surveyDropdown', 'surveyChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $publicConsultationPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-poll text-sm <?php echo in_array($currentPage, $publicConsultationPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Public Consultation & Survey</span>
              </div>
                <div class="dropdown-right">
                    <i id="surveyChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $publicConsultationPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="surveyDropdown" class="<?php echo in_array($currentPage, $publicConsultationPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/public-consultation/manage-surveys.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'manage-surveys.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-list-ul text-[10px] <?php echo $currentPage == 'manage-surveys.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Manage Surveys</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/public-consultation/live-results.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'live-results.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-chart-pie text-[10px] <?php echo $currentPage == 'live-results.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Live Results</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/public-consultation/participation-analytics.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'participation-analytics.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-chart-simple text-[10px] <?php echo $currentPage == 'participation-analytics.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Participation Analytics</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/public-consultation/published-consultations.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'published-consultations.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-bullhorn text-[10px] <?php echo $currentPage == 'published-consultations.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Published Consultations</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessAlerts = $isSuperAdmin || $hasResourceAccess(['notification', 'alert', 'broadcast', 'citizen']);
          if ($canAccessAlerts): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('alertDropdown', 'alertChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $notificationsAlertsPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-bell text-sm <?php echo in_array($currentPage, $notificationsAlertsPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Notifications & Alerts</span>
              </div>
                <div class="dropdown-right">
                    <i id="alertChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $notificationsAlertsPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="alertDropdown" class="<?php echo in_array($currentPage, $notificationsAlertsPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/notifications-alerts/compose-alert.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'compose-alert.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-pen text-[10px] <?php echo $currentPage == 'compose-alert.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Compose Alert</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/notifications-alerts/broadcast-history.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'broadcast-history.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clock-rotate-left text-[10px] <?php echo $currentPage == 'broadcast-history.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Broadcast History</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/notifications-alerts/delivery-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'delivery-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-envelope-open-text text-[10px] <?php echo $currentPage == 'delivery-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Delivery Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/notifications-alerts/alert-templates.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'alert-templates.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-lines text-[10px] <?php echo $currentPage == 'alert-templates.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Alert Templates</span></a>
            </div>
          </div>
          <?php endif; ?>

          <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mt-4 mb-2">Main Controls</span>

          <?php 
          $canAccessUserMgmt = $isSuperAdmin || $hasResourceAccess(['user directory', 'user account', 'users account', 'account status', 'user', 'account', 'employee']);
          if ($canAccessUserMgmt): 
          ?>
          <div class="space-y-1">
           <button onclick="toggleDropdown('userDropdown', 'userChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $usermanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
             <div class="flex items-center space-x-3">
                <i class="fa-solid fa-users-gear text-sm <?php echo in_array($currentPage, $usermanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                <span class="sidebar-text truncate">User Management</span>
             </div>
             <div class="dropdown-right">
                <i id="userChevron"
                   class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $usermanagementPages) ? 'rotate-180' : ''; ?>"></i>
             </div>
            </button>
            <div id="userDropdown" class="<?php echo in_array($currentPage, $usermanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/user-directory.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'user-directory.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-pen text-[10px] <?php echo $currentPage == 'user-directory.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>User Directory</span></a>

              <?php if ($isSuperAdmin || $hasResourceAccess(['users account', 'user account', 'create account'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/create-account.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'create-account.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-plus text-[10px] <?php echo $currentPage == 'create-account.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Create Staff Accounts</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['account status', 'status control', 'status'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/account-status.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'account-status.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-check text-[10px] <?php echo $currentPage == 'account-status.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Activate/Deactivate</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessRoleMgmt = $isSuperAdmin || $hasResourceAccess(['role', 'permission', 'module', 'resource', 'access control']);
          if ($canAccessRoleMgmt): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('roleDropdown', 'roleChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $rolesmanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
              <div class="flex items-center space-x-3">
                  <i class="fa-solid fa-user-shield text-sm <?php echo in_array($currentPage, $rolesmanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                  <span class="sidebar-text truncate">Role & Permissions</span>
              </div>
              <div class="dropdown-right">
                  <i id="roleChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $rolesmanagementPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>
            <div id="roleDropdown" class="<?php echo in_array($currentPage, $rolesmanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <?php if ($isSuperAdmin || $hasResourceAccess(['roles'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/roles-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'roles-management.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-users text-[10px] <?php echo $currentPage == 'roles-management.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Roles</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['module management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/module-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'module-management.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-cubes text-[10px] <?php echo $currentPage == 'module-management.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Module Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['resource management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/resource-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (in_array($currentPage, ['resource-management.php', 'resourcemanagement.php'])) ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-lines text-[10px] <?php echo (in_array($currentPage, ['resource-management.php', 'resourcemanagement.php'])) ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Resource Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['action management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/action-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (in_array($currentPage, ['action-management.php', 'actionmanagement.php'])) ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-bolt text-[10px] <?php echo (in_array($currentPage, ['action-management.php', 'actionmanagement.php'])) ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Action Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['permission builder'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/permissions.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'permissions.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-key text-[10px] <?php echo $currentPage == 'permissions.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Permission Builder</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['role permission matrix'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/access-control.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'access-control.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-shield-halved text-[10px] <?php echo $currentPage == 'access-control.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Role Permission Matrix</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessDeptMgmt = $hasResourceAccess(['department', 'position', 'sitemap', 'department management']);
          if ($canAccessDeptMgmt): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('deptDropdown', 'deptChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $departmentmanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-sitemap text-sm <?php echo in_array($currentPage, $departmentmanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Department Management</span>
              </div>
                <div class="dropdown-right">
                    <i id="deptChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $departmentmanagementPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="deptDropdown" class="<?php echo in_array($currentPage, $departmentmanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/department/departments.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'departments.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-building text-[10px] <?php echo $currentPage == 'departments.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Departments</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessCitizenMgmt = $hasResourceAccess(['citizen', 'kyc', 'verification']);
          if ($canAccessCitizenMgmt): 
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('citizenDropdown', 'citizenChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $citizenPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-address-book text-sm <?php echo in_array($currentPage, $citizenPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Citizen Management</span>
              </div>
                <div class="dropdown-right">
                    <i id="citizenChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $citizenPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="citizenDropdown" class="<?php echo in_array($currentPage, $citizenPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <?php if ($hasResourceAccess('citizen directory')): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen/citizen-directory.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'citizen-directory.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-id-card text-[10px] <?php echo $currentPage == 'citizen-directory.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Citizen Directory</span></a>
              <?php endif; ?>

              <?php if ($hasResourceAccess(['citizen account', 'kyc', 'verification'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen/citizen-account.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'citizen-account.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-database text-[10px] <?php echo $currentPage == 'citizen-account.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Citizen Account</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php
            $canAccessScholarshipModule = $hasResourceAccess(['scholarship', 'education', 'student']);
            if ($canAccessScholarshipModule):
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('scholarshipDropdown', 'scholarshipChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $scholarshipPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-graduation-cap text-sm <?php echo in_array($currentPage, $scholarshipPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Education & Scholarship</span>
              </div>
                <div class="dropdown-right">
                    <i id="scholarshipChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $scholarshipPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="scholarshipDropdown" class="<?php echo in_array($currentPage, $scholarshipPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/education-scholarship/scholarship-program/scholarship-types.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'scholarship-types.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-graduation-cap text-[10px] <?php echo $currentPage == 'scholarship-types.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Scholarship Types</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php 
          $canAccessAuditLogs = $hasResourceAccess(['audit', 'activity', 'log', 'change', 'history']);
          if ($canAccessAuditLogs): 
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('auditDropdown', 'auditChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $auditPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-clock-rotate-left text-sm <?php echo in_array($currentPage, $auditPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Audit Logs System</span>
              </div>

              <div class="dropdown-right">
                  <i id="auditChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $auditPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="auditDropdown" class="<?php echo in_array($currentPage, $auditPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/user-activities.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'user-activities.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-chart-line text-[10px] <?php echo $currentPage == 'user-activities.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>User Activities</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/login-history.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'login-history.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-history text-[10px] <?php echo $currentPage == 'login-history.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Login History</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/data-changes.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'data-changes.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-pen-to-square text-[10px] <?php echo $currentPage == 'data-changes.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Data Changes</span></a>
            </div>
          </div>
          <?php endif; ?>

        </nav>
        
        <div class="p-4 border-t shrink-0 sidebar-footer">
          <a href="#" onclick="openLogoutModal(event)" class="sidebar-logout-btn flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold tracking-wide transition group cursor-pointer">
            <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
            <span class="sidebar-text truncate">Logout</span>
          </a>
        </div>
      </div>
    </aside>
