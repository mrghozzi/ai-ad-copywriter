@extends('admin::layouts.admin')

@section('title', __('ai_ad_copywriter::messages.settings_title'))
@section('admin_shell_header_mode', 'hidden')

@section('content')
<div class="admin-page ai-ad-copywriter-page">
    <section class="admin-hero">
        <div class="admin-hero__content">
            <ul class="admin-breadcrumb">
                <li><a href="{{ route('admin.index') }}">{{ __('ai_ad_copywriter::messages.breadcrumb_home') }}</a></li>
                <li>{{ __('ai_ad_copywriter::messages.breadcrumb_plugins') }}</li>
                <li>{{ __('ai_ad_copywriter::messages.breadcrumb_current') }}</li>
            </ul>
            <div class="admin-hero__eyebrow">AI Ad Copywriter</div>
            <h1 class="admin-hero__title">{{ __('ai_ad_copywriter::messages.hero_title') }}</h1>
            <p class="admin-hero__copy">
                {{ __('ai_ad_copywriter::messages.hero_copy') }}
            </p>
        </div>
        <div class="admin-hero__actions">
            <div class="admin-summary-grid w-100">
                <div class="admin-summary-card">
                    <span class="admin-summary-label">{{ __('ai_ad_copywriter::messages.status_label') }}</span>
                    <span class="admin-summary-value {{ !empty($config['api_key']) ? 'text-success' : 'text-danger' }}">
                        {{ !empty($config['api_key']) ? __('ai_ad_copywriter::messages.status_active') : __('ai_ad_copywriter::messages.status_inactive') }}
                    </span>
                    <span class="admin-summary-meta">
                        {{ !empty($config['api_key']) ? __('ai_ad_copywriter::messages.status_active_desc') : __('ai_ad_copywriter::messages.status_inactive_desc') }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-4">{{ session('error') }}</div>
    @endif

    <div class="admin-workspace-grid">
        <section class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <span class="admin-panel__eyebrow">{{ __('ai_ad_copywriter::messages.config_header') }}</span>
                    <h2 class="admin-panel__title">{{ __('ai_ad_copywriter::messages.config_title') }}</h2>
                    <p class="admin-panel__copy mb-0">{{ __('ai_ad_copywriter::messages.config_desc') }}</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <form action="{{ route('admin.ai-ad-copywriter.save') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('ai_ad_copywriter::messages.api_key_label') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="feather-key"></i></span>
                            <input type="password" name="api_key" class="form-control" value="{{ $config['api_key'] ?? '' }}" placeholder="{{ __('ai_ad_copywriter::messages.api_key_placeholder') }}">
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">{!! __('ai_ad_copywriter::messages.api_key_help') !!}</small>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5 py-2" style="border-radius: 12px;">
                            <i class="feather-save me-1"></i> {{ __('ai_ad_copywriter::messages.save_settings') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <aside class="admin-panel">
            <div class="admin-panel__header">
                <div>
                    <span class="admin-panel__eyebrow">{{ __('ai_ad_copywriter::messages.test_header') }}</span>
                    <h2 class="admin-panel__title">{{ __('ai_ad_copywriter::messages.test_title') }}</h2>
                    <p class="admin-panel__copy mb-0">{{ __('ai_ad_copywriter::messages.test_desc') }}</p>
                </div>
            </div>
            <div class="admin-panel__body">
                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('ai_ad_copywriter::messages.test_prompt_label') }}</label>
                    <input type="text" id="test-prompt" class="form-control" placeholder="{{ __('ai_ad_copywriter::messages.test_prompt_placeholder') }}">
                </div>
                <button type="button" id="btn-test-ai" class="btn btn-outline-primary w-100 py-2" style="border-radius: 12px;">
                    <i class="feather-zap me-1"></i> {{ __('ai_ad_copywriter::messages.test_btn') }}
                </button>

                <div id="test-results" class="mt-3"></div>
            </div>
        </aside>
    </div>
</div>

<style>
    .ai-ad-copywriter-page { gap: 1.5rem; }
</style>

@push('scripts')
<script>
document.getElementById('btn-test-ai').addEventListener('click', function() {
    const prompt = document.getElementById('test-prompt').value;
    if (!prompt) return alert('{{ __('ai_ad_copywriter::messages.test_empty_prompt') }}');

    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> {{ __('ai_ad_copywriter::messages.test_generating') }}</div>';

    fetch('{{ route("admin.ai-ad-copywriter.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ prompt: prompt })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultsDiv.innerHTML = '<div class="list-group shadow-sm" style="border-radius: 12px; overflow: hidden;">' + 
                data.suggestions.map(s => '<div class="list-group-item list-group-item-action border-0 mb-1" style="background: #f8fbff; border-radius: 8px !important;">' + s + '</div>').join('') + 
                '</div>';
        } else {
            resultsDiv.innerHTML = '<div class="alert alert-danger mt-2" style="border-radius: 12px;">' + data.error + '</div>';
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = '<div class="alert alert-danger mt-2" style="border-radius: 12px;">خطأ في الاتصال: ' + error + '</div>';
    });
});
</script>
@endpush
@endsection
