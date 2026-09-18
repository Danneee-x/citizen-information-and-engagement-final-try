<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto bg-slate-50">
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
    <div class="space-y-1.5">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Notifications & Alerts</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Alert Templates</span>
      </div>
      <h1 class="text-2xl font-black text-slate-950 dark:text-white tracking-tight flex items-center gap-2.5 mt-2">
        <span>Alert Templates</span>
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 max-w-3xl leading-relaxed font-medium">
        This is a blank page ready for content.
      </p>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
