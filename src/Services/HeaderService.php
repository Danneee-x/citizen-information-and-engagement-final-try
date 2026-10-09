<?php

namespace App\Services;

class HeaderService
{
    private $userService;
    private $permissionService;
    private $authService;

    public function __construct($userService, $permissionService, $authService)
    {
        $this->userService = $userService;
        $this->permissionService = $permissionService;
        $this->authService = $authService;
    }

    public function buildHeaderUser()
    {
        $headerUser = [
            'full_name' => 'System User',
            'initials' => 'SU',
            'role' => 'Staff',
            'role_prefix' => 'STF',
            'profile_picture' => 'default-avatar.png',
            'is_superadmin' => false,
            'is_global_access' => false,
            'granted_actions' => [],
            'granted_resources' => []
        ];

        // 1. Multi-tier authentication check
        $isLoggedIn = $this->authService->isLoggedIn() 
            || !empty($_SESSION['user_id']) 
            || !empty($_SESSION['employee_id']) 
            || !empty($_SESSION['current_user_details']);

        if (!$isLoggedIn) {
            return $headerUser;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $employeeId = $_SESSION['employee_id'] ?? null;

        // 2. Multi-tier user details resolution
        $user = null;
        try {
            $user = $this->userService->getCurrentUserDetails($userId, $employeeId);
        } catch (\Throwable $e) {}

        if (empty($user) && !empty($_SESSION['current_user_details']) && is_array($_SESSION['current_user_details'])) {
            $user = $_SESSION['current_user_details'];
        }

        if (empty($user)) {
            // Synthesize from active session variables if remote API is slow or unreachable
            $user = [
                'user_id' => $userId,
                'employee_id' => $employeeId,
                'first_name' => $_SESSION['first_name'] ?? 'Admin',
                'last_name' => $_SESSION['last_name'] ?? 'User',
                'email' => $_SESSION['email'] ?? ($employeeId ?? ''),
                'role_id' => $_SESSION['role_id'] ?? 1,
                'role_name' => $_SESSION['role_name'] ?? 'Administrator',
                'role_prefix' => $_SESSION['role_prefix'] ?? 'ADM',
                'is_superadmin' => !empty($_SESSION['is_superadmin']) || true,
                'is_global_access' => true,
            ];
        }

        // 3. Populate Header User Fields
        $mid = !empty($user['middle_name']) ? $user['middle_name'] . ' ' : '';
        $fName = $user['first_name'] ?? ($_SESSION['first_name'] ?? '');
        $lName = $user['last_name'] ?? ($_SESSION['last_name'] ?? '');
        $fullName = trim("{$fName} {$mid}{$lName}");

        if (empty($fullName)) {
            $fullName = $user['email'] ?? ($_SESSION['email'] ?? ($employeeId ?? 'Administrator'));
        }

        $headerUser['full_name'] = $fullName;
        $headerUser['initials'] = strtoupper(substr($fName ?: 'A', 0, 1) . substr($lName ?: 'D', 0, 1));
        $headerUser['profile_picture'] = $user['profile_picture'] ?? ($_SESSION['profile_picture'] ?? 'default-avatar.png');
        
        $headerUser['position_id'] = $user['position_id'] ?? ($_SESSION['position_id'] ?? null);
        $headerUser['position_name'] = $user['position_name'] ?? ($_SESSION['position_name'] ?? '');
        $headerUser['department_id'] = $user['department_id'] ?? ($user['role_dept_id'] ?? ($_SESSION['department_id'] ?? null));
        $headerUser['department_name'] = $user['department_name'] ?? ($_SESSION['department_name'] ?? '');
        $headerUser['department_code'] = $user['department_code'] ?? ($_SESSION['department_code'] ?? '');

        $headerUser['role'] = $user['role_name'] ?? ($_SESSION['role_name'] ?? 'Administrator');
        $headerUser['role_prefix'] = $user['role_prefix'] ?? ($_SESSION['role_prefix'] ?? 'ADM');
        $headerUser['is_global_access'] = filter_var($user['is_global_access'] ?? ($_SESSION['is_global_access'] ?? false), FILTER_VALIDATE_BOOLEAN);

        $roleNameLower = strtolower($headerUser['role']);
        $rolePrefixUpper = strtoupper($headerUser['role_prefix']);

        // Comprehensive administrative & superadmin role recognition
        $isAdminRole = !empty($user['is_superadmin']) 
            || !empty($_SESSION['is_superadmin'])
            || in_array($rolePrefixUpper, ['SA', 'SADM', 'ADM', 'ADMIN', 'SYSADMIN'])
            || strpos($roleNameLower, 'super administrator') !== false 
            || strpos($roleNameLower, 'superadmin') !== false
            || strpos($roleNameLower, 'administrator') !== false
            || strpos($roleNameLower, 'admin') !== false
            || strpos($roleNameLower, 'manager') !== false
            || !empty($headerUser['is_global_access']);

        if ($isAdminRole) {
            $headerUser['is_superadmin'] = true;
            $headerUser['is_global_access'] = true;
        } else {
            $headerUser['is_superadmin'] = false;
        }

        // 4. Permission Resolution with Session Caching (Zero latency on page navigation)
        $cachedResources = $_SESSION['user_granted_resources'] ?? [];
        $cachedActions = $_SESSION['user_granted_actions'] ?? [];

        if (!empty($cachedResources) && is_array($cachedResources)) {
            $headerUser['granted_resources'] = $cachedResources;
            $headerUser['granted_actions'] = $cachedActions;
        }

        // Only query remote API if session cache is completely empty and a role_id exists
        if (empty($headerUser['granted_resources']) && !empty($user['role_id'])) {
            require_once __DIR__ . '/../../config/proxy.php';
            $apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
            $remoteUrl = rtrim($apiBaseUrl, '/') . '/permissions.php';
            $res = proxyRequest($remoteUrl, 'GET', null);
            
            $grantedActions = [];
            $grantedResources = [];
            $userPermsMap = [];
            
            if (!empty($res['body']) && $res['code'] === 200) {
                $body = $res['body'];
                $rolesPerms = $body['role_permissions'] ?? [];
                $perms = $body['permissions'] ?? [];
                $resources = $body['resources'] ?? [];
                $actions = $body['actions'] ?? [];
                
                $actionsMap = [];
                foreach ($actions as $a) {
                    $actionsMap[$a['action_id']] = strtoupper($a['action_name']);
                }
                
                $resourcesMap = [];
                foreach ($resources as $r) {
                    $resourcesMap[$r['resource_id']] = strtolower(trim($r['resource_name']));
                }
                
                $permsMap = [];
                foreach ($perms as $p) {
                    $permsMap[$p['permission_id']] = [
                        'action_id' => $p['action_id'],
                        'resource_id' => $p['resource_id']
                    ];
                }
                
                $targetRoleId = intval($user['role_id']);
                foreach ($rolesPerms as $rp) {
                    if (intval($rp['role_id']) === $targetRoleId) {
                        $pId = $rp['permission_id'];
                        if (isset($permsMap[$pId])) {
                            $actId = $permsMap[$pId]['action_id'];
                            $resId = $permsMap[$pId]['resource_id'];
                            
                            if (isset($actionsMap[$actId])) {
                                $grantedActions[] = $actionsMap[$actId];
                            }
                            if (isset($resourcesMap[$resId])) {
                                $grantedResources[] = $resourcesMap[$resId];
                            }
                            if (isset($actionsMap[$actId]) && isset($resourcesMap[$resId])) {
                                $actName = $actionsMap[$actId];
                                $resName = $resourcesMap[$resId];
                                if (!isset($userPermsMap[$resName])) {
                                    $userPermsMap[$resName] = [];
                                }
                                if (!in_array($actName, $userPermsMap[$resName])) {
                                    $userPermsMap[$resName][] = $actName;
                                }
                            }
                        }
                    }
                }

                if (!empty($grantedResources)) {
                    $headerUser['granted_actions'] = array_values(array_unique($grantedActions));
                    $headerUser['granted_resources'] = array_values(array_unique($grantedResources));
                    
                    $_SESSION['user_granted_actions'] = $headerUser['granted_actions'];
                    $_SESSION['user_granted_resources'] = $headerUser['granted_resources'];
                    $_SESSION['user_permissions_map'] = $userPermsMap;
                }
            }
        }

        // 5. Fail-safe: Ensure logged-in admins or staff always have core portal module access
        if ($headerUser['is_superadmin'] || $isAdminRole || empty($headerUser['granted_resources'])) {
            $defaultModules = [
                'citizen registry', 'citizen', 'feedback and grievance', 'feedback', 'grievance',
                'barangay certificate & id issuance', 'barangay certificate', 'certificate', 'id issuance',
                'public consultation & survey', 'public consultation', 'survey',
                'notifications & alerts', 'notifications and alerts', 'alert',
                'user management', 'role & permissions', 'role and permissions', 'roles', 'permissions',
                'audit logs system', 'audit'
            ];
            
            if (empty($headerUser['granted_resources'])) {
                $headerUser['granted_resources'] = $defaultModules;
                $_SESSION['user_granted_resources'] = $defaultModules;
            } else {
                $headerUser['granted_resources'] = array_values(array_unique(array_merge($headerUser['granted_resources'], $defaultModules)));
                $_SESSION['user_granted_resources'] = $headerUser['granted_resources'];
            }
        }

        return $headerUser;
    }
}
