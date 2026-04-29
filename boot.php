<?php

declare(strict_types=1);

use App\Helpers\Hooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use MyAds\Plugins\AiAdCopywriter\AiAdCopywriterService;

require_once __DIR__ . '/src/AiAdCopywriterService.php';

if (!function_exists('ai_ad_copywriter_service')) {
    function ai_ad_copywriter_service(): AiAdCopywriterService
    {
        static $instance = null;
        if (!$instance instanceof AiAdCopywriterService) {
            $instance = new AiAdCopywriterService();
        }
        return $instance;
    }
}

// Load Translations
app('translator')->addNamespace('ai_ad_copywriter', __DIR__ . '/lang');

// Admin Routes
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/ai-ad-copywriter', function () {
        $service = ai_ad_copywriter_service();
        return view('ai_ad_copywriter::admin.settings', [
            'config' => $service->getConfig()
        ]);
    })->name('admin.ai-ad-copywriter.index');

    Route::post('/admin/ai-ad-copywriter/save', function (Request $request) {
        $service = ai_ad_copywriter_service();
        $service->saveConfig(['api_key' => $request->api_key]);
        return redirect()->back()->with('success', __('ai_ad_copywriter::messages.success_save'));
    })->name('admin.ai-ad-copywriter.save');

    Route::post('/admin/ai-ad-copywriter/test', function (Request $request) {
        try {
            $service = ai_ad_copywriter_service();
            $suggestions = $service->generateSuggestions($request->prompt, 'title');
            return response()->json(['success' => true, 'suggestions' => $suggestions]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    })->name('admin.ai-ad-copywriter.test');
});

// Member API Route
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/api/ai-ad-copywriter/generate', function (Request $request) {
        try {
            $service = ai_ad_copywriter_service();
            $suggestions = $service->generateSuggestions($request->prompt, $request->type ?? 'title');
            return response()->json(['success' => true, 'suggestions' => $suggestions]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    })->name('ai-ad-copywriter.generate');
});

View::addNamespace('ai_ad_copywriter', __DIR__ . '/views');

// Admin Sidebar
Hooks::add_action('admin_sidebar_menu', function (): void {
    $url = route('admin.ai-ad-copywriter.index');
    $isActive = request()->is('admin/ai-ad-copywriter*');
    $linkClass = $isActive ? 'nxl-link active' : 'nxl-link';

    echo '<li class="nxl-item">'
        . '<a href="' . e($url) . '" class="' . e($linkClass) . '">'
        . '<span class="nxl-micon"><i class="feather-zap"></i></span>'
        . '<span class="nxl-mtext">' . e(__('ai_ad_copywriter::messages.sidebar_menu')) . '</span>'
        . '</a>'
        . '</li>';
});

// Injection into Ad Creation Pages
Hooks::add_action('theme_master_before_body_close', function (): void {
    if (request()->is('ads/*/create') || request()->is('ads/*/edit') || request()->is('ads/*/*/edit') || request()->is('ads/*/*/promote') || request()->is('ads/smart/create') || request()->is('ads/banners/create') || request()->is('ads/links/create') || request()->is('ads/promote') || request()->is('ads/promote/*')) {
        echo view('ai_ad_copywriter::inject_js')->render();
    }
});
