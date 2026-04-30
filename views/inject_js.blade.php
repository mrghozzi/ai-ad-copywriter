<style>
    .ai-assistant-btn {
        position: absolute;
        right: 10px;
        top: 35px;
        background: #615dfa;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: all 0.2s ease;
        box-shadow: 0 4px 10px rgba(97, 93, 250, 0.3);
    }
    .ai-assistant-btn:hover {
        background: #4e4ac8;
        transform: scale(1.1);
    }
    .ai-assistant-btn i {
        font-size: 14px;
    }
    .form-group {
        position: relative;
    }
    
    #ai-suggestions-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 500px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        z-index: 100001;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
    }
    
    #ai-suggestions-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 100000;
    }
    
    .ai-modal-header {
        background: #615dfa;
        padding: 20px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ai-modal-body {
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .ai-suggestion-item {
        padding: 15px;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fbff;
    }
    
    .ai-suggestion-item:hover {
        border-color: #615dfa;
        background: #eff2ff;
        transform: translateX(5px);
    }
    
    .ai-loading {
        text-align: center;
        padding: 30px;
    }
    
    .ai-prompt-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #edf2f7;
        border-radius: 12px;
        margin-bottom: 20px;
    }
    
    .ai-prompt-input:focus {
        border-color: #615dfa;
        outline: none;
    }
</style>

<div id="ai-suggestions-overlay"></div>
<div id="ai-suggestions-modal">
    <div class="ai-modal-header">
        <h5 style="margin:0; color:#fff;"><i class="fas fa-magic"></i> {{ __('ai_ad_copywriter::messages.ai_assistant_title') }}</h5>
        <span id="ai-close-modal" style="cursor:pointer;"><i class="fas fa-times"></i></span>
    </div>
    <div class="ai-modal-body">
        <p style="font-weight: 600; margin-bottom: 10px;">{{ __('ai_ad_copywriter::messages.what_is_ad_about') }}</p>
        <input type="text" id="ai-topic-input" class="ai-prompt-input" placeholder="{{ __('ai_ad_copywriter::messages.test_prompt_placeholder') }}">
        <button type="button" id="ai-generate-btn" class="btn btn-primary w-100 mb-4" style="background:#615dfa; border:none; padding:12px; border-radius:12px;">{{ __('ai_ad_copywriter::messages.generate_btn') }}</button>
        
        <div id="ai-results-container">
            <!-- Suggestions will appear here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetFields = [
        'input[name="name"]',
        'textarea[name="txt"]',
        'input[name="headline_override"]',
        'textarea[name="description_override"]'
    ];
    
    let activeInput = null;
    let activeType = 'title';

    const overlay = document.getElementById('ai-suggestions-overlay');
    const modal = document.getElementById('ai-suggestions-modal');
    const resultsContainer = document.getElementById('ai-results-container');
    const topicInput = document.getElementById('ai-topic-input');
    const generateBtn = document.getElementById('ai-generate-btn');

    document.querySelectorAll(targetFields.join(',')).forEach(field => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ai-assistant-btn';
        btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i>';
        btn.title = '{{ __('ai_ad_copywriter::messages.ai_assistant_title') }}';
        
        // Adjust position if it's a textarea
        if (field.tagName === 'TEXTAREA') {
            btn.style.top = '35px';
        }

        field.parentElement.style.position = 'relative';
        field.parentElement.appendChild(btn);

        btn.addEventListener('click', function() {
            activeInput = field;
            activeType = (field.name === 'name' || field.name === 'headline_override') ? 'title' : 'description';
            
            overlay.style.display = 'block';
            modal.style.display = 'block';
            topicInput.focus();
            resultsContainer.innerHTML = '';
        });
    });

    const closeModal = () => {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    };

    document.getElementById('ai-close-modal').addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);

    generateBtn.addEventListener('click', function() {
        const topic = topicInput.value;
        if (!topic) return alert('{{ __('ai_ad_copywriter::messages.test_empty_prompt') }}');

        resultsContainer.innerHTML = '<div class="ai-loading"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">{{ __('ai_ad_copywriter::messages.thinking') }}</p></div>';

        fetch('{{ route("ai-ad-copywriter.generate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ prompt: topic, type: activeType })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultsContainer.innerHTML = '<p style="font-weight:600; margin-bottom:10px;">{{ __('ai_ad_copywriter::messages.pick_suggestion') }}</p>';
                data.suggestions.forEach(suggestion => {
                    const item = document.createElement('div');
                    item.className = 'ai-suggestion-item';
                    item.innerText = suggestion;
                    item.addEventListener('click', function() {
                        activeInput.value = suggestion;
                        closeModal();
                    });
                    resultsContainer.appendChild(item);
                });
            } else {
                resultsContainer.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
            }
        })
        .catch(error => {
            resultsContainer.innerHTML = '<div class="alert alert-danger">{{ __('ai_ad_copywriter::messages.test_connection_error') }} ' + error + '</div>';
        });
    });
});
</script>
