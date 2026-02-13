@extends('layouts.guest')

@section('content')
<div class="max-w-2xl mx-auto px-6 py-12">
    
    {{-- Page Header --}}
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold mb-3">{{ __translator('Contact Support') }}</h1>
        <p class="opacity-70">{{ __translator('Have a question or need assistance? Send us a message.') }}</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
    <div class="rounded-lg p-4 mb-6" style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
        {{ session('success') }}
    </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
    <div class="rounded-lg p-4 mb-6 bg-red-600 text-white">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Support Form --}}
    <form action="{{ route('support.submit') }}" method="POST" class="space-y-6" enctype="multipart/form-data" id="support-form" data-recaptcha-action="support_submit">
        @csrf
        <input type="hidden" name="recaptcha_token" id="support-recaptcha-token">
        @if(request('customer'))
        <input type="hidden" name="customer_mandate_ref" value="{{ request('customer') }}">
        @endif

        {{-- From Name --}}
        <div>
            <label for="from_name" class="block text-sm font-medium mb-2">{{ __translator('Your Name') }}</label>
            <input type="text" 
                   id="from_name" 
                   name="from_name" 
                   value="{{ old('from_name') }}"
                   required
                   class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                   style="background-color: var(--card-background-color); color: var(--content-text-color);"
                   placeholder="{{ __translator('Your full name') }}">
        </div>

        {{-- From Email Address --}}
        <div>
            <label for="from_email" class="block text-sm font-medium mb-2">{{ __translator('From Email Address') }}</label>
            <input type="email" 
                   id="from_email" 
                   name="from_email" 
                   value="{{ old('from_email') }}"
                   required
                   class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                   style="background-color: var(--card-background-color); color: var(--content-text-color);"
                   placeholder="your@email.com">
        </div>

        {{-- Subject --}}
        <div>
            <label for="subject" class="block text-sm font-medium mb-2">{{ __translator('Subject') }}</label>
            <select id="subject" 
                    name="subject" 
                    required
                    class="w-full px-4 py-3 rounded-md border-0 focus:ring-2"
                    style="background-color: var(--card-background-color); color: var(--content-text-color);">
                <option value="">{{ __translator('Select a subject...') }}</option>
                @foreach(\App\Models\SupportTicket::subjectCategories() as $value => $label)
                <option value="{{ $value }}" {{ old('subject') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Message --}}
        <div>
            <label for="message" class="block text-sm font-medium mb-2">{{ __translator('Message') }}</label>
            <textarea id="message" 
                      name="message" 
                      rows="6"
                      required
                      class="w-full px-4 py-3 rounded-md border-0 focus:ring-2 resize-none"
                      style="background-color: var(--card-background-color); color: var(--content-text-color);"
                      placeholder="{{ __translator('How can we help you?') }}">{{ old('message') }}</textarea>
        </div>

        {{-- Attachments --}}
        <div>
            <label class="block text-sm font-medium mb-2">{{ __translator('Attachments') }} <span class="opacity-50">({{ __translator('optional') }})</span></label>
            
            {{-- Drag & Drop File Upload Zone --}}
            <div id="dropzone" 
                 class="relative rounded-lg border-2 border-dashed p-6 text-center cursor-pointer transition-all duration-200"
                 style="background-color: var(--card-background-color); border-color: var(--sidebar-hover-background-color);"
                 ondragover="handleDragOver(event)" 
                 ondragleave="handleDragLeave(event)" 
                 ondrop="handleDrop(event)"
                 onclick="document.getElementById('file-input').click()">
                
                <input type="file" 
                       id="file-input"
                       name="attachments[]" 
                       multiple
                       class="hidden"
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip"
                       onchange="handleFileSelect(this.files)">
                
                <div id="dropzone-content" class="pointer-events-none">
                    <svg class="mx-auto h-12 w-12 opacity-40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm font-medium mb-1">{{ __translator('Drag & drop files here') }}</p>
                    <p class="text-xs opacity-50">{{ __translator('or click to browse') }}</p>
                </div>
            </div>
            
            {{-- File List Preview --}}
            <div id="file-list" class="mt-3 space-y-2 hidden"></div>
            
            <p class="text-xs opacity-50 mt-2">{{ __translator('Max 5 files. Allowed: images, PDF, DOC, TXT, ZIP (max 10MB each)') }}</p>
        </div>

        {{-- Submit Button --}}
        <div>
            <button type="submit" 
                    class="w-full px-6 py-3 text-lg font-medium rounded-md transition-colors hover:opacity-90"
                    style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                {{ __translator('Submit Form Now') }}
            </button>
        </div>

    </form>

    <script>
        let selectedFiles = [];
        const maxFiles = 5;
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('dropzone');
            dropzone.style.borderColor = 'var(--brand-primary-color)';
            dropzone.style.backgroundColor = 'var(--sidebar-hover-background-color)';
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropzone = document.getElementById('dropzone');
            dropzone.style.borderColor = 'var(--sidebar-hover-background-color)';
            dropzone.style.backgroundColor = 'var(--card-background-color)';
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            handleDragLeave(e);
            const files = e.dataTransfer.files;
            handleFileSelect(files);
        }

        function handleFileSelect(files) {
            for (let i = 0; i < files.length; i++) {
                if (selectedFiles.length >= maxFiles) {
                    alert('Maximum ' + maxFiles + ' files allowed.');
                    break;
                }
                
                const file = files[i];
                const ext = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(ext)) {
                    alert('File type not allowed: ' + file.name);
                    continue;
                }
                
                if (file.size > maxSize) {
                    alert('File too large (max 10MB): ' + file.name);
                    continue;
                }
                
                // Check for duplicates
                if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    continue;
                }
                
                selectedFiles.push(file);
            }
            
            updateFileList();
            updateFileInput();
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
            updateFileInput();
        }

        function updateFileList() {
            const fileList = document.getElementById('file-list');
            
            if (selectedFiles.length === 0) {
                fileList.classList.add('hidden');
                fileList.innerHTML = '';
                return;
            }
            
            fileList.classList.remove('hidden');
            fileList.innerHTML = selectedFiles.map((file, index) => {
                const size = formatFileSize(file.size);
                const icon = getFileIcon(file.name);
                return `
                    <div class="flex items-center justify-between p-3 rounded-md" style="background-color: var(--content-background-color);">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-lg flex-shrink-0">${icon}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">${escapeHtml(file.name)}</p>
                                <p class="text-xs opacity-50">${size}</p>
                            </div>
                        </div>
                        <button type="button" onclick="removeFile(${index})" 
                                class="flex-shrink-0 p-1 rounded hover:opacity-70 transition-opacity"
                                style="color: var(--status-error-color);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                `;
            }).join('');
        }

        function updateFileInput() {
            const input = document.getElementById('file-input');
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '📄',
                'doc': '📝', 'docx': '📝',
                'txt': '📃',
                'zip': '📦',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
            };
            return icons[ext] || '📎';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</div>
@endsection

@push('head')
@if(config('recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
<style>
    .grecaptcha-badge {
        bottom: 70px !important;
    }
</style>
@endif
@endpush

@push('scripts')
<script>
    const RECAPTCHA_SITE_KEY = '{{ config('recaptcha.site_key') }}';

    if (RECAPTCHA_SITE_KEY && typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(function() {
            const supportForm = document.getElementById('support-form');
            if (supportForm) {
                supportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'support_submit'}).then(function(token) {
                        document.getElementById('support-recaptcha-token').value = token;
                        supportForm.submit();
                    });
                });
            }
        });
    }
</script>
@endpush
